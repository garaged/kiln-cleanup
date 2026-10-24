# Architecture

Kiln Cleanup is a standalone, self-hosted WordPress plugin. It does not expose cleanup mutations through MCP and never contains a remote relay, telemetry collector, or credential store.

## Safety boundary

discover → classify → review → quarantine → grace period → permanent deletion

No classifier can directly delete an account. Permanent deletion is a separate future capability and will require an explicit operator confirmation and an authored-content disposition policy.

## M0 persistence model

- **Jobs** retain an immutable policy snapshot and a monotonic user-ID cursor.
- **Candidates** retain the score, human-readable reason codes, and per-target state.
- **Audit events** are append-only and deliberately omit raw email, IP, and user-meta values.
- Long-running work uses keyset pagination (ID > cursor), not OFFSET, and idempotent state transitions.

## M1 inventory boundary

The WordPress user table is accessed behind a read-only repository. Its query has an ascending ID cursor, bounded page size, and typed filter input. The UI receives records only through the application service; no M1 component contains a WordPress user mutation function.

## Extension boundary

Future detectors and activity providers may contribute reason codes through narrowly typed interfaces. They cannot override allowlists, mutate users, or grant themselves a destructive action.
