<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Application\CandidateSnapshotRepository;
use KilnCleanup\Domain\CandidateSnapshot;

final class WpdbCandidateSnapshotRepository implements CandidateSnapshotRepository
{
    public function insert(CandidateSnapshot $candidate): bool
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            'INSERT IGNORE INTO ' . $wpdb->prefix . 'kiln_cleanup_candidates (job_id, user_id, score, band, policy_version, reasons, state, created_at) VALUES (%d, %d, %d, %s, %s, %s, %s, %s)',
            $candidate->jobId,
            $candidate->userId,
            $candidate->score,
            $candidate->band,
            $candidate->policyVersion,
            wp_json_encode(['rule_codes' => $candidate->reasonCodes], JSON_THROW_ON_ERROR),
            'review',
            current_time('mysql', true),
        );

        return $wpdb->query($sql) === 1;
    }
}
