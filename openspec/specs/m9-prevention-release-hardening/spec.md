# M9 — Prevention and Release Hardening

## Intent

Reduce unwanted registrations and strengthen safe operations without introducing a remote service, opaque rate limiter, automatic account action, or collection of network identifiers.

## Requirements

- An administrator MUST be able to explicitly disable public registration from Kiln. The action MUST require administrator capability, a nonce, and the typed confirmation `LOCKDOWN REGISTRATION`. It MUST set WordPress’s existing `users_can_register` option to false and emit a redacted audit receipt.
- The prevention page MUST show the current public-registration state and explain that disabling registration is reversible through WordPress Settings or a future explicit Kiln enable action.
- Registration evidence MUST be disabled by default. When explicitly enabled by an administrator, Kiln MAY record a redacted local audit event for a successful registration containing only target user ID, event time, and the statement that no IP address, user-agent, email, password, or request payload was retained.
- Kiln MUST expose a documented local rate-control integration point through the `kiln_cleanup_registration_rate_limited` filter. Kiln itself MUST not count requests, inspect IPs, set cookies, contact an external service, or impose a hidden threshold. A filter response of true MUST reject registration before account creation with a generic message.
- Prevention configuration changes MUST require administrator capability and a nonce and MUST generate redacted audit events.
- M9 MUST update operator recovery, release, performance, and supported-host acceptance documentation. Release packaging MUST remain reproducible and verification MUST fail when packaged files or checksum do not match.
- No M9 code path may quarantine, restore, delete, change roles, alter content, or make automatic account decisions.

## Scenarios

### Lock down a closed-registration site

Given public registration is enabled, when an administrator types `LOCKDOWN REGISTRATION` and submits, then WordPress public registration is disabled and a redacted audit receipt is recorded.

### Opt-in registration evidence

Given evidence is disabled, when a user registers, then Kiln writes no registration-evidence event. Given an administrator enables evidence, when a user registers, then Kiln records only the user ID and redacted event metadata, with no network identifiers or email retained.

### External local rate control

Given a local plugin returns true from `kiln_cleanup_registration_rate_limited`, when a registration is attempted, then WordPress rejects it before account creation. Given no filter blocks it, Kiln adds no rate limit of its own.

## Non-goals

No IP or user-agent retention, built-in rate limits, CAPTCHA, external reputation service, telemetry, cookies, relay, automatic quarantine, bulk action, account mutation, or content mutation.
