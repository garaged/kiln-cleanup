<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use WP_Error;
use WP_User;

final class WordPressQuarantineAuthBlocker
{
    public function block(WP_User|WP_Error $user): WP_User|WP_Error
    {
        if ($user instanceof WP_Error) {
            return $user;
        }
        if (get_user_meta($user->ID, '_kiln_cleanup_quarantined', true) === '1') {
            return new WP_Error('kiln_cleanup_quarantined', __('This account is unavailable. Contact a site administrator.', 'kiln-cleanup'));
        }
        return $user;
    }
}
