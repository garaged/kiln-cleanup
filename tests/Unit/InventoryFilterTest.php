<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use InvalidArgumentException;
use KilnCleanup\Domain\InventoryFilter;
use PHPUnit\Framework\TestCase;

final class InventoryFilterTest extends TestCase
{
    public function test_accepts_exact_iso_date(): void
    {
        $filter = InventoryFilter::fromRegisteredBeforeInput('2026-10-24');

        self::assertSame('2026-10-24 00:00:00', $filter->registeredBefore?->format('Y-m-d H:i:s'));
    }

    public function test_rejects_ambiguous_or_invalid_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InventoryFilter::fromRegisteredBeforeInput('10/24/2026');
    }
}
