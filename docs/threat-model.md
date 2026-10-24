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

M5 intentionally has no account mutation path. Its audit records contain IDs, state names, cursors, counts, rule codes, and policy digests only; raw email, IP, and user meta are excluded.
