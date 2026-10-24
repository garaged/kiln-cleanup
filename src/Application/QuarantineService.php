<?php

declare(strict_types=1);

namespace KilnCleanup\Application;

use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Domain\QuarantineRecord;
use RuntimeException;

final readonly class QuarantineService
{
    public function __construct(
        private QuarantineCandidateRepository $candidates,
        private QuarantineRepository $quarantines,
        private UserAccountGateway $accounts,
        private QuarantineAuditRecorder $audit,
    ) {
    }

    public function quarantine(int $jobId, int $userId, DetectionPolicy $policy, int $actorUserId): QuarantineRecord
    {
        $candidate = $this->candidates->eligibleCandidate($jobId, $userId);
        if ($candidate === null || $candidate['band'] !== 'eligible_for_quarantine') {
            throw new RuntimeException('Only a high-confidence candidate from this review job can be quarantined.');
        }
        if ($this->quarantines->get($userId)?->isActive()) {
            throw new RuntimeException('This account is already quarantined.');
        }
        $email = $this->accounts->emailFor($userId);
        if ($email === null) {
            throw new RuntimeException('The candidate account no longer exists.');
        }
        if ($policy->protects($userId, $email)) {
            throw new RuntimeException('Protected accounts cannot be quarantined.');
        }

        $access = $this->accounts->snapshotAccess($userId);
        $record = $this->quarantines->create($userId, $jobId, $access['capabilities'], $access['user_level'], $actorUserId);
        try {
            $this->accounts->removeAccessAndSessions($userId);
            $this->audit->recordQuarantined($jobId, $userId, $actorUserId);
        } catch (\Throwable $error) {
            // The durable record intentionally remains: an administrator must restore only after verifying account state.
            throw new RuntimeException('Quarantine did not complete safely: ' . $error->getMessage(), previous: $error);
        }
        return $record;
    }

    public function restore(int $userId, int $actorUserId): void
    {
        $record = $this->quarantines->get($userId);
        if ($record === null || ! $record->isActive()) {
            throw new RuntimeException('This account is not actively quarantined.');
        }
        $this->accounts->restoreAccessAndInvalidateSessions($userId, $record->originalCapabilities, $record->originalUserLevel);
        if (! $this->quarantines->markRestored($userId, $actorUserId)) {
            throw new RuntimeException('Restore did not persist safely.');
        }
        $this->audit->recordRestored($record->sourceJobId, $userId, $actorUserId);
    }
}
