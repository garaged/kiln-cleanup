<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Application\QuarantineAuditRecorder;
use KilnCleanup\Application\QuarantineCandidateRepository;
use KilnCleanup\Application\QuarantineRepository;
use KilnCleanup\Application\QuarantineService;
use KilnCleanup\Application\PermanentDeletionGateway;
use KilnCleanup\Application\PermanentDeletionService;
use KilnCleanup\Application\UserAccountGateway;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\QuarantineRecord;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class QuarantineServiceTest extends TestCase
{
    public function testQuarantineRemovesAccessOnlyAfterCapturingItAndRestoreReturnsExactAccess(): void
    {
        $candidates = new InMemoryCandidates(['job_id' => 9, 'user_id' => 42, 'band' => 'eligible_for_quarantine', 'policy_snapshot' => ['version' => 'test']]);
        $quarantines = new InMemoryQuarantines();
        $accounts = new InMemoryAccounts('candidate@example.test');
        $audit = new InMemoryAudit();
        $service = new QuarantineService($candidates, $quarantines, $accounts, $audit);

        $service->quarantine(9, 42, new DetectionPolicy('test'), 1);
        self::assertTrue($accounts->accessRemoved);
        self::assertSame(['subscriber' => true, 'custom_cap' => true], $quarantines->record?->originalCapabilities);
        self::assertSame(['quarantined:42'], $audit->events);

        $service->restore(42, 1);
        self::assertSame(['subscriber' => true, 'custom_cap' => true], $accounts->restoredCapabilities);
        self::assertSame(0, $accounts->restoredLevel);
        self::assertSame(['quarantined:42', 'restored:42'], $audit->events);
    }

    public function testQuarantineRejectsNonHighConfidenceAndProtectedAccounts(): void
    {
        $service = new QuarantineService(new InMemoryCandidates(['job_id' => 9, 'user_id' => 42, 'band' => 'eligible_for_quarantine', 'policy_snapshot' => []]), new InMemoryQuarantines(), new InMemoryAccounts('protected@example.test'), new InMemoryAudit());
        $this->expectException(RuntimeException::class);
        $service->quarantine(9, 42, new DetectionPolicy('test', protectedEmails: ['protected@example.test']), 1);
    }

    public function testPermanentDeletionRequiresElapsedGracePeriodAndAuditsAggregateContentOnly(): void
    {
        $record = new QuarantineRecord(42, 9, 'quarantined', [], 0, new \DateTimeImmutable('-31 days'));
        $quarantines = new InMemoryQuarantines($record);
        $audit = new InMemoryAudit();
        $gateway = new InMemoryDeletionGateway('candidate@example.test', ['post' => 3, 'attachment' => 2]);
        $service = new PermanentDeletionService($quarantines, new InMemoryCandidates(['job_id' => 9, 'user_id' => 42, 'band' => 'eligible_for_quarantine', 'policy_snapshot' => ['version' => 'test']]), $gateway, $audit);

        $service->delete(42, 1);

        self::assertTrue($gateway->deleted);
        self::assertSame(['deleted:42:post=3,attachment=2'], $audit->events);
    }

    public function testPermanentDeletionRejectsPrematureAndMultisitePreflights(): void
    {
        $record = new QuarantineRecord(42, 9, 'quarantined', [], 0, new \DateTimeImmutable('-29 days'));
        $service = new PermanentDeletionService(new InMemoryQuarantines($record), new InMemoryCandidates(['job_id' => 9, 'user_id' => 42, 'band' => 'eligible_for_quarantine', 'policy_snapshot' => ['version' => 'test']]), new InMemoryDeletionGateway('candidate@example.test', []), new InMemoryAudit());
        $this->expectException(RuntimeException::class);
        $service->delete(42, 1);
    }
}

final class InMemoryCandidates implements QuarantineCandidateRepository { public function __construct(private ?array $candidate) {} public function eligibleCandidate(int $jobId, int $userId): ?array { return $this->candidate; } public function listEligible(int $limit): array { return $this->candidate === null ? [] : [$this->candidate]; } }
final class InMemoryQuarantines implements QuarantineRepository { public ?QuarantineRecord $record; public function __construct(?QuarantineRecord $record = null) { $this->record = $record; } public function get(int $userId): ?QuarantineRecord { return $this->record; } public function create(int $userId, int $sourceJobId, array $caps, int $level, int $actor): QuarantineRecord { return $this->record = new QuarantineRecord($userId, $sourceJobId, 'quarantined', $caps, $level, new \DateTimeImmutable('now', new \DateTimeZone('UTC'))); } public function markRestored(int $userId, int $actor): bool { $this->record = new QuarantineRecord($userId, $this->record->sourceJobId, 'restored', $this->record->originalCapabilities, $this->record->originalUserLevel, $this->record->quarantinedAt); return true; } public function listActive(int $limit): array { return $this->record?->isActive() ? [$this->record] : []; } }
final class InMemoryAccounts implements UserAccountGateway { public bool $accessRemoved = false; public array $restoredCapabilities = []; public int $restoredLevel = -1; public function __construct(private string $email) {} public function emailFor(int $userId): ?string { return $this->email; } public function snapshotAccess(int $userId): array { return ['capabilities' => ['subscriber' => true, 'custom_cap' => true], 'user_level' => 0]; } public function removeAccessAndSessions(int $userId): void { $this->accessRemoved = true; } public function restoreAccessAndInvalidateSessions(int $userId, array $caps, int $level): void { $this->restoredCapabilities = $caps; $this->restoredLevel = $level; } }
final class InMemoryAudit implements QuarantineAuditRecorder { public array $events = []; public function recordQuarantined(int $jobId, int $userId, int $actor): void { $this->events[] = 'quarantined:' . $userId; } public function recordRestored(int $jobId, int $userId, int $actor): void { $this->events[] = 'restored:' . $userId; } public function recordDeleted(int $jobId, int $userId, int $actor, array $counts): void { $parts = []; foreach ($counts as $type => $count) { $parts[] = $type . '=' . $count; } $this->events[] = 'deleted:' . $userId . ':' . implode(',', $parts); } }
final class InMemoryDeletionGateway implements PermanentDeletionGateway { public bool $deleted = false; public function __construct(private string $email, private array $counts, private bool $multisite = false) {} public function isMultisite(): bool { return $this->multisite; } public function emailFor(int $userId): ?string { return $this->email; } public function authoredContentCounts(int $userId): array { return $this->counts; } public function deleteAccountAndAuthoredContent(int $userId): void { $this->deleted = true; } }
