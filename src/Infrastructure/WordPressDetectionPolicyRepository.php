<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Detection\DetectionPolicy;

final class WordPressDetectionPolicyRepository
{
    private const OPTION = 'kiln_cleanup_detection_policy';

    public function load(): DetectionPolicy
    {
        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return new DetectionPolicy(
            'm3-v1',
            array_values(array_map('absint', $stored['protected_user_ids'] ?? [])),
            array_values(array_map('strval', $stored['protected_emails'] ?? [])),
            array_values(array_map('strval', $stored['blocked_email_domains'] ?? [])),
        );
    }

    public function save(DetectionPolicy $policy): void
    {
        update_option(self::OPTION, [
            'protected_user_ids' => $policy->protectedUserIds,
            'protected_emails' => $policy->protectedEmails,
            'blocked_email_domains' => $policy->blockedEmailDomains,
        ], false);
    }
}
