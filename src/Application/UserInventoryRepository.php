<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Domain\UserInventoryPage;

interface UserInventoryRepository
{
    public function pageAfter(int $cursor, int $limit, InventoryFilter $filter): UserInventoryPage;
}
