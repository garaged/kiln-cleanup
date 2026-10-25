# Kiln Cleanup

A safety-first, self-hosted WordPress cleanup plugin for site owners who need to remediate large volumes of unwanted accounts without treating a heuristic as permission to destroy data.

## Status

**M7 — resumable background review execution.** Quarantine remains reversible, per-account, and administrator-confirmed; bulk and automatic account action do not exist.

## Product principles

- local administration only; no relay, telemetry, credential store, ML, or external reputation service;
- explainable detection rather than opaque bot verdicts;
- explicit allowlists take precedence over every detector;
- review and quarantine before permanent deletion;
- resumable background execution for large jobs;
- redacted, auditable outcomes;
- no automatic destructive action from a detection score.

## Current capability

Administrators can inspect users through **Tools → Kiln Cleanup** using a cursor-based page (100 users per page) and an optional exact registration-date filter. **Tools → Kiln Cleanup Jobs** creates a durable review job whose policy and filter are immutable snapshots. A job runs one bounded, idempotent scan step at a time and stores review-only candidate evidence.

**Tools → Kiln Cleanup Quarantine** provides a separate, per-account command for high-confidence review candidates. It requires a typed target-specific confirmation, re-checks the captured policy allowlist, captures the original access state, destroys sessions, blocks future authentication, and supports exact restore. It never deletes an account, content, or profile data.

Review jobs may run one bounded batch through WordPress Cron or `wp kiln-cleanup review-jobs run`. Both paths only evaluate and persist review evidence; neither can quarantine or restore an account.

## Planned account-cleanup flow

discover → classify → review → quarantine → grace period → permanent deletion

For a large incident, workers will use keyset pagination and compact, idempotent batches rather than OFFSET queries or one background task per account.

## Development

    composer install
    composer test
    composer lint

See the [M6 specification](openspec/specs/m6-quarantine-restore/spec.md), [manual acceptance](docs/manual-m6-quarantine-restore.md), and [roadmap](docs/roadmap.md).
