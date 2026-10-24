<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

final readonly class ReviewJobScanResult
{
    public function __construct(
        public ?int $nextCursor,
        public int $processedCount,
        public int $insertedCandidateCount,
    ) {
    }

    public function isExhausted(): bool
    {
        return $this->nextCursor === null;
    }
}
