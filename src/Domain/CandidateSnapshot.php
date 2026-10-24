<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

final readonly class CandidateSnapshot
{
    /** @param list<string> $reasonCodes */
    public function __construct(
        public int $jobId,
        public int $userId,
        public int $score,
        public array $reasonCodes,
    ) {
    }
}
