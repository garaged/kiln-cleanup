<?php

declare(strict_types=1);

namespace KilnCleanup\Admin;

use KilnCleanup\Application\PermanentDeletionService;
use KilnCleanup\Infrastructure\QuarantineAuditRecorder;
use KilnCleanup\Infrastructure\WordPressPermanentDeletionGateway;
use KilnCleanup\Infrastructure\WpdbQuarantineCandidateRepository;
use KilnCleanup\Infrastructure\WpdbQuarantineRepository;
use RuntimeException;

final class PermanentDeletionPageController
{
    private const PAGE = 'kiln-cleanup-permanent-deletion';
    private const ACTION = 'kiln_cleanup_permanent_deletion';
    private const EXPORT_ACTION = 'kiln_cleanup_audit_export';

    public function register(): void
    {
        add_management_page(__('Kiln Cleanup Permanent Deletion', 'kiln-cleanup'), __('Kiln Cleanup Deletion', 'kiln-cleanup'), 'manage_options', self::PAGE, [$this, 'render']);
    }

    public function handlePost(): void
    {
        $this->authorize();
        check_admin_referer(self::ACTION);
        try {
            $userId = absint(wp_unslash($_POST['user_id'] ?? 0));
            $confirmation = trim(sanitize_text_field(wp_unslash($_POST['confirmation'] ?? '')));
            if ($userId < 1 || ! hash_equals('DELETE ' . $userId, $confirmation)) {
                throw new RuntimeException('Confirmation must exactly match DELETE ' . $userId . '.');
            }
            $this->service()->delete($userId, get_current_user_id());
            $this->redirect('deleted');
        } catch (\Throwable $error) {
            $this->redirect('error', $error->getMessage());
        }
    }

    public function exportAudit(): never
    {
        $this->authorize();
        check_admin_referer(self::EXPORT_ACTION);
        global $wpdb;
        $rows = $wpdb->get_results('SELECT event_type, job_id, target_user_id, actor_user_id, context, occurred_at FROM ' . $wpdb->prefix . 'kiln_cleanup_audit_events ORDER BY id ASC', ARRAY_A);
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=kiln-cleanup-audit.csv');
        $output = fopen('php://output', 'wb');
        fputcsv($output, ['event_type', 'job_id', 'target_user_id', 'actor_user_id', 'context', 'occurred_at']);
        foreach ($rows as $row) {
            fputcsv($output, [$row['event_type'], $row['job_id'], $row['target_user_id'], $row['actor_user_id'], $row['context'], $row['occurred_at']]);
        }
        fclose($output);
        exit;
    }

    public function render(): void
    {
        $this->authorize();
        $notice = sanitize_key((string) wp_unslash($_GET['kiln_cleanup_notice'] ?? ''));
        $error = sanitize_text_field((string) wp_unslash($_GET['kiln_cleanup_error'] ?? ''));
        echo '<div class="wrap"><h1>' . esc_html__('Kiln Cleanup: Permanent Deletion', 'kiln-cleanup') . '</h1>';
        echo '<p><strong>' . esc_html__('This action permanently deletes one quarantined account and its authored content. It cannot be undone.', 'kiln-cleanup') . '</strong></p>';
        if ($notice === 'deleted') { echo '<div class="notice notice-success"><p>' . esc_html__('Account and authored content permanently deleted.', 'kiln-cleanup') . '</p></div>'; }
        if ($error !== '') { echo '<div class="notice notice-error"><p><strong>' . esc_html__('No deletion was completed.', 'kiln-cleanup') . '</strong> ' . esc_html($error) . '</p></div>'; }
        $this->renderAuditExport();
        if (is_multisite()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Permanent deletion is unavailable on WordPress Multisite. No network-user action is supported.', 'kiln-cleanup') . '</p></div></div>';
            return;
        }
        $this->renderPreflights();
        echo '</div>';
    }

    private function renderAuditExport(): void
    {
        echo '<p><a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . self::EXPORT_ACTION), self::EXPORT_ACTION)) . '">' . esc_html__('Export redacted audit CSV', 'kiln-cleanup') . '</a></p>';
    }

    private function renderPreflights(): void
    {
        $records = (new WpdbQuarantineRepository())->listActive(50);
        $service = $this->service();
        echo '<h2>' . esc_html__('Deletion preflight', 'kiln-cleanup') . '</h2><table class="widefat striped"><thead><tr><th>Account ID</th><th>Email</th><th>Source job</th><th>Authored content to delete</th><th>Grace period</th><th>Action</th></tr></thead><tbody>';
        foreach ($records as $record) {
            try {
                $preflight = $service->preflight($record->userId);
                $counts = $preflight->contentCounts === [] ? __('None', 'kiln-cleanup') : implode(', ', array_map(static fn (string $type, int $count): string => $type . ': ' . $count, array_keys($preflight->contentCounts), $preflight->contentCounts));
                $ready = $preflight->isEligibleAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
                echo '<tr><td>' . esc_html((string) $preflight->userId) . '</td><td>' . esc_html($preflight->email) . '</td><td>' . esc_html((string) $preflight->sourceJobId) . '</td><td>' . esc_html($counts) . '</td><td>' . esc_html($preflight->eligibleAt->format('Y-m-d H:i:s') . ' UTC') . '</td><td>';
                if ($ready) {
                    $this->renderDeleteForm($preflight->userId);
                } else {
                    echo esc_html__('Not yet eligible for deletion.', 'kiln-cleanup');
                }
                echo '</td></tr>';
            } catch (\Throwable $error) {
                echo '<tr><td>' . esc_html((string) $record->userId) . '</td><td colspan="5">' . esc_html($error->getMessage()) . '</td></tr>';
            }
        }
        if ($records === []) { echo '<tr><td colspan="6">' . esc_html__('No actively quarantined accounts exist.', 'kiln-cleanup') . '</td></tr>'; }
        echo '</tbody></table>';
    }

    private function renderDeleteForm(int $userId): void
    {
        $token = 'DELETE ' . $userId;
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field(self::ACTION);
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '"><input type="hidden" name="user_id" value="' . esc_attr((string) $userId) . '">';
        echo '<label for="delete-confirmation-' . esc_attr((string) $userId) . '">' . esc_html(sprintf(__('Type %s exactly:', 'kiln-cleanup'), $token)) . '</label> <input id="delete-confirmation-' . esc_attr((string) $userId) . '" name="confirmation" required autocomplete="off" placeholder="' . esc_attr($token) . '"> ';
        submit_button(__('Permanently delete account and content', 'kiln-cleanup'), 'delete small', 'submit', false);
        echo '</form>';
    }

    private function service(): PermanentDeletionService
    {
        return new PermanentDeletionService(new WpdbQuarantineRepository(), new WpdbQuarantineCandidateRepository(), new WordPressPermanentDeletionGateway(), new QuarantineAuditRecorder());
    }

    private function authorize(): void
    {
        if (! current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to permanently delete accounts.', 'kiln-cleanup')); }
    }

    private function redirect(string $notice, string $error = ''): never
    {
        $query = ['page' => self::PAGE, 'kiln_cleanup_notice' => $notice];
        if ($error !== '') { $query['kiln_cleanup_error'] = $error; }
        wp_safe_redirect(add_query_arg($query, admin_url('tools.php')));
        exit;
    }
}
