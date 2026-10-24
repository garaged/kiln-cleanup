<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Detection\DetectionEngine;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Domain\ReviewJob;
use RuntimeException;

final readonly class ReviewJobProgressService
{
    public function __construct(
        private ReviewJobScanStep $scanStep,
        private ReviewJobCursorStore $cursorStore,
        private ReviewJobStateStore $stateStore,
        private ReviewJobAuditRecorder $audit,
    ) {
    }

    /**
     * Persists candidates first; cursor movement is an optimistic final commit.
     */
    public function run(ReviewJob $job, DetectionEngine $engine, DetectionPolicy $policy, InventoryFilter $filter, int $limit): ReviewJobScanResult
    {
        $result = $this->scanStep->run($job, $engine, $policy, $filter, $limit);
        if ($result->isExhausted()) {
            if (! $this->stateStore->compareAndTransition($job->id, $job->state, \KilnCleanup\Domain\JobState::Completed)) {
                throw new RuntimeException('Review job state changed concurrently; reload before retrying.');
            }
            $this->audit->recordScanBatch($job->id, $job->createdBy, $job->cursorUserId, null, 0, 0);
            $this->audit->recordStateTransition($job->id, $job->createdBy, $job->state, \KilnCleanup\Domain\JobState::Completed);
            return $result;
        }

        if (! $this->cursorStore->compareAndAdvance($job->id, $job->cursorUserId, $result->nextCursor, $result->processedCount, $result->insertedCandidateCount)) {
            throw new RuntimeException('Review job cursor changed concurrently; candidate writes are safe to retry.');
        }

        $this->audit->recordScanBatch($job->id, $job->createdBy, $job->cursorUserId, $result->nextCursor, $result->processedCount, $result->insertedCandidateCount);

        return $result;
    }
}
