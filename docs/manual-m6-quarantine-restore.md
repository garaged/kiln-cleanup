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

1. Create a disposable subscriber account and a high-confidence candidate through a completed M5 review job. Do not use a real account.
2. In **Tools → Kiln Cleanup Quarantine**, verify only high-confidence candidates appear. Attempt an incorrect confirmation; verify no change occurs.
3. Type exactly `QUARANTINE <account-id>`. Verify the account cannot authenticate in an incognito browser and any earlier session is invalidated.
4. Verify the account's role is absent while quarantined; verify its profile and authored content remain present.
5. Use a policy-protected account and verify quarantine is rejected with no mutation.
6. Type exactly `RESTORE <account-id>`. Verify the original role/capabilities return, the quarantine marker is cleared, and a new login works. Existing sessions must still require a new login.
7. Inspect `kiln_cleanup_audit_events`: quarantine and restore rows must contain IDs and the source marker only—no email, capabilities, sessions, or profile data.

When finished:

```sh
make container-down
```
