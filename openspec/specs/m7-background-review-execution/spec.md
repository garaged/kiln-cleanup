# M7 — Resumable Background Review Execution

## Intent

Scale M5 review jobs without changing the M6 quarantine safety boundary.

## Requirements

- WordPress Cron and WP-CLI MUST process only review-job scan batches; neither path may quarantine, restore, delete, alter users, roles, sessions, or content.
- A worker MUST claim one eligible review job through a durable lease before starting a batch. A concurrent worker MUST fail to claim it.
- Claims MUST expire, so an interrupted worker can be retried. Candidate writes remain idempotent and the cursor remains optimistic.
- A worker batch MUST remain bounded to 25–500 users. WP-CLI batch loops MUST be bounded.
- Worker errors MUST fail the review job closed, release its claim, retain a bounded error summary, and leave candidate evidence durable.
- Cron scheduling MUST be registered on activation and removed on deactivation.
- WP-CLI MUST expose only `wp kiln-cleanup review-jobs run`, with optional `--job`, `--batches`, and `--batch-size` bounds.

## Acceptance scenarios

### Interrupted worker resume

Given a claimed job’s lease expires before the cursor advances, when a later worker runs, then it can safely claim and retry the same bounded batch without duplicate candidates or skipped cursor IDs.

### Concurrent workers

Given two workers request the same ready job, when the first receives the lease, then the second receives no job and cannot advance its cursor.

### CLI is non-mutating

Given a completed high-confidence review candidate, when the CLI runs, then it scans review jobs only and never invokes quarantine or restore.

## Non-goals

No bulk quarantine, automatic quarantine, background restore, deletion, content action, external queue, per-user queued task, telemetry, or third-party dependency.
