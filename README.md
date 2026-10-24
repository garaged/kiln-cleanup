# Kiln Cleanup

A safety-first, self-hosted WordPress cleanup plugin for site owners who need to remediate large volumes of unwanted accounts without treating a heuristic as permission to destroy data.

## Status

**M1 — read-only user inventory in progress.** The plugin contains no user mutation path.

## Product principles

- local administration only; no relay, telemetry, or credential store;
- explainable detection rather than opaque bot verdicts;
- review and quarantine before permanent deletion;
- resumable background execution for large jobs;
- redacted, auditable outcomes;
- no automatic destructive action from a detection score.

## Current capability

Administrators can inspect users through **Tools → Kiln Cleanup** using a cursor-based page (100 users per page) and an optional exact registration-date filter. The page is intentionally read-only.

## Planned account-cleanup flow

discover → classify → review → quarantine → grace period → permanent deletion

For a large incident, workers will use keyset pagination and compact, idempotent batches rather than OFFSET queries or one background task per account.

## Development

    composer install
    composer test
    composer lint

See [M0 specification](openspec/specs/m0-foundation/spec.md), the [M1 specification](openspec/specs/m1-user-inventory/spec.md), the [architecture](docs/architecture.md), and the [threat model](docs/threat-model.md).
