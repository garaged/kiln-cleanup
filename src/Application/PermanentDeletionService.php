<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\DeletionPreflight;
use RuntimeException;

final readonly class PermanentDeletionService
{
    private const GRACE_DAYS = 30;

    public function __construct(
        private QuarantineRepository $quarantines,
        private QuarantineCandidateRepository $candidates,
        private PermanentDeletionGateway $accounts,
        private QuarantineAuditRecorder $audit,
    ) {
    }

    public function preflight(int $userId): DeletionPreflight
    {
        if ($this->accounts->isMultisite()) {
            throw new RuntimeException('Permanent deletion is unavailable on WordPress Multisite.');
        }
        $record = $this->quarantines->get($userId);
        if ($record === null || ! $record->isActive()) {
            throw new RuntimeException('Only an actively quarantined account can be permanently deleted.');
        }
        $email = $this->accounts->emailFor($userId);
        if ($email === null) {
            throw new RuntimeException('The quarantined account no longer exists.');
        }
        $candidate = $this->candidates->eligibleCandidate($record->sourceJobId, $userId);
        if ($candidate === null || $candidate['band'] !== 'eligible_for_quarantine') {
            throw new RuntimeException('The source review candidate is no longer valid for deletion.');
        }
        $policy = (new ReviewJobSnapshotCodec())->policyFromSnapshot($candidate['policy_snapshot']);
        if ($policy->protects($userId, $email)) {
            throw new RuntimeException('Protected accounts cannot be permanently deleted.');
        }
        $eligibleAt = $record->quarantinedAt->modify('+' . self::GRACE_DAYS . ' days');
        return new DeletionPreflight($userId, $email, $record->sourceJobId, $record->quarantinedAt, $eligibleAt, $this->accounts->authoredContentCounts($userId));
    }

    public function delete(int $userId, int $actorUserId): void
    {
        $preflight = $this->preflight($userId);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if (! $preflight->isEligibleAt($now)) {
            throw new RuntimeException('The 30-day quarantine grace period has not elapsed.');
        }
        $this->accounts->deleteAccountAndAuthoredContent($userId);
        $this->audit->recordDeleted($preflight->sourceJobId, $userId, $actorUserId, $preflight->contentCounts);
    }
}
