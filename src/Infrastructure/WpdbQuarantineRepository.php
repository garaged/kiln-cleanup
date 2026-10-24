<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\QuarantineRepository;
use KilnCleanup\Domain\QuarantineRecord;
use RuntimeException;

final class WpdbQuarantineRepository implements QuarantineRepository
{
    public function get(int $userId): ?QuarantineRecord
    {
        global $wpdb;
        return $this->hydrate($wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'kiln_cleanup_quarantines WHERE user_id = %d', $userId), ARRAY_A));
    }

    public function create(int $userId, int $sourceJobId, array $originalCapabilities, int $originalUserLevel, int $actorUserId): QuarantineRecord
    {
        global $wpdb;
        $inserted = $wpdb->insert($wpdb->prefix . 'kiln_cleanup_quarantines', [
            'user_id' => $userId,
            'source_job_id' => $sourceJobId,
            'state' => 'quarantined',
            'original_capabilities' => wp_json_encode($originalCapabilities, JSON_THROW_ON_ERROR),
            'original_user_level' => $originalUserLevel,
            'quarantined_by' => $actorUserId,
            'quarantined_at' => current_time('mysql', true),
        ]);
        if ($inserted !== 1) {
            throw new RuntimeException('The quarantine record could not be created.');
        }
        return $this->get($userId) ?? throw new RuntimeException('The quarantine record could not be read back.');
    }

    public function markRestored(int $userId, int $actorUserId): bool
    {
        global $wpdb;
        return $wpdb->query($wpdb->prepare(
            'UPDATE ' . $wpdb->prefix . 'kiln_cleanup_quarantines SET state = %s, restored_by = %d, restored_at = %s WHERE user_id = %d AND state = %s',
            'restored', $actorUserId, current_time('mysql', true), $userId, 'quarantined',
        )) === 1;
    }

    public function listActive(int $limit): array
    {
        global $wpdb;
        $limit = min(100, max(1, $limit));
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'kiln_cleanup_quarantines WHERE state = %s ORDER BY quarantined_at DESC LIMIT %d', 'quarantined', $limit), ARRAY_A);
        return array_values(array_filter(array_map(fn (mixed $row): ?QuarantineRecord => $this->hydrate($row), $rows)));
    }

    private function hydrate(mixed $row): ?QuarantineRecord
    {
        if (! is_array($row)) {
            return null;
        }
        return new QuarantineRecord(
            (int) $row['user_id'],
            (int) $row['source_job_id'],
            (string) $row['state'],
            json_decode((string) $row['original_capabilities'], true, flags: JSON_THROW_ON_ERROR),
            (int) $row['original_user_level'],
        );
    }
}
