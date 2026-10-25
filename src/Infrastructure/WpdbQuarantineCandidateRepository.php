<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\QuarantineCandidateRepository;

final class WpdbQuarantineCandidateRepository implements QuarantineCandidateRepository
{
    public function eligibleCandidate(int $jobId, int $userId): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT c.job_id, c.user_id, c.band, j.policy_snapshot FROM ' . $wpdb->prefix . 'kiln_cleanup_candidates c INNER JOIN ' . $wpdb->prefix . 'kiln_cleanup_jobs j ON j.id = c.job_id WHERE c.job_id = %d AND c.user_id = %d AND j.type = %s AND j.state = %s',
            $jobId,
            $userId,
            'review',
            'completed',
        ), ARRAY_A);
        return $this->hydrate($row);
    }

    public function listEligible(int $limit): array
    {
        global $wpdb;
        $limit = min(100, max(1, $limit));
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT c.job_id, c.user_id, c.band, j.policy_snapshot, u.user_email FROM ' . $wpdb->prefix . 'kiln_cleanup_candidates c INNER JOIN ' . $wpdb->prefix . 'kiln_cleanup_jobs j ON j.id = c.job_id LEFT JOIN ' . $wpdb->users . ' u ON u.ID = c.user_id INNER JOIN (SELECT newest.user_id, MAX(newest.id) AS candidate_id FROM ' . $wpdb->prefix . 'kiln_cleanup_candidates newest INNER JOIN ' . $wpdb->prefix . 'kiln_cleanup_jobs newest_job ON newest_job.id = newest.job_id WHERE newest.band = %s AND newest_job.type = %s AND newest_job.state = %s GROUP BY newest.user_id) latest ON latest.candidate_id = c.id WHERE c.band = %s AND j.type = %s AND j.state = %s ORDER BY c.id DESC LIMIT %d',
            'eligible_for_quarantine',
            'review',
            'completed',
            'eligible_for_quarantine',
            'review',
            'completed',
            $limit,
        ), ARRAY_A);
        return array_values(array_filter(array_map(fn (mixed $row): ?array => $this->hydrate($row), $rows)));
    }

    private function hydrate(mixed $row): ?array
    {
        if (! is_array($row)) {
            return null;
        }
        $snapshot = json_decode((string) $row['policy_snapshot'], true, flags: JSON_THROW_ON_ERROR);
        return [
            'job_id' => (int) $row['job_id'],
            'user_id' => (int) $row['user_id'],
            'email' => (string) ($row['user_email'] ?? ''),
            'band' => (string) $row['band'],
            'policy_snapshot' => $snapshot['policy'],
        ];
    }
}
