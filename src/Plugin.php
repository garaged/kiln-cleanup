<?php

declare(strict_types=1);

namespace KilnCleanup;

use KilnCleanup\Application\BackgroundReviewJobWorkerFactory;
use KilnCleanup\Admin\DetectionPolicyPageController;
use KilnCleanup\Admin\ReviewJobsPageController;
use KilnCleanup\Admin\QuarantinePageController;
use KilnCleanup\Admin\PermanentDeletionPageController;
use KilnCleanup\Admin\PreventionPageController;
use KilnCleanup\Infrastructure\AuditRecorder;
use KilnCleanup\Admin\UserInventoryPageController;
use KilnCleanup\Infrastructure\DatabaseSchema;
use KilnCleanup\Infrastructure\WordPressQuarantineAuthBlocker;
use KilnCleanup\Support\Environment;

final class Plugin
{
    private const MINIMUM_WORDPRESS_VERSION = '6.9';
    private const MINIMUM_PHP_VERSION = '8.2.0';

    public static function boot(string $pluginFile): void
    {
        register_activation_hook($pluginFile, [self::class, 'activate']);
        register_deactivation_hook($pluginFile, [self::class, 'deactivate']);

        $reviewJobs = new ReviewJobsPageController();
        $quarantine = new QuarantinePageController();
        $deletion = new PermanentDeletionPageController();
        $prevention = new PreventionPageController();
        add_action('admin_post_kiln_cleanup_review_job', [$reviewJobs, 'handlePost']);
        add_action('admin_post_kiln_cleanup_quarantine', [$quarantine, 'handlePost']);
        add_action('admin_post_kiln_cleanup_permanent_deletion', [$deletion, 'handlePost']);
        add_action('admin_post_kiln_cleanup_audit_export', [$deletion, 'exportAudit']);
        add_action('admin_post_kiln_cleanup_prevention', [$prevention, 'handlePost']);
        add_filter('wp_authenticate_user', [(new WordPressQuarantineAuthBlocker()), 'block'], 10, 1);
        add_filter('registration_errors', static function (\WP_Error $errors, string $sanitizedUserLogin, string $userEmail): \WP_Error {
            if ((bool) apply_filters('kiln_cleanup_registration_rate_limited', false, $sanitizedUserLogin, $userEmail)) {
                $errors->add('kiln_cleanup_registration_rate_limited', __('Registration is unavailable. Please try again later.', 'kiln-cleanup'));
            }
            return $errors;
        }, 10, 3);
        add_action('user_register', static function (int $userId): void {
            if (get_option('kiln_cleanup_registration_evidence_enabled') === '1') {
                (new AuditRecorder())->recordRegistrationObserved($userId);
            }
        });
        add_filter('cron_schedules', static function (array $schedules): array {
            $schedules['kiln_cleanup_minute'] = ['interval' => 60, 'display' => __('Every minute', 'kiln-cleanup')];
            return $schedules;
        });
        add_action('kiln_cleanup_run_review_jobs', static function (): void {
            try {
                BackgroundReviewJobWorkerFactory::create()->runOne();
            } catch (\Throwable) {
                // The worker marks its claimed job as failed. A cron fault must never break a site request.
                error_log('Kiln Cleanup background review worker failed. Inspect the review-job status for recovery details.');
            }
        });

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('kiln-cleanup review-jobs', \KilnCleanup\Cli\ReviewJobsCommand::class);
        }

        add_action('plugins_loaded', [DatabaseSchema::class, 'maybeUpgrade']);

        add_action('admin_init', static function (): void {
            $issue = Environment::compatibilityIssue(self::MINIMUM_PHP_VERSION, self::MINIMUM_WORDPRESS_VERSION);
            if ($issue !== null) {
                add_action('admin_notices', static function () use ($issue): void {
                    echo '<div class="notice notice-error"><p>' . esc_html($issue) . '</p></div>';
                });
            }
        });

        add_action('admin_menu', static function () use ($reviewJobs, $quarantine, $deletion, $prevention): void {
            (new UserInventoryPageController())->register();
            (new DetectionPolicyPageController())->register();
            $reviewJobs->register();
            $quarantine->register();
            $deletion->register();
            $prevention->register();
        });
    }

    public static function activate(): void
    {
        $issue = Environment::compatibilityIssue(self::MINIMUM_PHP_VERSION, self::MINIMUM_WORDPRESS_VERSION);
        if ($issue !== null) {
            wp_die(esc_html($issue), esc_html__('Kiln Cleanup cannot be activated', 'kiln-cleanup'));
        }
        DatabaseSchema::install();
        if (! wp_next_scheduled('kiln_cleanup_run_review_jobs')) {
            wp_schedule_event(time() + 60, 'kiln_cleanup_minute', 'kiln_cleanup_run_review_jobs');
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('kiln_cleanup_run_review_jobs');
    }
}
