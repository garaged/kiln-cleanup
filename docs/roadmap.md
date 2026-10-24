# Kiln Cleanup Delivery Roadmap

This roadmap is the sequencing authority for Kiln Cleanup. Each milestone gets one branch and draft PR, its own OpenSpec, focused tests, documentation updates, hosted CI, WordPress/container acceptance where applicable, and explicit merge approval.

## Non-negotiable product rules

- Detection is explainable, local-first, and never a bot verdict.
- Allowlists override every detector.
- Low and medium confidence can never produce a destructive action.
- Quarantine is the first account action; deletion is separate, explicit, and delayed.
- No relay, telemetry, credentials, external reputation service, or ML is required.
- Each operation records redacted audit evidence.
- Large work is cursor-based, bounded, resumable, and idempotent.

## Milestone map

| Milestone | Status | Outcome | Explicit non-goal |
| --- | --- | --- | --- |
| M0 | Merged | Safety foundation, schema, audit model, CI | Any user action |
| M1 | Merged | Read-only cursor user inventory | Scoring or cleanup |
| M2 | Merged | Explainable local detection engine | Persisted policy or action |
| M3 | Merged | Local administrator policy management | Account mutation |
| M4 | Merged | Read-only candidate review view | Candidate persistence or action |
| M5 | Merged | Durable candidate snapshots and review jobs | Quarantine |
| M6 | Merged | Reversible quarantine and restore | Permanent deletion |
| M7 | In progress | Resumable background review execution and CLI | Bulk quarantine or deletion |
| M8 | Planned | Explicit permanent deletion after grace period | Automatic deletion |
| M9 | Planned | Prevention controls and operational hardening | Third-party dependency |

## Definition of done for every milestone

The operational execution protocol in [AGENTS.md](../AGENTS.md) is binding for every milestone.


1. OpenSpec states intent, requirements, scenarios, non-goals, and acceptance.
2. Tests prove the safety invariants and failure behavior.
3. Architecture, threat model, validation guide, and usage documentation are updated or explicitly unchanged.
4. CI is green on the exact PR head; validation gates are never weakened.
5. Manual acceptance is recorded for every WordPress-facing or mutation-capable feature.
6. PR stays draft until the milestone is complete and awaits explicit merge approval.

## Remaining implementation plan

### M4 — Candidate review view

Render the configured policy against each inventory row: score, confidence band, policy version, allowlist state, and reasons. Preserve cursor pagination and zero account mutation.

**Acceptance:** an administrator can understand why a signal exists, distinguish an allowlisted account, and verify no account data changed.

### M5 — Review jobs and immutable candidate snapshots

Create a preview job that captures the policy version, filters, scan cursor, candidate score/reasons, and a redacted audit receipt. Jobs remain read-only and cancellable.

**Acceptance:** a review result can be reproduced from its stored policy snapshot; retrying a scan does not duplicate a candidate.

### M6 — Quarantine and restore

Add a dedicated administrator-confirmed, per-account quarantine command. Preserve original role/data, invalidate sessions, block authentication, record audit evidence, and provide restore.

**Acceptance:** quarantined accounts cannot authenticate; restore returns the exact prior state; protected accounts and non-high-confidence accounts are rejected.

### M7 — Background review execution and CLI

Run large review jobs in bounded batches with durable cursors, exclusive claims, pause/resume/cancel/retry, progress, and WP-CLI support. Never enqueue one task per user. Quarantine remains a per-account, administrator-confirmed M6 operation.

**Acceptance:** an interrupted 600,000-user simulation resumes without skips or duplicates and stays within configured batch limits.

### M8 — Permanent deletion

After a configurable default 30-day quarantine grace period, expose a separate, confirmation-bound deletion workflow with authored-content disposition, Multisite safeguards, per-target preflight, and audit export.

**Acceptance:** deletion rejects stale/invalid previews, protected accounts, and accounts before grace expiry; content disposition is explicit.

### M9 — Prevention and release hardening

Add registration lockdown guidance, optional privacy-conscious registration evidence, rate-control integration points, release packaging, recovery/operator documentation, performance testing, and supported-host acceptance.

**Acceptance:** a site that does not need public registration can close the attack surface; operator documentation covers prevention, rollback, and incident recovery.

## Decision gates

The following require a dedicated review before implementation: altering the 30-day default grace period; adding external reputation sources; retaining IP/user-agent data; automatic quarantine; Multisite network deletion; or any bulk destructive action.
