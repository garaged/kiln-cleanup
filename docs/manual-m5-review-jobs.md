# M5 review-job acceptance

Before testing, refresh the exact branch:

    git fetch origin --prune
    git switch feature/m5-review-jobs
    git pull --ff-only origin feature/m5-review-jobs
    git status --short
    git rev-parse HEAD

1. Create a review job from a known policy/filter.
2. Change current policy; verify the existing job snapshot and candidates remain unchanged.
3. Run a bounded scan step, then retry it; candidate count must not duplicate.
4. Interrupt and resume; the cursor must not skip a user.
5. Cancel the job; confirm no WordPress user, role, content, login, or session changed.
6. Inspect audit evidence; it must contain IDs, counts, rule codes, and digests only.
7. Run `make release-package`, `make release-verify`, `make container-preflight`, `make container-test`, and `make container-down`.
