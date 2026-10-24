<?php

declare(strict_types=1);

namespace KilnCleanup;

use KilnCleanup\Application\BackgroundReviewJobWorkerFactory;
use KilnCleanup\Admin\DetectionPolicyPageController;
use KilnCleanup\Admin\ReviewJobsPageController;
use KilnCleanup\Admin\QuarantinePageController;
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
        add_action('admin_post_kiln_cleanup_review_job', [$reviewJobs, 'handlePost']);
        add_action('admin_post_kiln_cleanup_quarantine', [$quarantine, 'handlePost']);
        add_filter('wp_authenticate_user', [(new WordPressQuarantineAuthBlocker()), 'block'], 10, 1);
        add_filter('cron_schedules', static function (array $schedules): array {
            $schedules['kiln_cleanup_minute'] = ['interval' => 60, 'display' => __('Every minute', 'kiln-cleanup')];
            return $schedules;
        });
        add_action('kiln_cleanup_run_review_jobs', static function (): void {
            BackgroundReviewJobWorkerFactory::create()->runOne();
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

        add_action('admin_menu', static function () use ($reviewJobs): void {
            (new UserInventoryPageController())->register();
            (new DetectionPolicyPageController())->register();
            $reviewJobs->register();
            $quarantine->register();
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
