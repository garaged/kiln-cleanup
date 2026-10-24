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
    ) {
    }

    /**
     * Persists candidates first; cursor movement is an optimistic final commit.
     */
    public function run(ReviewJob $job, DetectionEngine $engine, DetectionPolicy $policy, InventoryFilter $filter, int $limit): ?int
    {
        $nextCursor = $this->scanStep->run($job, $engine, $policy, $filter, $limit);
        if ($nextCursor === null) {
            return null;
        }

        if (! $this->cursorStore->compareAndAdvance($job->id, $job->cursorUserId, $nextCursor)) {
            throw new RuntimeException('Review job cursor changed concurrently; candidate writes are safe to retry.');
        }

        return $nextCursor;
    }
}
