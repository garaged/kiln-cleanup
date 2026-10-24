<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

final readonly class UserInventoryRecord
{
    public function __construct(
        public int $id,
        public string $login,
        public string $email,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
