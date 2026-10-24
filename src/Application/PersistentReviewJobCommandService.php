<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\JobState;
use RuntimeException;

final readonly class PersistentReviewJobCommandService
{
    public function __construct(
        private ReviewJobCommandService $commands,
        private ReviewJobStateStore $store,
    ) {
    }

    public function cancel(int $jobId, JobState $current): JobState
    {
        $next = $this->commands->cancel($current);
        if (! $this->store->compareAndTransition($jobId, $current, $next)) {
            throw new RuntimeException('Review job state changed concurrently; reload before retrying.');
        }
        return $next;
    }
}
