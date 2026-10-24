<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use KilnCleanup\Application\CandidateSnapshotRepository;
use KilnCleanup\Application\ReviewJobAuditRecorder;
use KilnCleanup\Application\ReviewJobCursorStore;
use KilnCleanup\Application\ReviewJobLifecycle;
use KilnCleanup\Application\ReviewJobProgressService;
use KilnCleanup\Application\ReviewJobScanStep;
use KilnCleanup\Application\ReviewJobStateStore;
use KilnCleanup\Application\UserInventoryRepository;
use KilnCleanup\Detection\DetectionEngine;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\CandidateSnapshot;
use KilnCleanup\Domain\InventoryFilter;
use KilnCleanup\Domain\JobState;
use KilnCleanup\Domain\ReviewJob;
use KilnCleanup\Domain\UserInventoryPage;
use KilnCleanup\Domain\UserInventoryRecord;
use PHPUnit\Framework\TestCase;

final class ReviewJobProgressServiceTest extends TestCase
{
    public function test_scan_persists_candidates_before_a_counted_cursor_commit(): void
    {
        $candidates = new class implements CandidateSnapshotRepository {
            public array $keys = [];
            public function insert(CandidateSnapshot $candidate): bool { $key = $candidate->jobId . ':' . $candidate->userId; if (isset($this->keys[$key])) return false; $this->keys[$key] = true; return true; }
        };
        $cursor = new class implements ReviewJobCursorStore {
            public array $calls = [];
            public function compareAndAdvance(int $jobId, int $expectedCursor, int $nextCursor, int $processedCount, int $insertedCandidateCount): bool { $this->calls[] = [$jobId, $expectedCursor, $nextCursor, $processedCount, $insertedCandidateCount]; return true; }
        };
        $audit = new class implements ReviewJobAuditRecorder {
            public array $batches = [];
            public function recordCreated(int $jobId, int $actorUserId, array $policySnapshot, array $filterSnapshot): void {}
            public function recordStateTransition(int $jobId, int $actorUserId, JobState $from, JobState $to): void {}
            public function recordScanBatch(int $jobId, int $actorUserId, int $fromCursor, ?int $toCursor, int $processedCount, int $insertedCandidateCount): void { $this->batches[] = [$fromCursor, $toCursor, $processedCount, $insertedCandidateCount]; }
        };
        $inventory = new class implements UserInventoryRepository {
            public function pageAfter(int $cursor, int $limit, InventoryFilter $filter): UserInventoryPage { return new UserInventoryPage([new UserInventoryRecord(11, 'abc123', 'a@bots.example', new DateTimeImmutable('2025-01-01', new DateTimeZone('UTC'))), new UserInventoryRecord(12, 'user', 'b@example.test', new DateTimeImmutable('2025-01-02', new DateTimeZone('UTC')))], null); }
        };
        $state = new class implements ReviewJobStateStore { public function compareAndTransition(int $jobId, JobState $expected, JobState $next): bool { return true; } };
        $service = new ReviewJobProgressService(new ReviewJobScanStep($inventory, $candidates, new ReviewJobLifecycle()), $cursor, $state, $audit);
        $job = new ReviewJob(4, JobState::Running, [], [], 10, 3);

        $result = $service->run($job, new DetectionEngine(), new DetectionPolicy('test'), new InventoryFilter(), 25);

        self::assertSame(12, $result->nextCursor);
        self::assertSame(2, $result->processedCount);
        self::assertSame(2, $result->insertedCandidateCount);
        self::assertSame([[4, 10, 12, 2, 2]], $cursor->calls);
        self::assertSame([[10, 12, 2, 2]], $audit->batches);
    }
}
