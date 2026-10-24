# M5: Immutable Review Jobs and Candidate Snapshots

## Intent

Persist a reproducible, read-only review result before any account action exists. A job captures the exact policy, filters, candidate signals, and scan state that produced it.

## Scope

- Create, inspect, cancel, and retry read-only review jobs.
- Persist immutable policy and typed filter snapshots.
- Persist candidate score, band, reason codes, policy version, and source user ID.
- Use keyset cursor progress and idempotent candidate insertion.
- Emit redacted audit events.

## Non-goals

No quarantine, restore, deletion, login/session change, automatic action, external service, telemetry, or machine learning.

## Requirements

### R1 — Snapshot immutability

A job retains its original policy/filter snapshots. Editing the current policy never alters an existing job.

### R2 — Idempotent candidates

A candidate is unique by job ID and user ID. Retrying the same cursor cannot create a duplicate.

### R3 — Review-only lifecycle

A job may be draft, ready, running, paused, completed, failed, or cancelled. No lifecycle transition alters a WordPress user.

### R4 — Bounded execution

Each scan step uses a cursor and bounded batch size. OFFSET is forbidden.

### R5 — Redacted evidence

Audit records contain job/candidate identifiers, rule IDs, counts, and policy digest; never raw email, IP, or user meta.

## Acceptance

1. Create a job from a policy/filter snapshot.
2. Change the current policy and verify the old job remains unchanged.
3. Retry a scan step and verify no duplicate candidate.
4. Cancel a job and verify no user account was touched.
5. Resume from persisted cursor and verify no skips.
