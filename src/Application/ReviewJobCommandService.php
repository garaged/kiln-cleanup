<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\JobState;
use LogicException;

final readonly class ReviewJobCommandService
{
    public function __construct(
        private ReviewJobLifecycle $lifecycle,
    ) {
    }

    public function pause(JobState $state): JobState
    {
        return $this->transition($state, JobState::Paused);
    }

    public function resume(JobState $state): JobState
    {
        return $this->transition($state, JobState::Ready);
    }

    public function cancel(JobState $state): JobState
    {
        return $this->transition($state, JobState::Cancelled);
    }

    private function transition(JobState $current, JobState $next): JobState
    {
        try {
            return $this->lifecycle->transition($current, $next);
        } catch (LogicException $error) {
            throw new LogicException('Review job command rejected: ' . $error->getMessage(), previous: $error);
        }
    }
}
