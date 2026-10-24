<?php

declare(strict_types=1);

namespace KilnCleanup\Admin;

use InvalidArgumentException;
use KilnCleanup\Application\UserInventoryService;
use KilnCleanup\Detection\DetectionEngine;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Infrastructure\WordPressDetectionPolicyRepository;
use KilnCleanup\Infrastructure\WpdbUserInventoryRepository;

final class UserInventoryPageController
{
    public function register(): void
    {
        add_management_page(__('Kiln Cleanup Inventory', 'kiln-cleanup'), __('Kiln Cleanup', 'kiln-cleanup'), 'manage_options', 'kiln-cleanup-inventory', [$this, 'render']);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to view this page.', 'kiln-cleanup'));
        }

        $cursor = isset($_GET['cursor']) ? absint(wp_unslash($_GET['cursor'])) : 0;
        $registeredBefore = isset($_GET['registered_before']) ? sanitize_text_field(wp_unslash($_GET['registered_before'])) : '';

        $filterError = null;
        try {
            $filter = InventoryFilter::fromRegisteredBeforeInput($registeredBefore);
        } catch (InvalidArgumentException $exception) {
            $filter = new InventoryFilter();
            $filterError = $exception->getMessage();
        }

        $page = (new UserInventoryService(new WpdbUserInventoryRepository()))->pageAfter($cursor, 100, $filter);
        $policy = (new WordPressDetectionPolicyRepository())->load();
        $engine = new DetectionEngine();

        echo '<div class="wrap"><h1>' . esc_html__('Kiln Cleanup: Candidate Review', 'kiln-cleanup') . '</h1><p>' . esc_html__('Read-only review. Scores are explanations, not proof; no account has been changed.', 'kiln-cleanup') . '</p>';
        if ($filterError !== null) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__('Filter not applied.', 'kiln-cleanup') . '</strong> ' . esc_html($filterError) . '</p></div>';
        }
        echo '<form method="get"><input type="hidden" name="page" value="kiln-cleanup-inventory"><label for="registered_before">' . esc_html__('Registered before', 'kiln-cleanup') . '</label> <input id="registered_before" name="registered_before" type="date" value="' . esc_attr($registeredBefore) . '"' . ($filterError !== null ? ' aria-invalid="true" aria-describedby="registered_before_error"' : ' aria-describedby="registered_before_help"') . '> ';
        if ($filterError !== null) {
            echo '<span id="registered_before_error" class="description">' . esc_html__('Enter a calendar date in YYYY-MM-DD format, or clear the field to review all users.', 'kiln-cleanup') . '</span> ';
        } else {
            echo '<span id="registered_before_help" class="description">' . esc_html__('Optional. Uses UTC and includes users registered before this date.', 'kiln-cleanup') . '</span> ';
        }
        echo '<button class="button">' . esc_html__('Apply filter', 'kiln-cleanup') . '</button></form>';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__('Login', 'kiln-cleanup') . '</th><th>' . esc_html__('Email', 'kiln-cleanup') . '</th><th>' . esc_html__('Registered', 'kiln-cleanup') . '</th><th>' . esc_html__('Review signal', 'kiln-cleanup') . '</th><th>' . esc_html__('Reasons', 'kiln-cleanup') . '</th></tr></thead><tbody>';

        foreach ($page->records as $record) {
            $result = $engine->evaluate($record, $policy);
            $reasons = $result->allowlisted
                ? __('Protected by allowlist', 'kiln-cleanup')
                : implode('; ', array_map(static fn ($reason): string => $reason->message, $result->reasons));
            echo '<tr><td>' . esc_html((string) $record->id) . '</td><td>' . esc_html($record->login) . '</td><td>' . esc_html($record->email) . '</td><td>' . esc_html($record->registeredAt->format('Y-m-d H:i:s')) . '</td><td>' . esc_html(sprintf('%d — %s', $result->score, $result->band->value)) . '</td><td>' . esc_html($reasons !== '' ? $reasons : __('No signal', 'kiln-cleanup')) . '</td></tr>';
        }

        if ($page->records === []) {
            echo '<tr><td colspan="6">' . esc_html__('No users match this inventory page.', 'kiln-cleanup') . '</td></tr>';
        }
        echo '</tbody></table>';

        if ($page->nextCursor !== null) {
            $url = add_query_arg(array_filter(['page' => 'kiln-cleanup-inventory', 'cursor' => $page->nextCursor, 'registered_before' => $registeredBefore !== '' ? $registeredBefore : null]), admin_url('tools.php'));
            echo '<p><a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Next page', 'kiln-cleanup') . '</a></p>';
        }
        echo '</div>';
    }
}
