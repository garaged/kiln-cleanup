<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

final readonly class ReviewJob
{
    /** @param array<string, mixed> $policySnapshot @param array<string, scalar|null> $filterSnapshot */
    public function __construct(
        public int $id,
        public JobState $state,
        public array $policySnapshot,
        public array $filterSnapshot,
        public int $cursorUserId,
        public int $createdBy,
        public int $processedCandidates = 0,
        public ?string $lastError = null,
    ) {
    }
}
