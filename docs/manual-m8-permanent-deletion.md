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

1. Create a dedicated disposable fixture. It creates a subscriber, a completed high-confidence review candidate, and its active quarantine; it prints the resulting account ID and email:

   ```sh
   make container-manual-m8-fixture
   ```

   Refresh **Tools → Kiln Cleanup Deletion**. The account displays its email, source review job, and authored-content summary, but has no deletion button.
2. On the disposable stack only, create authored content for that same account (replace `<USER_ID>` with the printed fixture ID):

   ```sh
   docker compose -f docker-compose.test.yml run --rm --entrypoint=wp smoke post create --post_title='M8 deletion acceptance' --post_status=publish --post_author=<USER_ID> --porcelain --allow-root
   ```

3. On the disposable stack only, age that same active quarantine beyond 30 days, then refresh the page:

   ```sh
   docker compose -f docker-compose.test.yml run --rm --entrypoint=wp smoke eval 'global $wpdb; $userId = 123; $updated = $wpdb->update($wpdb->prefix . "kiln_cleanup_quarantines", ["quarantined_at" => gmdate("Y-m-d H:i:s", time() - 31 * DAY_IN_SECONDS)], ["user_id" => $userId, "state" => "quarantined"]); if ($updated !== 1) { throw new RuntimeException("Expected one active quarantine to be aged."); }' --allow-root
   ```

   Replace `123` with the target account ID. `wp eval` does not accept an extra positional argument, so the ID intentionally appears inside the disposable-stack command.

   Verify the summary still appears with an explicit `DELETE <account-id>` confirmation field and identifies the authored content before deletion.
4. Submit an incorrect confirmation. Verify no user or content is deleted.
5. Submit the exact confirmation. Verify the account and authored post no longer exist; the audit export contains an `account_deleted` event with IDs, disposition, and aggregate counts but no email, title, body, URL, IP, or user meta.
6. Confirm **Tools → Kiln Cleanup Deletion** reports that it is unavailable when WordPress Multisite is active. No network-user action must be offered.
7. Run `make container-test`.

```sh
make container-down
```
