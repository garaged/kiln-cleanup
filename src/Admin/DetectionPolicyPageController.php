<?php

declare(strict_types=1);

namespace KilnCleanup\Admin;

use InvalidArgumentException;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Infrastructure\WordPressDetectionPolicyRepository;

final class DetectionPolicyPageController
{
    public function register(): void
    {
        add_submenu_page('tools.php', __('Kiln Cleanup Policy', 'kiln-cleanup'), __('Kiln Cleanup Policy', 'kiln-cleanup'), 'manage_options', 'kiln-cleanup-policy', [$this, 'render']);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to manage this policy.', 'kiln-cleanup'));
        }

        $repository = new WordPressDetectionPolicyRepository();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('kiln_cleanup_save_policy');

            try {
                $repository->save(new DetectionPolicy(
                    'm3-v1',
                    $this->ids($_POST['protected_user_ids'] ?? ''),
                    $this->lines($_POST['protected_emails'] ?? ''),
                    $this->lines($_POST['blocked_email_domains'] ?? ''),
                ));
                echo '<div class="notice notice-success"><p>' . esc_html__('Policy saved. Existing accounts were not changed.', 'kiln-cleanup') . '</p></div>';
            } catch (InvalidArgumentException $exception) {
                echo '<div class="notice notice-error"><p>' . esc_html($exception->getMessage()) . '</p></div>';
            }
        }

        $policy = $repository->load();
        echo '<div class="wrap"><h1>' . esc_html__('Kiln Cleanup: Detection Policy', 'kiln-cleanup') . '</h1><p>' . esc_html__('This policy only changes review signals. It cannot change user accounts.', 'kiln-cleanup') . '</p><form method="post">';
        wp_nonce_field('kiln_cleanup_save_policy');
        $this->textarea('protected_user_ids', __('Protected user IDs (one per line)', 'kiln-cleanup'), implode("\n", $policy->protectedUserIds));
        $this->textarea('protected_emails', __('Protected emails (one per line)', 'kiln-cleanup'), implode("\n", $policy->protectedEmails));
        $this->textarea('blocked_email_domains', __('Blocked email domains (one per line, no @)', 'kiln-cleanup'), implode("\n", $policy->blockedEmailDomains));
        submit_button(__('Save review policy', 'kiln-cleanup'));
        echo '</form></div>';
    }

    private function textarea(string $name, string $label, string $value): void
    {
        echo '<p><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label><br><textarea class="large-text code" rows="6" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea></p>';
    }

    /** @return list<string> */
    private function lines(mixed $input): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', sanitize_textarea_field((string) wp_unslash($input))) ?: [])));
        if (count($lines) > 500) {
            throw new InvalidArgumentException('A policy list cannot contain more than 500 entries.');
        }
        return $lines;
    }

    /** @return list<int> */
    private function ids(mixed $input): array
    {
        $lines = $this->lines($input);
        if (array_filter($lines, static fn (string $line): bool => ! ctype_digit($line))) {
            throw new InvalidArgumentException('Protected user IDs must be positive integer values.');
        }
        return array_values(array_unique(array_map('intval', $lines)));
    }
}
