# Operations and recovery

## Supported host baseline

Kiln Cleanup supports WordPress 6.9+ on PHP 8.2 or 8.3 with a MySQL-compatible database, normal WordPress Cron availability, and filesystem access sufficient for plugin installation. The release package and container stack are validated on Linux CI; each mutation-capable milestone also requires Rancher Desktop ARM64 acceptance.

## Registration prevention recovery

- **Public registration locked down:** Kiln changes only WordPress’s `users_can_register` option. Re-enable registration explicitly in **Settings → General → Membership**, if the site genuinely needs it. Kiln never re-enables it automatically.
- **Registration evidence enabled:** Open **Tools → Kiln Cleanup Prevention**, uncheck the evidence option, and save. Existing redacted audit events remain in the audit ledger; no raw network identifiers, email, password, or request payload were collected.
- **External rate-control integration:** Remove or change the local component returning `true` from `kiln_cleanup_registration_rate_limited`. Kiln does not keep a counter, cookie, block list, or remote state.

## Performance verification

Before a production rollout with a large user base, run review jobs with the intended batch size and record:

1. the user count and filter used;
2. elapsed time for at least five bounded review batches;
3. peak database/query impact from the host’s normal observability tools; and
4. cursor monotonicity, candidate idempotency, and the absence of account mutations.

Start with a batch size of 100 and stay within the 25–500 bounds. Reduce the batch size if host latency or database pressure is material. Prevention controls add no recurring scanner, rate counter, or external request.
