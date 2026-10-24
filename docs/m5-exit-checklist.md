# M5 exit checklist

- [x] Immutable policy/filter review-job snapshots.
- [x] Idempotent candidate snapshots.
- [x] Bounded keyset scan step.
- [x] Optimistic cursor progression after candidate persistence.
- [x] Read-only pause/resume/cancel command contract.
- [x] Persist lifecycle transitions and redacted audit receipts.
- [x] Automated snapshot, lifecycle, idempotence, and cursor-count tests.
- [x] Administrator-only review-job UI with nonce-protected controls.
- [x] Hosted CI on the exact PR head.
- [ ] Rancher Desktop ARM64 manual acceptance.

No M5 capability may modify a WordPress user, authentication, session, role, or content.
