<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

interface UserAccountGateway
{
    public function emailFor(int $userId): ?string;

    /** @return array{capabilities:array<string,bool>,user_level:int} */
    public function snapshotAccess(int $userId): array;

    public function removeAccessAndSessions(int $userId): void;

    /** @param array<string,bool> $capabilities */
    public function restoreAccessAndInvalidateSessions(int $userId, array $capabilities, int $userLevel): void;
}
