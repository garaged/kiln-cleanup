<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

interface PermanentDeletionGateway
{
    public function isMultisite(): bool;

    public function emailFor(int $userId): ?string;

    /** @return array<string,int> */
    public function authoredContentCounts(int $userId): array;

    public function deleteAccountAndAuthoredContent(int $userId): void;
}
