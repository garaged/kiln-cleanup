<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Domain\UserInventoryPage;
use OutOfRangeException;

final readonly class UserInventoryService
{
    public const MINIMUM_PAGE_SIZE = 25;
    public const MAXIMUM_PAGE_SIZE = 500;

    public function __construct(
        private UserInventoryRepository $repository,
    ) {
    }

    public function pageAfter(int $cursor, int $limit, InventoryFilter $filter): UserInventoryPage
    {
        if ($cursor < 0) {
            throw new OutOfRangeException('Cursor cannot be negative.');
        }

        if ($limit < self::MINIMUM_PAGE_SIZE || $limit > self::MAXIMUM_PAGE_SIZE) {
            throw new OutOfRangeException(sprintf(
                'Page size must be between %d and %d.',
                self::MINIMUM_PAGE_SIZE,
                self::MAXIMUM_PAGE_SIZE,
            ));
        }

        return $this->repository->pageAfter($cursor, $limit, $filter);
    }
}
