<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

final readonly class AuditEvent
{
    /**
     * @param array<string, scalar|null> $context Deliberately excludes raw email, IP, and user-meta values.
     */
    public function __construct(
        public string $eventType,
        public ?int $jobId,
        public ?int $targetUserId,
        public int $actorUserId,
        public array $context,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
