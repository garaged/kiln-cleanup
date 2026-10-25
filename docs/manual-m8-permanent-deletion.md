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
2. On the disposable stack only, create authored content for that account (replace `<USER_ID>`):

   ```sh
   docker compose -f docker-compose.test.yml run --rm --entrypoint=wp smoke post create --post_title='M8 deletion acceptance' --post_status=publish --post_author=<USER_ID> --porcelain --allow-root
   ```

3. On the disposable stack only, age that same active quarantine beyond 30 days, then refresh the page:

   ```sh
   docker compose -f docker-compose.test.yml run --rm --entrypoint=wp smoke eval 'global $wpdb; $userId = (int) $argv[1]; $updated = $wpdb->update($wpdb->prefix . "kiln_cleanup_quarantines", ["quarantined_at" => gmdate("Y-m-d H:i:s", time() - 31 * DAY_IN_SECONDS)], ["user_id" => $userId, "state" => "quarantined"]); if ($updated !== 1) { throw new RuntimeException("Expected one active quarantine to be aged."); }' <USER_ID> --allow-root
   ```

   Verify the summary still appears with an explicit `DELETE <account-id>` confirmation field and identifies the authored content before deletion.
4. Submit an incorrect confirmation. Verify no user or content is deleted.
5. Submit the exact confirmation. Verify the account and authored post no longer exist; the audit export contains an `account_deleted` event with IDs, disposition, and aggregate counts but no email, title, body, URL, IP, or user meta.
6. Confirm **Tools → Kiln Cleanup Deletion** reports that it is unavailable when WordPress Multisite is active. No network-user action must be offered.
7. Run `make container-test`.

```sh
make container-down
```
