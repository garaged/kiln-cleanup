# M7 manual acceptance — background review execution

```sh
git fetch origin --prune
git switch feature/m7-background-review-execution
git pull --ff-only origin feature/m7-background-review-execution
git status --short
git rev-parse HEAD
make release-package
make release-verify
make container-preflight
make container-manual-up
```

1. Create a review job under **Tools → Kiln Cleanup Jobs**. Confirm it begins in `ready` state.
2. Wait at least one minute, then refresh. Confirm the scheduled worker advances the cursor or completes the job. The progress display must move only forward.
3. Create another review job, then run a bounded CLI batch from the WordPress container:

   ```sh
   docker compose -f docker-compose.test.yml exec -u 0 wordpress wp kiln-cleanup review-jobs run --batches=1 --batch-size=25 --allow-root
   ```

4. Confirm the job is running/completed and that no WordPress user, role, session, login, quarantine record, or content changed.
5. Pause a running job. Wait two minutes and verify the cursor does not move. Resume it and verify execution can continue.
6. Verify an existing high-confidence candidate is not quarantined by cron or WP-CLI.
7. Run `make container-test`; it must pass the schema and M6 quarantine/restore smoke checks.

```sh
make container-down
```
