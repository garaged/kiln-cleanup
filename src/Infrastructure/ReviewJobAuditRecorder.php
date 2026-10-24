<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Domain\JobState;

final class ReviewJobAuditRecorder
{
    public function recordStateTransition(int $jobId, int $actorUserId, JobState $from, JobState $to): void
    {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'kiln_cleanup_audit_events', [
            'event_type' => 'review_job_state_changed',
            'job_id' => $jobId,
            'target_user_id' => null,
            'actor_user_id' => $actorUserId,
            'context' => wp_json_encode([
                'from_state' => $from->value,
                'to_state' => $to->value,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => current_time('mysql', true),
        ]);
    }

    public function recordScanBatch(int $jobId, int $actorUserId, int $fromCursor, ?int $toCursor, int $candidateCount): void
    {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'kiln_cleanup_audit_events', [
            'event_type' => 'review_job_scan_batch',
            'job_id' => $jobId,
            'target_user_id' => null,
            'actor_user_id' => $actorUserId,
            'context' => wp_json_encode([
                'from_cursor' => $fromCursor,
                'to_cursor' => $toCursor,
                'candidate_count' => $candidateCount,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => current_time('mysql', true),
        ]);
    }
}
