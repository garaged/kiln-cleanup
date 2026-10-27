<?php

declare(strict_types=1);

namespace KilnCleanup\Infrastructure;

use KilnCleanup\Detection\DetectionPolicy;

final class AuditRecorder
{
    public function recordPolicySaved(DetectionPolicy $policy, int $actorUserId): void
    {
        global $wpdb;

        $context = wp_json_encode([
            'policy_version' => $policy->version,
            'protected_user_count' => count($policy->protectedUserIds),
            'protected_email_count' => count($policy->protectedEmails),
            'blocked_domain_count' => count($policy->blockedEmailDomains),
            'policy_digest' => hash('sha256', wp_json_encode([
                $policy->protectedUserIds,
                array_map('strtolower', $policy->protectedEmails),
                array_map('strtolower', $policy->blockedEmailDomains),
            ])),
        ], JSON_THROW_ON_ERROR);

        $wpdb->insert($wpdb->prefix . 'kiln_cleanup_audit_events', [
            'event_type' => 'detection_policy_saved',
            'job_id' => null,
            'target_user_id' => null,
            'actor_user_id' => $actorUserId,
            'context' => $context,
            'occurred_at' => current_time('mysql', true),
        ]);
    }

    public function recordRegistrationLockdown(int $actorUserId): void
    {
        $this->record('public_registration_disabled', null, $actorUserId, ['source' => 'administrator_confirmed_lockdown']);
    }

    public function recordRegistrationEvidenceSetting(bool $enabled, int $actorUserId): void
    {
        $this->record('registration_evidence_setting_changed', null, $actorUserId, ['enabled' => $enabled]);
    }

    public function recordRegistrationObserved(int $userId): void
    {
        $this->record('registration_observed', $userId, 0, ['source' => 'wordpress_user_register', 'network_identifiers_retained' => false]);
    }

    /** @param array<string,scalar|null> $context */
    private function record(string $eventType, ?int $targetUserId, int $actorUserId, array $context): void
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'kiln_cleanup_audit_events', [
            'event_type' => $eventType,
            'job_id' => null,
            'target_user_id' => $targetUserId,
            'actor_user_id' => $actorUserId,
            'context' => wp_json_encode($context, JSON_THROW_ON_ERROR),
            'occurred_at' => current_time('mysql', true),
        ]);
    }
}
