# M9 manual acceptance — prevention and release hardening

1. Open **Tools → Kiln Cleanup Prevention** as an administrator. Verify public-registration state is displayed and evidence is disabled by default.
2. With public registration enabled on the disposable stack, submit an incorrect lockdown confirmation. Verify registration remains enabled. Then type `LOCKDOWN REGISTRATION`; verify WordPress public registration is disabled and the audit CSV contains a redacted `public_registration_disabled` event.
3. Enable registration evidence, create one disposable user through WordPress, and export the audit CSV. Verify `registration_observed` identifies only the target ID and does not contain email, IP address, user-agent, password, or request payload. Disable evidence and repeat; no event should be added.
4. Add a disposable local plugin/filter returning `true` from `kiln_cleanup_registration_rate_limited`. Verify registration is rejected before account creation. Remove the filter and verify Kiln itself imposes no rate threshold.
5. Run `make release-package`, `make release-verify`, `make container-preflight`, and `make container-test`.
