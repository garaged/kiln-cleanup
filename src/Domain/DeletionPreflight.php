<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

final readonly class DeletionPreflight
{
    /** @param array<string,int> $contentCounts */
    public function __construct(
        public int $userId,
        public string $email,
        public int $sourceJobId,
        public \DateTimeImmutable $quarantinedAt,
        public \DateTimeImmutable $eligibleAt,
        public array $contentCounts,
    ) {
    }

    public function isEligibleAt(\DateTimeImmutable $now): bool
    {
        return $now >= $this->eligibleAt;
    }
}
