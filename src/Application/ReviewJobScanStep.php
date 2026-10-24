<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Detection\DetectionEngine;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\CandidateSnapshot;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Domain\ReviewJob;

final readonly class ReviewJobScanStep
{
    public function __construct(
        private UserInventoryRepository $inventory,
        private CandidateSnapshotRepository $candidates,
        private ReviewJobLifecycle $lifecycle,
    ) {
    }

    /** Returns the next cursor only after every candidate write succeeds. */
    public function run(ReviewJob $job, DetectionEngine $engine, DetectionPolicy $policy, InventoryFilter $filter, int $limit): ?int
    {
        $this->lifecycle->requiresBoundedBatch($limit);
        $page = $this->inventory->pageAfter($job->cursorUserId, $limit, $filter);

        foreach ($page->records as $record) {
            $result = $engine->evaluate($record, $policy);
            $this->candidates->insert(new CandidateSnapshot(
                $job->id,
                $record->id,
                $result->score,
                array_map(static fn ($reason): string => $reason->ruleId, $result->reasons),
            ));
        }

        return $page->records === [] ? null : $page->records[array_key_last($page->records)]->id;
    }
}
