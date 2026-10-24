# Kiln Cleanup

A safety-first, self-hosted WordPress cleanup plugin for site owners who need to remediate large volumes of unwanted accounts without treating a heuristic as permission to destroy data.

## Status

**M0 — foundation in progress.** The plugin contains no user mutation path yet.

## Product principles

- local administration only; no relay, telemetry, or credential store;
- explainable detection rather than opaque bot verdicts;
- review and quarantine before permanent deletion;
- resumable background execution for large jobs;
- redacted, auditable outcomes;
- no automatic destructive action from a detection score.

## Planned account-cleanup flow

discover → classify → review → quarantine → grace period → permanent deletion

For a large incident, workers will use keyset pagination and compact, idempotent batches rather than OFFSET queries or one background task per account.

## Development

    composer install
    composer test
    composer lint

See [M0 specification](openspec/specs/m0-foundation/spec.md), the [architecture](docs/architecture.md), and the [threat model](docs/threat-model.md).
