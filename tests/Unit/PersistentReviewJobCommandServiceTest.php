<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Application\PersistentReviewJobCommandService;
use KilnCleanup\Application\ReviewJobAuditRecorder;
use KilnCleanup\Application\ReviewJobCommandService;
use KilnCleanup\Application\ReviewJobLifecycle;
use KilnCleanup\Application\ReviewJobStateStore;
use KilnCleanup\Domain\JobState;
use PHPUnit\Framework\TestCase;

final class PersistentReviewJobCommandServiceTest extends TestCase
{
    public function test_transition_is_persisted_before_a_redacted_audit_receipt(): void
    {
        $events = [];
        $store = new class implements ReviewJobStateStore {
            public array $calls = [];
            public function compareAndTransition(int $jobId, JobState $expected, JobState $next): bool { $this->calls[] = [$jobId, $expected, $next]; return true; }
        };
        $audit = new class($events) implements ReviewJobAuditRecorder {
            public array $events = [];
            public function __construct(array $events) { $this->events = $events; }
            public function recordCreated(int $jobId, int $actorUserId, array $policySnapshot, array $filterSnapshot): void {}
            public function recordStateTransition(int $jobId, int $actorUserId, JobState $from, JobState $to): void { $this->events[] = [$jobId, $actorUserId, $from, $to]; }
            public function recordScanBatch(int $jobId, int $actorUserId, int $fromCursor, ?int $toCursor, int $processedCount, int $insertedCandidateCount): void {}
        };
        $service = new PersistentReviewJobCommandService(new ReviewJobCommandService(new ReviewJobLifecycle()), $store, $audit);

        self::assertSame(JobState::Ready, $service->prepare(8, JobState::Draft, 4));
        self::assertSame([[8, JobState::Draft, JobState::Ready]], $store->calls);
        self::assertSame([[8, 4, JobState::Draft, JobState::Ready]], $audit->events);
    }
}
