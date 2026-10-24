<?php

declare(strict_types=1);

namespace KilnCleanup\Domain;

final readonly class UserInventoryPage
{
    /**
     * @param list<UserInventoryRecord> $records
     */
    public function __construct(
        public array $records,
        public ?int $nextCursor,
    ) {
    }
}
