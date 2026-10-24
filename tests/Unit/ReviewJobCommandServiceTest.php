<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Application\ReviewJobCommandService;
use KilnCleanup\Application\ReviewJobLifecycle;
use KilnCleanup\Domain\JobState;
use PHPUnit\Framework\TestCase;

final class ReviewJobCommandServiceTest extends TestCase
{
    public function test_pause_resume_and_cancel_follow_job_state_machine(): void
    {
        $commands = new ReviewJobCommandService(new ReviewJobLifecycle());

        self::assertSame(JobState::Paused, $commands->pause(JobState::Running));
        self::assertSame(JobState::Ready, $commands->resume(JobState::Paused));
        self::assertSame(JobState::Cancelled, $commands->cancel(JobState::Ready));
    }
}
