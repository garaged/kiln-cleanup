# Validation strategy

## Automated gates

| Gate | M0 coverage |
| --- | --- |
| Composer metadata validation | Required in CI |
| PHP 8.2 and 8.3 syntax checks | Required in CI |
| Domain unit tests | Required in CI |
| Docker Compose topology | Required in CI |
| WordPress activation smoke test | M1, when a reproducible packaged vendor tree is added |
| Large-volume interruption/retry test | C4, before background cleanup ships |
| Quarantine/restore acceptance test | C3, before any user mutation ships |

## Non-negotiable release rule

No cleanup action is released based solely on unit tests. The corresponding WordPress lifecycle, authorization, failure recovery, and manual acceptance evidence must exist first.
