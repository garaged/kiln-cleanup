<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\UserAccountGateway;
use RuntimeException;
use WP_Session_Tokens;
use WP_User;

final class WordPressUserAccountGateway implements UserAccountGateway
{
    public function emailFor(int $userId): ?string
    {
        $user = get_userdata($userId);
        return $user === false ? null : (string) $user->user_email;
    }

    public function snapshotAccess(int $userId): array
    {
        global $wpdb;
        $user = get_userdata($userId);
        if ($user === false) {
            throw new RuntimeException('The account no longer exists.');
        }
        $capabilities = get_user_meta($userId, $wpdb->prefix . 'capabilities', true);
        if (! is_array($capabilities)) {
            $capabilities = [];
        }
        return ['capabilities' => $capabilities, 'user_level' => (int) get_user_meta($userId, $wpdb->prefix . 'user_level', true)];
    }

    public function removeAccessAndSessions(int $userId): void
    {
        global $wpdb;
        $user = new WP_User($userId);
        if (! $user->exists()) {
            throw new RuntimeException('The account no longer exists.');
        }
        update_user_meta($userId, $wpdb->prefix . 'capabilities', []);
        update_user_meta($userId, $wpdb->prefix . 'user_level', 0);
        update_user_meta($userId, '_kiln_cleanup_quarantined', '1');
        clean_user_cache($userId);
        WP_Session_Tokens::get_instance($userId)->destroy_all();
    }

    public function restoreAccessAndInvalidateSessions(int $userId, array $capabilities, int $userLevel): void
    {
        global $wpdb;
        $user = new WP_User($userId);
        if (! $user->exists()) {
            throw new RuntimeException('The account no longer exists.');
        }
        update_user_meta($userId, $wpdb->prefix . 'capabilities', $capabilities);
        update_user_meta($userId, $wpdb->prefix . 'user_level', $userLevel);
        delete_user_meta($userId, '_kiln_cleanup_quarantined');
        clean_user_cache($userId);
        WP_Session_Tokens::get_instance($userId)->destroy_all();
    }
}
