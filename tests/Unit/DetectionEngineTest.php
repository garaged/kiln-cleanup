<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use KilnCleanup\Detection\DetectionEngine;
use KilnCleanup\Detection\DetectionPolicy;
use KilnCleanup\Detection\ScoreBand;
use KilnCleanup\Domain\UserInventoryRecord;
use PHPUnit\Framework\TestCase;

final class DetectionEngineTest extends TestCase
{
    public function test_allowlist_has_precedence_over_every_rule(): void
    {
        $record = $this->record(7, 'a1b2c3d4e5f6g7h8i9j0', 'bot@blocked.example');
        $result = (new DetectionEngine())->evaluate($record, new DetectionPolicy(
            'm2-v1',
            protectedUserIds: [7],
            blockedEmailDomains: ['blocked.example'],
        ));

        self::assertTrue($result->allowlisted);
        self::assertSame(0, $result->score);
        self::assertSame([], $result->reasons);
    }

    public function test_exact_blocked_domain_is_high_confidence_but_not_an_action(): void
    {
        $result = (new DetectionEngine())->evaluate(
            $this->record(8, 'ordinaryuser', 'person@blocked.example'),
            new DetectionPolicy('m2-v1', blockedEmailDomains: ['blocked.example']),
        );

        self::assertSame(75, $result->score);
        self::assertSame(ScoreBand::EligibleForQuarantine, $result->band);
        self::assertFalse($result->allowlisted);
        self::assertSame('blocked_email_domain', $result->reasons[0]->ruleId);
    }

    public function test_generated_patterns_are_review_only(): void
    {
        $result = (new DetectionEngine())->evaluate(
            $this->record(8, 'a1b2c3d4e5f6g7h8i9j0', 'ordinary@example.com'),
            new DetectionPolicy('m2-v1'),
        );

        self::assertSame(30, $result->score);
        self::assertSame(ScoreBand::Review, $result->band);
    }

    private function record(int $id, string $login, string $email): UserInventoryRecord
    {
        return new UserInventoryRecord($id, $login, $email, new \DateTimeImmutable('2026-10-24T00:00:00Z'));
    }
}
