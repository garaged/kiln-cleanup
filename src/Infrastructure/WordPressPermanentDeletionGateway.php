<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\PermanentDeletionGateway;
use RuntimeException;

final class WordPressPermanentDeletionGateway implements PermanentDeletionGateway
{
    public function isMultisite(): bool
    {
        return is_multisite();
    }

    public function emailFor(int $userId): ?string
    {
        $user = get_userdata($userId);
        return $user === false ? null : (string) $user->user_email;
    }

    public function authoredContentCounts(int $userId): array
    {
        $counts = [];
        foreach (get_post_types(['show_ui' => true], 'names') as $type) {
            if ($type === 'revision' || $type === 'nav_menu_item') {
                continue;
            }
            $count = count_user_posts($userId, $type, true);
            if ($count > 0) {
                $counts[$type] = $count;
            }
        }
        return $counts;
    }

    public function deleteAccountAndAuthoredContent(int $userId): void
    {
        require_once ABSPATH . 'wp-admin/includes/user.php';

        $content = get_posts([
            'author' => $userId,
            'fields' => 'ids',
            'numberposts' => -1,
            'post_status' => 'any',
            'post_type' => 'any',
        ]);
        foreach ($content as $contentId) {
            if (get_post_type($contentId) === 'attachment') {
                wp_delete_attachment($contentId, true);
                continue;
            }
            wp_delete_post($contentId, true);
        }

        if (wp_delete_user($userId) !== true) {
            throw new RuntimeException('WordPress did not delete the account and authored content.');
        }
    }
}
