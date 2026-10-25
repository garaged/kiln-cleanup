<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\ReviewJobClaimStore;
use KilnCleanup\Domain\JobState;
use KilnCleanup\Domain\ReviewJob;

final class WpdbReviewJobClaimStore implements ReviewJobClaimStore
{
    public function claimNext(string $token, int $leaseSeconds, ?int $jobId = null): ?ReviewJob
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $expires = gmdate('Y-m-d H:i:s', time() + $leaseSeconds);
        $table = $wpdb->prefix . 'kiln_cleanup_jobs';
        $whereId = $jobId === null ? '' : $wpdb->prepare(' AND id = %d', $jobId);
        $id = $wpdb->get_var("SELECT id FROM {$table} WHERE type = 'review' AND state IN ('ready', 'running') AND (claim_expires_at IS NULL OR claim_expires_at < '{$now}'){$whereId} ORDER BY id ASC LIMIT 1");
        if ($id === null) {
            return null;
        }
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET claim_token = %s, claim_expires_at = %s, last_error = NULL WHERE id = %d AND state IN (%s, %s) AND (claim_expires_at IS NULL OR claim_expires_at < %s)",
            $token, $expires, (int) $id, JobState::Ready->value, JobState::Running->value, $now,
        ));
        if ($updated !== 1) {
            return null;
        }
        return (new WpdbReviewJobRepository())->get((int) $id);
    }

    public function release(int $jobId, string $token): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'kiln_cleanup_jobs SET claim_token = NULL, claim_expires_at = NULL WHERE id = %d AND claim_token = %s', $jobId, $token));
    }

    public function fail(int $jobId, string $token, string $message): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'kiln_cleanup_jobs SET state = %s, claim_token = NULL, claim_expires_at = NULL, last_error = %s WHERE id = %d AND claim_token = %s AND state = %s', JobState::Failed->value, substr($message, 0, 255), $jobId, $token, JobState::Running->value));
    }
}
