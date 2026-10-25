<?php

declare(strict_types=1);

namespace KilnCleanup\Admin;

use KilnCleanup\Application\QuarantineService;
use KilnCleanup\Application\ReviewJobSnapshotCodec;
use KilnCleanup\Infrastructure\QuarantineAuditRecorder;
use KilnCleanup\Infrastructure\WordPressUserAccountGateway;
use KilnCleanup\Infrastructure\WpdbQuarantineCandidateRepository;
use KilnCleanup\Infrastructure\WpdbQuarantineRepository;
use RuntimeException;

final class QuarantinePageController
{
    private const PAGE = 'kiln-cleanup-quarantine';
    private const ACTION = 'kiln_cleanup_quarantine';

    public function register(): void
    {
        add_management_page(__('Kiln Cleanup Quarantine', 'kiln-cleanup'), __('Kiln Cleanup Quarantine', 'kiln-cleanup'), 'manage_options', self::PAGE, [$this, 'render']);
    }

    public function handlePost(): void
    {
        $this->authorize();
        check_admin_referer(self::ACTION);
        try {
            $operation = sanitize_key((string) wp_unslash($_POST['operation'] ?? ''));
            $userId = absint(wp_unslash($_POST['user_id'] ?? 0));
            if ($userId < 1) {
                throw new RuntimeException('A valid account ID is required.');
            }
            $service = new QuarantineService(new WpdbQuarantineCandidateRepository(), new WpdbQuarantineRepository(), new WordPressUserAccountGateway(), new QuarantineAuditRecorder());
            if ($operation === 'quarantine') {
                $jobId = absint(wp_unslash($_POST['job_id'] ?? 0));
                $confirmation = trim(sanitize_text_field(wp_unslash($_POST['confirmation'] ?? '')));
                if (! hash_equals('QUARANTINE ' . $userId, $confirmation)) {
                    throw new RuntimeException('Confirmation must exactly match QUARANTINE ' . $userId . '.');
                }
                $candidate = (new WpdbQuarantineCandidateRepository())->eligibleCandidate($jobId, $userId);
                if ($candidate === null) {
                    throw new RuntimeException('This account is not an eligible candidate in that review job.');
                }
                $policy = (new ReviewJobSnapshotCodec())->policyFromSnapshot($candidate['policy_snapshot']);
                $service->quarantine($jobId, $userId, $policy, get_current_user_id());
                $this->redirect('quarantined');
            }
            if ($operation === 'restore') {
                $confirmation = trim(sanitize_text_field(wp_unslash($_POST['confirmation'] ?? '')));
                if (! hash_equals('RESTORE ' . $userId, $confirmation)) {
                    throw new RuntimeException('Confirmation must exactly match RESTORE ' . $userId . '.');
                }
                $service->restore($userId, get_current_user_id());
                $this->redirect('restored');
            }
            throw new RuntimeException('Unsupported quarantine operation.');
        } catch (\Throwable $error) {
            $this->redirect('error', $error->getMessage());
        }
    }

    public function render(): void
    {
        $this->authorize();
        $notice = sanitize_key((string) wp_unslash($_GET['kiln_cleanup_notice'] ?? ''));
        $error = sanitize_text_field((string) wp_unslash($_GET['kiln_cleanup_error'] ?? ''));
        echo '<div class="wrap"><h1>' . esc_html__('Kiln Cleanup: Quarantine', 'kiln-cleanup') . '</h1>';
        echo '<p>' . esc_html__('Quarantine is reversible. It removes account access, destroys existing sessions, and blocks future authentication. It never deletes the account or content.', 'kiln-cleanup') . '</p>';
        if ($notice === 'quarantined') { echo '<div class="notice notice-success"><p>' . esc_html__('Account quarantined.', 'kiln-cleanup') . '</p></div>'; }
        if ($notice === 'restored') { echo '<div class="notice notice-success"><p>' . esc_html__('Account access restored; previous sessions remain invalidated.', 'kiln-cleanup') . '</p></div>'; }
        if ($error !== '') { echo '<div class="notice notice-error"><p><strong>' . esc_html__('No account change was completed.', 'kiln-cleanup') . '</strong> ' . esc_html($error) . '</p></div>'; }
        $this->renderEligible();
        $this->renderActive();
        echo '</div>';
    }

    private function renderEligible(): void
    {
        $candidates = (new WpdbQuarantineCandidateRepository())->listEligible(50);
        echo '<h2>' . esc_html__('Eligible review candidates', 'kiln-cleanup') . '</h2><p>' . esc_html__('Only high-confidence candidates are shown. A snapshot allowlist is checked again immediately before quarantine.', 'kiln-cleanup') . '</p>';
        echo '<table class="widefat striped"><thead><tr><th>Review job</th><th>Account ID</th><th>Email</th><th>Confirmation</th><th>Action</th></tr></thead><tbody>';
        foreach ($candidates as $candidate) {
            $email = $candidate['email'] !== '' ? $candidate['email'] : __('Account no longer exists', 'kiln-cleanup');
            echo '<tr><td>' . esc_html((string) $candidate['job_id']) . '</td><td>' . esc_html((string) $candidate['user_id']) . '</td><td>' . esc_html($email) . '</td><td>';
            $this->form($candidate['user_id'], 'quarantine', $candidate['job_id']);
            echo '</td><td>' . esc_html__('Type the required confirmation, then submit.', 'kiln-cleanup') . '</td></tr>';
        }
        if ($candidates === []) { echo '<tr><td colspan="5">' . esc_html__('No eligible candidates exist in completed review snapshots.', 'kiln-cleanup') . '</td></tr>'; }
        echo '</tbody></table>';
    }

    private function renderActive(): void
    {
        $records = (new WpdbQuarantineRepository())->listActive(50);
        echo '<h2>' . esc_html__('Active quarantines', 'kiln-cleanup') . '</h2><table class="widefat striped"><thead><tr><th>Account ID</th><th>Email</th><th>Source job</th><th>Confirmation</th><th>Action</th></tr></thead><tbody>';
        foreach ($records as $record) {
            $user = get_user_by('id', $record->userId);
            $email = $user instanceof \WP_User ? $user->user_email : __('Account no longer exists', 'kiln-cleanup');
            echo '<tr><td>' . esc_html((string) $record->userId) . '</td><td>' . esc_html($email) . '</td><td>' . esc_html((string) $record->sourceJobId) . '</td><td>';
            $this->form($record->userId, 'restore');
            echo '</td><td>' . esc_html__('Restore the exact captured capabilities; prior sessions stay invalidated.', 'kiln-cleanup') . '</td></tr>';
        }
        if ($records === []) { echo '<tr><td colspan="5">' . esc_html__('No accounts are actively quarantined.', 'kiln-cleanup') . '</td></tr>'; }
        echo '</tbody></table>';
    }

    private function form(int $userId, string $operation, int $jobId = 0): void
    {
        $token = strtoupper($operation) . ' ' . $userId;
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field(self::ACTION);
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '"><input type="hidden" name="operation" value="' . esc_attr($operation) . '"><input type="hidden" name="user_id" value="' . esc_attr((string) $userId) . '">';
        if ($jobId > 0) { echo '<input type="hidden" name="job_id" value="' . esc_attr((string) $jobId) . '">'; }
        echo '<label for="confirmation-' . esc_attr($operation . '-' . $userId) . '">' . esc_html(sprintf(__('Type %s exactly:', 'kiln-cleanup'), $token)) . '</label> <input id="confirmation-' . esc_attr($operation . '-' . $userId) . '" name="confirmation" required autocomplete="off" placeholder="' . esc_attr($token) . '"> ';
        submit_button(ucfirst($operation), 'secondary small', 'submit', false);
        echo '</form>';
    }

    private function authorize(): void
    {
        if (! current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to manage quarantines.', 'kiln-cleanup')); }
    }

    private function redirect(string $notice, string $error = ''): never
    {
        $query = ['page' => self::PAGE, 'kiln_cleanup_notice' => $notice];
        if ($error !== '') { $query['kiln_cleanup_error'] = $error; }
        wp_safe_redirect(add_query_arg($query, admin_url('tools.php')));
        exit;
    }
}
