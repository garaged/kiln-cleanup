<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\ReviewJob;

interface ReviewJobClaimStore
{
    public function claimNext(string $token, int $leaseSeconds, ?int $jobId = null): ?ReviewJob;

    public function release(int $jobId, string $token): void;

    public function fail(int $jobId, string $token, string $message): void;
}
