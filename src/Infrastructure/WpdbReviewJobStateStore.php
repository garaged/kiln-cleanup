<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\ReviewJobStateStore;
use KilnCleanup\Domain\JobState;

final class WpdbReviewJobStateStore implements ReviewJobStateStore
{
    public function compareAndTransition(int $jobId, JobState $expected, JobState $next): bool
    {
        global $wpdb;

        return $wpdb->query($wpdb->prepare(
            'UPDATE ' . $wpdb->prefix . 'kiln_cleanup_jobs SET state = %s, finished_at = CASE WHEN %s IN (%s, %s) THEN %s ELSE finished_at END WHERE id = %d AND state = %s',
            $next->value,
            $next->value,
            JobState::Completed->value,
            JobState::Cancelled->value,
            current_time('mysql', true),
            $jobId,
            $expected->value,
        )) === 1;
    }
}
