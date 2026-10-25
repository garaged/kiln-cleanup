# Threat model

## Assets

Legitimate user accounts, authored content, administrator access, cleanup-policy integrity, and audit evidence.

## Primary threats and controls

| Threat | Control |
| --- | --- |
| False-positive account removal | Quarantine first, allowlists, review samples, grace period, explicit deletion stage |
| Job replay or duplicate worker | Immutable snapshots, unique job/user key, candidate-before-cursor write order, and optimistic cursor compare-and-set |
| Privilege escalation | Administrator-only capability checks and nonce verification in every future admin action |
| Expensive query denial of service | Keyset pagination, bounded batch sizes, indexed job/candidate columns |
| Sensitive-data disclosure | Redacted audit context, no telemetry, opt-in future IP handling |
| Third-party plugin side effects | Use WordPress lifecycle APIs for destructive actions; record failures and do not silently bypass hooks |
| Quarantining the wrong account | Require a high-confidence M5 candidate, re-check the immutable policy allowlist, typed target-specific confirmation, and per-account operation |
| Stolen active session after quarantine | Destroy all WordPress session tokens and reject future authentication while the quarantine marker is active |
| Failed or unsafe restore | Preserve an exact access snapshot, restore only from an active durable record, invalidate sessions again, and audit the outcome |
| Irreversible deletion of the wrong account or content | Active quarantine only, fixed 30-day grace period, immutable-policy re-check, content-count preflight, target-specific confirmation, and no bulk/automatic path |
| Multisite network-user side effects | M8 rejects Multisite before content or account mutation |

M5 intentionally has no account mutation path. Its audit records contain IDs, state names, cursors, counts, rule codes, and policy digests only; raw email, IP, and user meta are excluded.

M6 permits only reversible account-access mutation. It has no automatic action, bulk operation, content edit, email notification, permanent deletion, or external communication path.

M8 permits one explicit permanent deletion only after the grace period. It deletes authored content with the account through WordPress lifecycle APIs and records aggregate counts only; it cannot reassign content, operate in Multisite, or run from Cron, CLI, or a queue.
