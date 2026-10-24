<?php

declare(strict_types=1);

namespace KilnCleanup;

use KilnCleanup\Admin\UserInventoryPageController;
use KilnCleanup\Infrastructure\DatabaseSchema;
use KilnCleanup\Support\Environment;

final class Plugin
{
    private const MINIMUM_WORDPRESS_VERSION = '6.9';
    private const MINIMUM_PHP_VERSION = '8.2.0';

    public static function boot(string $pluginFile): void
    {
        register_activation_hook($pluginFile, [self::class, 'activate']);

        add_action('admin_init', static function (): void {
            $issue = Environment::compatibilityIssue(
                self::MINIMUM_PHP_VERSION,
                self::MINIMUM_WORDPRESS_VERSION,
            );

            if ($issue !== null) {
                add_action('admin_notices', static function () use ($issue): void {
                    echo '<div class="notice notice-error"><p>'
                        . esc_html($issue)
                        . '</p></div>';
                });
            }
        });

        add_action('admin_menu', static function (): void {
            (new UserInventoryPageController())->register();
        });
    }

    public static function activate(): void
    {
        $issue = Environment::compatibilityIssue(
            self::MINIMUM_PHP_VERSION,
            self::MINIMUM_WORDPRESS_VERSION,
        );

        if ($issue !== null) {
            wp_die(esc_html($issue), esc_html__('Kiln Cleanup cannot be activated', 'kiln-cleanup'));
        }

        DatabaseSchema::install();
    }
}
