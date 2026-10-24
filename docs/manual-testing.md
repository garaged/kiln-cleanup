# Manual testing

## M1–M4 read-only acceptance

1. Install the packaged plugin on WordPress 6.9+ / PHP 8.2+.
2. Activate it and verify the three Kiln Cleanup tables are created.
3. As an administrator, open Tools → Kiln Cleanup and navigate two cursor pages; IDs must increase with no repeats.
4. Apply a valid registered-before date; malformed values must fail closed.
5. Open Tools → Kiln Cleanup Policy. Save a protected email and blocked domain; use a fresh page load to confirm persistence.
6. Confirm protected users score zero and show no detector reasons.
7. Confirm a blocked-domain account displays an explainable signal.
8. Confirm no user account, role, session, login, or content changes.

Record WordPress/PHP versions, package checksum, and outcomes with the PR before merge.
