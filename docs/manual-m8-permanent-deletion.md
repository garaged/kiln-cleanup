# M8 manual acceptance — permanent deletion

```sh
git fetch origin --prune
git switch feature/m8-permanent-deletion
git pull --ff-only origin feature/m8-permanent-deletion
git status --short
git rev-parse HEAD
make release-package
make release-verify
make container-preflight
make container-manual-up
```

1. Under **Tools → Kiln Cleanup Deletion**, verify that an actively quarantined account under 30 days old displays its email, source review job, and authored-content summary, but has no deletion button.
2. On the disposable stack only, age that account's quarantine timestamp beyond 30 days using the test setup, refresh, and verify the summary still appears with an explicit `DELETE <account-id>` confirmation field.
3. Give the test account at least one post and one media item. Confirm the preflight count identifies both before deletion.
4. Submit an incorrect confirmation. Verify no user or content is deleted.
5. Submit the exact confirmation. Verify the account, authored post, and media item no longer exist; the audit export contains an `account_deleted` event with IDs, disposition, and aggregate counts but no email, title, body, URL, IP, or user meta.
6. Confirm **Tools → Kiln Cleanup Deletion** reports that it is unavailable when WordPress Multisite is active. No network-user action must be offered.
7. Run `make container-test`.

```sh
make container-down
```
