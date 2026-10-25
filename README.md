# Kiln Cleanup

A safety-first, self-hosted WordPress cleanup plugin for site owners who need to remediate large volumes of unwanted accounts without treating a heuristic as permission to destroy data.

## Status

**M9 — local prevention and release hardening.** Registration lockdown is explicit and reversible; optional registration evidence is redacted and disabled by default; no network identifiers, telemetry, or automatic account action exist.

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

**Tools → Kiln Cleanup Deletion** is a distinct irreversible command for actively quarantined accounts after 30 days. It shows an aggregate authored-content summary, requires `DELETE <account-id>`, and then deletes the account with that authored content through WordPress APIs. It has no reassignment, bulk, Cron, CLI, or Multisite path. Administrators can export the redacted Kiln audit ledger as CSV.

**Tools → Kiln Cleanup Prevention** can explicitly disable WordPress public registration with `LOCKDOWN REGISTRATION`. It also controls opt-in local registration evidence, which records only target account IDs and event metadata. Existing local plugins may block registration using the `kiln_cleanup_registration_rate_limited` filter; Kiln has no built-in request tracking or rate threshold.

## Planned account-cleanup flow

discover → classify → review → quarantine → grace period → permanent deletion

For a large incident, workers will use keyset pagination and compact, idempotent batches rather than OFFSET queries or one background task per account.

## Development

    composer install
    composer test
    composer lint

See the [M8 specification](openspec/specs/m8-permanent-deletion/spec.md), [manual acceptance](docs/manual-m8-permanent-deletion.md), and [roadmap](docs/roadmap.md).
