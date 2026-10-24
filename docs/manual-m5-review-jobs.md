# M5 review-job acceptance

Before testing, refresh the exact branch:

    git fetch origin --prune
    git switch feature/m5-review-jobs
    git pull --ff-only origin feature/m5-review-jobs
    git status --short
    git rev-parse HEAD

1. Run `make container-manual-up`. This provisions and leaves the test stack running at `http://localhost:8080/wp-admin`; sign in as `admin` with password `not-a-real-secret`. If port 8080 is occupied, set `KILN_CLEANUP_HTTP_PORT` before the command and use that port instead.
2. In **Tools → Kiln Cleanup Jobs**, create a job with a known registration-date filter. It must appear as `ready` with the displayed policy version and filter.
3. Change the current policy in the policy page. Return to the existing job: its displayed snapshot version/filter must remain unchanged.
4. Press **Run one safe scan step**. Refresh the page and note the cursor. Re-submit/refresh safely: the job must never create duplicate `(job, user)` candidates.
5. Press **Pause**, then **Resume**, then run one step. The cursor must move forward only; it must not skip backwards or skip records.
6. Create a second job and press **Cancel**. Confirm its state is `cancelled` and that no WordPress user, role, content, login, or session changed.
7. Inspect the audit table with an administrator database tool. Context must contain only state names, IDs, cursors, counts, and no raw email, IP, or user meta.
8. Run `make release-package`, `make release-verify`, `make container-preflight`, `make container-test`, and finally `make container-down`.
