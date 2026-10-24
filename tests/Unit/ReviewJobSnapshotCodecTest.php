<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Application\ReviewJobSnapshotCodec;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\InventoryFilter;
use PHPUnit\Framework\TestCase;

final class ReviewJobSnapshotCodecTest extends TestCase
{
    public function test_snapshot_round_trip_is_independent_of_later_policy_changes(): void
    {
        $codec = new ReviewJobSnapshotCodec();
        $snapshot = $codec->policySnapshot(new DetectionPolicy('m3-v1', [7], ['safe@example.test'], ['bots.example']));
        $filter = $codec->filterSnapshot(InventoryFilter::fromRegisteredBeforeInput('2026-01-01'));

        $restored = $codec->policyFromSnapshot($snapshot);
        self::assertSame([7], $restored->protectedUserIds);
        self::assertSame(['bots.example'], $restored->blockedEmailDomains);
        self::assertSame('2026-01-01', $codec->filterFromSnapshot($filter)->registeredBefore?->format('Y-m-d'));

        $snapshot['protected_user_ids'][] = 99;
        self::assertSame([7], $restored->protectedUserIds);
    }
}
