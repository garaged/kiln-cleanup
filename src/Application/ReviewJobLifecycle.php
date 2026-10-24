<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\JobState;
use LogicException;

final class ReviewJobLifecycle
{
    public function transition(JobState $current, JobState $next): JobState
    {
        if (! $current->canTransitionTo($next)) {
            throw new LogicException(sprintf('Illegal review-job transition from %s to %s.', $current->value, $next->value));
        }

        return $next;
    }

    public function requiresBoundedBatch(int $limit): void
    {
        if ($limit < 25 || $limit > 500) {
            throw new \OutOfRangeException('Review-job batch size must be between 25 and 500.');
        }
    }
}
