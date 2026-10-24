<?php

declare(strict_types=1);

namespace KilnCleanup\Tests\Unit;

use InvalidArgumentException;
use KilnCleanup\Detection\DetectionPolicy;
use PHPUnit\Framework\TestCase;

final class DetectionPolicyTest extends TestCase
{
    public function test_rejects_a_blocked_domain_with_an_at_sign(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DetectionPolicy('m3-v1', blockedEmailDomains: ['@blocked.example']);
    }

    public function test_matches_email_and_domain_case_insensitively(): void
    {
        $policy = new DetectionPolicy('m3-v1', protectedEmails: ['Owner@Example.com'], blockedEmailDomains: ['Blocked.Example']);

        self::assertTrue($policy->protects(9, 'owner@example.COM'));
        self::assertTrue($policy->blocksDomainFor('bot@blocked.example'));
    }
}
