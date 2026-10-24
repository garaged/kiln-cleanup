<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Application\UserInventoryRepository;
use KilnCleanup\Application\UserInventoryService;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Domain\UserInventoryPage;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

final class UserInventoryServiceTest extends TestCase
{
    public function test_rejects_unbounded_page_size(): void
    {
        $service = new UserInventoryService(new class implements UserInventoryRepository {
            public function pageAfter(int $cursor, int $limit, InventoryFilter $filter): UserInventoryPage
            {
                throw new \LogicException('The repository must not be called.');
            }
        });

        $this->expectException(OutOfRangeException::class);
        $service->pageAfter(0, 501, new InventoryFilter());
    }
}
