<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\ReviewJobCursorStore;

final class WpdbReviewJobCursorStore implements ReviewJobCursorStore
{
    public function compareAndAdvance(int $jobId, int $expectedCursor, int $nextCursor): bool
    {
        global $wpdb;

        $updated = $wpdb->query($wpdb->prepare(
            'UPDATE ' . $wpdb->prefix . 'kiln_cleanup_jobs SET cursor_user_id = %d, processed_candidates = processed_candidates + 1 WHERE id = %d AND cursor_user_id = %d AND state IN (%s, %s)',
            $nextCursor,
            $jobId,
            $expectedCursor,
            'running',
            'ready',
        ));

        return $updated === 1;
    }
}
