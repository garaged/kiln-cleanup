<?php
/**
 * Plugin Name: Kiln Cleanup
 * Description: Safety-first, reviewable cleanup for WordPress.
 * Version: 0.1.0-dev
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Author: GaRaGeD
 * License: GPL-2.0-or-later
 * Text Domain: kiln-cleanup
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (! is_readable($autoload)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>'
            . esc_html__('Kiln Cleanup is missing its Composer dependencies.', 'kiln-cleanup')
            . '</p></div>';
    });
    return;
}

require_once $autoload;

\KilnCleanup\Plugin::boot(__FILE__);
