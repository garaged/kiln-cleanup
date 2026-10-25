<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

interface QuarantineAuditRecorder
{
    public function recordQuarantined(int $sourceJobId, int $userId, int $actorUserId): void;

    public function recordRestored(int $sourceJobId, int $userId, int $actorUserId): void;

    /** @param array<string,int> $contentCounts */
    public function recordDeleted(int $sourceJobId, int $userId, int $actorUserId, array $contentCounts): void;
}
