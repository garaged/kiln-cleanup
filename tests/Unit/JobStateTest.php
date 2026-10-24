<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Domain\JobState;
use PHPUnit\Framework\TestCase;

final class JobStateTest extends TestCase
{
    public function test_completed_job_cannot_be_restarted(): void
    {
        self::assertFalse(JobState::Completed->canTransitionTo(JobState::Running));
    }

    public function test_paused_job_must_return_to_ready_before_running(): void
    {
        self::assertTrue(JobState::Paused->canTransitionTo(JobState::Ready));
        self::assertFalse(JobState::Paused->canTransitionTo(JobState::Running));
    }

    public function test_running_job_can_be_paused_or_completed(): void
    {
        self::assertTrue(JobState::Running->canTransitionTo(JobState::Paused));
        self::assertTrue(JobState::Running->canTransitionTo(JobState::Completed));
    }
}
