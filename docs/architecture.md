# Architecture

Kiln Cleanup is a standalone, self-hosted WordPress plugin. It does not expose cleanup mutations through MCP and never contains a remote relay, telemetry collector, or credential store.

## Safety boundary

discover → classify → review → quarantine → grace period → permanent deletion

No classifier can directly delete an account. Permanent deletion is a separate future capability and will require an explicit operator confirmation and an authored-content disposition policy.

## M5 review-job persistence model

- **Jobs** retain one immutable policy/filter snapshot, an optimistic monotonic user-ID cursor, and a review-only lifecycle.
- **Candidates** retain the source user ID, score, band, stable rule codes, snapshot policy version, and per-target review state. The unique `(job_id, user_id)` key makes a retry idempotent.
- **Audit events** are append-only and deliberately omit raw email, IP, and user-meta values.
- A cursor advances only after its complete candidate batch is durable. The advancement uses a compare-and-set predicate, so concurrent or retried workers cannot silently skip a user.
- Long-running work uses keyset pagination (ID > cursor), not OFFSET, bounded batches, and explicit persisted lifecycle transitions.

## M1 inventory boundary

The WordPress user table is accessed behind a read-only repository. Its query has an ascending ID cursor, bounded page size, and typed filter input. The UI receives records only through the application service; no M1 component contains a WordPress user mutation function.

## M2 detection boundary

The detection engine consumes a user record and an explicit, versioned local policy. It emits a score, band, and stable reasons. Allowlist matches short-circuit all scoring. A high band only means eligible for a future quarantine review; M2 has no mutation service, queue, or action endpoint.

## Extension boundary

Future detectors and activity providers may contribute reason codes through narrowly typed interfaces. They cannot override allowlists, mutate users, or grant themselves a destructive action.

## M5 operator boundary

The Review Jobs page is administrator-only and every state-changing request carries a WordPress nonce. It exposes only create, scan-one-batch, pause, resume, and cancel. Candidate persistence and audit recording are the only writes; no M5 class invokes a WordPress user, role, authentication, session, or content mutation API.

## M6 quarantine boundary

- Quarantine is a per-account, administrator-confirmed command sourced only from an M5 `eligible_for_quarantine` candidate.
- The immutable policy snapshot is re-applied immediately before mutation; a protected account is rejected even if it appeared in an earlier review result.
- The quarantine record stores only the access state needed for restore: the original capability map and user level. It does not copy profile data, content, email, IP, or authentication secrets.
- A quarantine removes capabilities, marks the account unavailable to the authentication hook, and destroys every session token. Restore returns the exact captured access state and invalidates sessions again.
- No automatic, background, bulk, deletion, or content mutation capability exists in M6.

## M7 background review boundary

- A worker claims exactly one `ready` or `running` review job with a short durable lease before processing a single bounded scan batch.
- Claims are compare-and-set updates and expire after interruption. The existing candidate-before-cursor ordering remains the resume guarantee.
- WordPress Cron wakes the worker at most once per minute. WP-CLI invokes the same worker and constrains batch size/count.
- Worker failure records a bounded error and transitions only the review job to `failed`; it never changes a WordPress account.
