# Validation strategy

## Automated gates

| Gate | Coverage |
| --- | --- |
| Composer metadata validation | Required in CI |
| PHP 8.2 and 8.3 syntax checks | Required in CI |
| Domain unit tests | Required in CI |
| Docker Compose topology | Required in CI |
| WordPress activation/schema/review-job smoke test | Required in CI/container smoke |
| Review-job interruption/retry test | Required before M5 merge; verifies idempotent candidates and cursor compare-and-set |
| Large-volume interruption/retry test | M7, before background cleanup ships |
| Quarantine/restore acceptance test | M6, before any user mutation ships |
| Permanent-deletion preflight and lifecycle acceptance test | M8, before irreversible mutation ships |

## Non-negotiable release rule

No cleanup action is released based solely on unit tests. The corresponding WordPress lifecycle, authorization, failure recovery, and manual acceptance evidence must exist first.

For M6, acceptance must prove that a low-confidence or protected account is rejected without mutation; quarantine blocks a test account's existing and new sessions; restore returns its original role/capability state; and audit rows contain no email, capability values, or session data.

For M8, acceptance must prove that a target before the 30-day grace deadline, a restored/nonexistent target, a newly protected target, and any Multisite target are rejected without mutation. A matured single-site target must display aggregate authored-content counts, require its exact deletion token, delete the account and authored content through WordPress, and create a redacted audit receipt/export.
