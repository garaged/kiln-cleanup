<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\JobState;

interface ReviewJobStateStore
{
    public function compareAndTransition(int $jobId, JobState $expected, JobState $next): bool;
}
