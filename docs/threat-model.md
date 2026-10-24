# Threat model

## Assets

Legitimate user accounts, authored content, administrator access, cleanup-policy integrity, and audit evidence.

## Primary threats and controls

| Threat | Control |
| --- | --- |
| False-positive account removal | Quarantine first, allowlists, review samples, grace period, explicit deletion stage |
| Job replay or duplicate worker | Durable state machine, idempotent target state, unique job/user key |
| Privilege escalation | Administrator-only capability checks and nonce verification in every future admin action |
| Expensive query denial of service | Keyset pagination, bounded batch sizes, indexed job/candidate columns |
| Sensitive-data disclosure | Redacted audit context, no telemetry, opt-in future IP handling |
| Third-party plugin side effects | Use WordPress lifecycle APIs for destructive actions; record failures and do not silently bypass hooks |

M0 intentionally has no account mutation path.
