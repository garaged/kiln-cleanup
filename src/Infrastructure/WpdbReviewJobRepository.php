<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\ReviewJobRepository;
use KilnCleanup\Domain\JobState;
use KilnCleanup\Domain\ReviewJob;

final class WpdbReviewJobRepository implements ReviewJobRepository
{
    public function create(array $policySnapshot, array $filterSnapshot, int $actorUserId): ReviewJob
    {
        global $wpdb;

        $wpdb->insert($wpdb->prefix . 'kiln_cleanup_jobs', [
            'type' => 'review',
            'state' => JobState::Draft->value,
            'policy_snapshot' => wp_json_encode(['policy' => $policySnapshot, 'filter' => $filterSnapshot], JSON_THROW_ON_ERROR),
            'created_by' => $actorUserId,
            'created_at' => current_time('mysql', true),
        ]);

        return $this->get((int) $wpdb->insert_id) ?? throw new \RuntimeException('Review job creation did not persist.');
    }

    public function get(int $jobId): ?ReviewJob
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'kiln_cleanup_jobs WHERE id = %d AND type = %s', $jobId, 'review'), ARRAY_A);
        return $this->hydrate($row);
    }

    public function listRecent(int $limit = 50): array
    {
        global $wpdb;
        $limit = min(100, max(1, $limit));
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'kiln_cleanup_jobs WHERE type = %s ORDER BY id DESC LIMIT %d', 'review', $limit), ARRAY_A);
        return array_values(array_filter(array_map(fn (array $row): ?ReviewJob => $this->hydrate($row), $rows)));
    }

    private function hydrate(mixed $row): ?ReviewJob
    {
        if (! is_array($row)) {
            return null;
        }
        $snapshot = json_decode((string) $row['policy_snapshot'], true, flags: JSON_THROW_ON_ERROR);
        return new ReviewJob(
            (int) $row['id'],
            JobState::from((string) $row['state']),
            $snapshot['policy'],
            $snapshot['filter'],
            (int) $row['cursor_user_id'],
            (int) $row['created_by'],
            (int) $row['processed_candidates'],
            $row['last_error'] === null ? null : (string) $row['last_error'],
        );
    }
}
