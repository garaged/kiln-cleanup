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
        private ReviewJobAuditRecorder $audit,
    ) {
    }

    public function cancel(int $jobId, JobState $current, int $actorUserId): JobState
    {
        return $this->transition($jobId, $current, $this->commands->cancel($current), $actorUserId);
    }

    public function prepare(int $jobId, JobState $current, int $actorUserId): JobState
    {
        return $this->transition($jobId, $current, $this->commands->prepare($current), $actorUserId);
    }

    public function pause(int $jobId, JobState $current, int $actorUserId): JobState
    {
        return $this->transition($jobId, $current, $this->commands->pause($current), $actorUserId);
    }

    public function resume(int $jobId, JobState $current, int $actorUserId): JobState
    {
        return $this->transition($jobId, $current, $this->commands->resume($current), $actorUserId);
    }

    public function start(int $jobId, JobState $current, int $actorUserId): JobState
    {
        return $this->transition($jobId, $current, $this->commands->start($current), $actorUserId);
    }

    private function transition(int $jobId, JobState $current, JobState $next, int $actorUserId): JobState
    {
        if (! $this->store->compareAndTransition($jobId, $current, $next)) {
            throw new RuntimeException('Review job state changed concurrently; reload before retrying.');
        }
        $this->audit->recordStateTransition($jobId, $actorUserId, $current, $next);
        return $next;
    }
}
