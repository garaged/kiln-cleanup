# M6 manual acceptance — quarantine and restore

## Start from the exact PR branch

```sh
git fetch origin --prune
git switch feature/m6-quarantine-restore
git pull --ff-only origin feature/m6-quarantine-restore
git status --short
git rev-parse HEAD
make release-package
make release-verify
make container-preflight
make container-manual-up
```

Open the printed local WordPress URL and sign in as `admin` / `not-a-real-secret`.

## Safety checks

1. In **Users → Add New User**, create a disposable Subscriber. Use an email such as `m6-candidate@blocked.test`; do not use a real account.
2. In **Tools → Kiln Cleanup Policy**, add the exact blocked domain `blocked.test` and save. This produces the high-confidence, explainable signal used only for this test.
3. In **Tools → Kiln Cleanup Jobs**, create a review job and run one scan step. On the disposable stack it completes in one batch. Verify the job is `completed`.
4. In **Tools → Kiln Cleanup Quarantine**, verify the disposable account appears as the eligible review candidate. Enter an incorrect confirmation first; verify no change occurs.
5. Type exactly `QUARANTINE <account-id>`. Verify the account cannot authenticate in an incognito browser and any earlier session is invalidated.
6. Verify the account's role is absent while quarantined; verify its profile and authored content remain present.
7. Type exactly `RESTORE <account-id>`. Verify the original Subscriber role/capabilities return, the quarantine marker is cleared, and a new login works. Existing sessions must still require a new login.
8. Inspect `kiln_cleanup_audit_events`: quarantine and restore rows must contain IDs and the source marker only—no email, capabilities, sessions, or profile data.

When finished:

```sh
make container-down
```
