<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\ReviewJob;

interface ReviewJobRepository
{
    /** @param array<string, mixed> $policySnapshot @param array<string, scalar|null> $filterSnapshot */
    public function create(array $policySnapshot, array $filterSnapshot, int $actorUserId): ReviewJob;

    public function get(int $jobId): ?ReviewJob;
}
