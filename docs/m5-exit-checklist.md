# M5 exit checklist

- [x] Immutable policy/filter review-job snapshots.
- [x] Idempotent candidate snapshots.
- [x] Bounded keyset scan step.
- [x] Optimistic cursor progression after candidate persistence.
- [x] Read-only pause/resume/cancel command contract.
- [ ] Persist lifecycle transitions and audit receipts.
- [ ] Automated persistence/integration tests.
- [ ] Review-job admin UI and manual acceptance.
- [ ] Hosted CI plus Rancher Desktop ARM64 acceptance.

No M5 capability may modify a WordPress user, authentication, session, role, or content.
