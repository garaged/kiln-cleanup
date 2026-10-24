<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Application\ReviewJobLifecycle;
use KilnCleanup\Domain\JobState;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ReviewJobLifecycleTest extends TestCase
{
    public function test_cannot_restart_terminal_review_job(): void
    {
        $this->expectException(LogicException::class);
        (new ReviewJobLifecycle())->transition(JobState::Completed, JobState::Running);
    }

    public function test_rejects_unbounded_batch(): void
    {
        $this->expectException(\OutOfRangeException::class);
        (new ReviewJobLifecycle())->requiresBoundedBatch(501);
    }
}
