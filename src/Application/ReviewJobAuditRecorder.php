<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\JobState;

interface ReviewJobAuditRecorder
{
    /** @param array<string, mixed> $policySnapshot @param array<string, scalar|null> $filterSnapshot */
    public function recordCreated(int $jobId, int $actorUserId, array $policySnapshot, array $filterSnapshot): void;

    public function recordStateTransition(int $jobId, int $actorUserId, JobState $from, JobState $to): void;

    public function recordScanBatch(int $jobId, int $actorUserId, int $fromCursor, ?int $toCursor, int $processedCount, int $insertedCandidateCount): void;
}
