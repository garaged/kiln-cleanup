<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\QuarantineAuditRecorder as QuarantineAuditRecorderContract;

final class QuarantineAuditRecorder implements QuarantineAuditRecorderContract
{
    public function recordQuarantined(int $sourceJobId, int $userId, int $actorUserId): void
    {
        $this->record('account_quarantined', $sourceJobId, $userId, $actorUserId);
    }

    public function recordRestored(int $sourceJobId, int $userId, int $actorUserId): void
    {
        $this->record('account_restored', $sourceJobId, $userId, $actorUserId);
    }

    private function record(string $eventType, int $jobId, int $userId, int $actorUserId): void
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'kiln_cleanup_audit_events', [
            'event_type' => $eventType,
            'job_id' => $jobId,
            'target_user_id' => $userId,
            'actor_user_id' => $actorUserId,
            'context' => wp_json_encode(['source' => 'administrator_confirmed_quarantine'], JSON_THROW_ON_ERROR),
            'occurred_at' => current_time('mysql', true),
        ]);
    }
}
