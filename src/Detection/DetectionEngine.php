<?php

declare(strict_types=1);

namespace KilnCleanup\Detection;

use KilnCleanup\Domain\UserInventoryRecord;

final class DetectionEngine
{
    public function evaluate(UserInventoryRecord $record, DetectionPolicy $policy): DetectionResult
    {
        if ($policy->protects($record->id, $record->email)) {
            return DetectionResult::allowlisted($policy->version);
        }

        $reasons = [];

        if ($policy->blocksDomainFor($record->email)) {
            $reasons[] = new DetectionReason(
                'blocked_email_domain',
                75,
                'The email domain exactly matches an operator-maintained blocked-domain policy.',
            );
        }

        if ($this->looksGenerated($record->login)) {
            $reasons[] = new DetectionReason(
                'generated_login_pattern',
                30,
                'The login is a long mixed alphanumeric token without word separators.',
            );
        }

        $emailLocalPart = (string) strstr($record->email, '@', true);
        if ($this->looksGenerated($emailLocalPart)) {
            $reasons[] = new DetectionReason(
                'generated_email_local_part',
                20,
                'The email local-part is a long mixed alphanumeric token without word separators.',
            );
        }

        $score = min(100, array_sum(array_map(
            static fn (DetectionReason $reason): int => $reason->points,
            $reasons,
        )));

        return new DetectionResult($score, ScoreBand::fromScore($score), $reasons, false, $policy->version);
    }

    private function looksGenerated(string $value): bool
    {
        return preg_match('/\A(?=.*[a-z])(?=.*\d)[a-z\d]{20,}\z/i', $value) === 1;
    }
}
