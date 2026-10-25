<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

final readonly class QuarantineRecord
{
    /** @param array<string, bool> $originalCapabilities */
    public function __construct(
        public int $userId,
        public int $sourceJobId,
        public string $state,
        public array $originalCapabilities,
        public int $originalUserLevel,
        public \DateTimeImmutable $quarantinedAt,
    ) {
    }

    public function isActive(): bool
    {
        return $this->state === 'quarantined';
    }
}
