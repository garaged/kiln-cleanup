<?php

declare(strict_types=1);

namespace KilnCleanup\Admin;

use KilnCleanup\Infrastructure\AuditRecorder;
use RuntimeException;

final class PreventionPageController
{
    private const PAGE = 'kiln-cleanup-prevention';
    private const ACTION = 'kiln_cleanup_prevention';
    private const EVIDENCE_OPTION = 'kiln_cleanup_registration_evidence_enabled';

    public function register(): void
    {
        add_management_page(__('Kiln Cleanup Prevention', 'kiln-cleanup'), __('Kiln Cleanup Prevention', 'kiln-cleanup'), 'manage_options', self::PAGE, [$this, 'render']);
    }

    public function handlePost(): void
    {
        $this->authorize();
        check_admin_referer(self::ACTION);
        try {
            $operation = sanitize_key((string) wp_unslash($_POST['operation'] ?? ''));
            $audit = new AuditRecorder();
            if ($operation === 'lockdown') {
                $confirmation = trim(sanitize_text_field(wp_unslash($_POST['confirmation'] ?? '')));
                if (! hash_equals('LOCKDOWN REGISTRATION', $confirmation)) {
                    throw new RuntimeException('Confirmation must exactly match LOCKDOWN REGISTRATION.');
                }
                update_option('users_can_register', 0);
                $audit->recordRegistrationLockdown(get_current_user_id());
                $this->redirect('locked');
            }
            if ($operation === 'evidence') {
                $enabled = isset($_POST['evidence_enabled']) && (string) wp_unslash($_POST['evidence_enabled']) === '1';
                update_option(self::EVIDENCE_OPTION, $enabled ? '1' : '0', false);
                $audit->recordRegistrationEvidenceSetting($enabled, get_current_user_id());
                $this->redirect('evidence');
            }
            throw new RuntimeException('Unsupported prevention operation.');
        } catch (\Throwable $error) {
            $this->redirect('error', $error->getMessage());
        }
    }

    public function render(): void
    {
        $this->authorize();
        $notice = sanitize_key((string) wp_unslash($_GET['kiln_cleanup_notice'] ?? ''));
        $error = sanitize_text_field((string) wp_unslash($_GET['kiln_cleanup_error'] ?? ''));
        $registrationEnabled = (bool) get_option('users_can_register');
        $evidenceEnabled = get_option(self::EVIDENCE_OPTION) === '1';
        echo '<div class="wrap"><h1>' . esc_html__('Kiln Cleanup: Prevention', 'kiln-cleanup') . '</h1>';
        echo '<p>' . esc_html__('Prevention controls are local and reversible. They never quarantine, restore, delete, or change an existing account.', 'kiln-cleanup') . '</p>';
        if ($notice === 'locked') { echo '<div class="notice notice-success"><p>' . esc_html__('Public registration has been disabled.', 'kiln-cleanup') . '</p></div>'; }
        if ($notice === 'evidence') { echo '<div class="notice notice-success"><p>' . esc_html__('Registration evidence preference saved.', 'kiln-cleanup') . '</p></div>'; }
        if ($error !== '') { echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>'; }
        echo '<h2>' . esc_html__('Public registration', 'kiln-cleanup') . '</h2><p>' . esc_html($registrationEnabled ? __('WordPress currently allows public registration.', 'kiln-cleanup') : __('Public registration is currently disabled.', 'kiln-cleanup')) . '</p>';
        if ($registrationEnabled) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field(self::ACTION);
            echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '"><input type="hidden" name="operation" value="lockdown">';
            echo '<label for="lockdown-confirmation">' . esc_html__('Type LOCKDOWN REGISTRATION exactly:', 'kiln-cleanup') . '</label> <input id="lockdown-confirmation" name="confirmation" required autocomplete="off" placeholder="LOCKDOWN REGISTRATION"> ';
            submit_button(__('Disable public registration', 'kiln-cleanup'), 'delete small', 'submit', false);
            echo '</form>';
        } else {
            echo '<p class="description">' . esc_html__('To enable it again, use WordPress Settings → General. Kiln does not silently re-open registration.', 'kiln-cleanup') . '</p>';
        }
        echo '<h2>' . esc_html__('Optional registration evidence', 'kiln-cleanup') . '</h2><p>' . esc_html__('Disabled by default. When enabled, successful registrations create a local audit event with only the account ID and event time. Kiln never retains IP address, user-agent, email, password, or request payload.', 'kiln-cleanup') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field(self::ACTION);
        echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '"><input type="hidden" name="operation" value="evidence">';
        echo '<label><input type="checkbox" name="evidence_enabled" value="1"' . checked($evidenceEnabled, true, false) . '> ' . esc_html__('Record redacted registration audit events', 'kiln-cleanup') . '</label> ';
        submit_button(__('Save evidence preference', 'secondary', 'submit', false);
        echo '</form>';
        echo '<h2>' . esc_html__('Rate-control integration', 'kiln-cleanup') . '</h2><p><code>kiln_cleanup_registration_rate_limited</code> ' . esc_html__('is a local WordPress filter. Return true from another local plugin to reject a registration before account creation. Kiln sets no thresholds, collects no request data, and calls no external service.', 'kiln-cleanup') . '</p></div>';
    }

    private function authorize(): void
    {
        if (! current_user_can('manage_options')) { wp_die(esc_html__('You are not allowed to manage prevention controls.', 'kiln-cleanup')); }
    }

    private function redirect(string $notice, string $error = ''): never
    {
        $query = ['page' => self::PAGE, 'kiln_cleanup_notice' => $notice];
        if ($error !== '') { $query['kiln_cleanup_error'] = $error; }
        wp_safe_redirect(add_query_arg($query, admin_url('tools.php')));
        exit;
    }
}
