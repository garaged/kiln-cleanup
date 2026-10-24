<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

interface ReviewJobCursorStore
{
    /** Advances only when the persisted cursor still equals expectedCursor. */
    public function compareAndAdvance(int $jobId, int $expectedCursor, int $nextCursor): bool;
}
