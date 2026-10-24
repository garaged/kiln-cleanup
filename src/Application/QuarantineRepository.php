<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Domain\QuarantineRecord;

interface QuarantineRepository
{
    public function get(int $userId): ?QuarantineRecord;

    /** @param array<string, bool> $originalCapabilities */
    public function create(int $userId, int $sourceJobId, array $originalCapabilities, int $originalUserLevel, int $actorUserId): QuarantineRecord;

    public function markRestored(int $userId, int $actorUserId): bool;

    /** @return list<QuarantineRecord> */
    public function listActive(int $limit): array;
}
