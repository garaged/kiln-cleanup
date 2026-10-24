<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\ReviewJobAuditRecorder as ReviewJobAuditRecorderContract;
use KilnCleanup\Domain\JobState;

final class ReviewJobAuditRecorder implements ReviewJobAuditRecorderContract
{
    public function recordCreated(int $jobId, int $actorUserId, array $policySnapshot, array $filterSnapshot): void
    {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'kiln_cleanup_audit_events', [
            'event_type' => 'review_job_created',
            'job_id' => $jobId,
            'target_user_id' => null,
            'actor_user_id' => $actorUserId,
            'context' => wp_json_encode([
                'policy_version' => $policySnapshot['version'] ?? 'unknown',
                'policy_digest' => hash('sha256', wp_json_encode($policySnapshot, JSON_THROW_ON_ERROR)),
                'has_registered_before_filter' => ($filterSnapshot['registered_before'] ?? null) !== null,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => current_time('mysql', true),
        ]);
    }

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

    public function recordScanBatch(int $jobId, int $actorUserId, int $fromCursor, ?int $toCursor, int $processedCount, int $insertedCandidateCount): void
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
                'processed_count' => $processedCount,
                'inserted_candidate_count' => $insertedCandidateCount,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => current_time('mysql', true),
        ]);
    }
}
