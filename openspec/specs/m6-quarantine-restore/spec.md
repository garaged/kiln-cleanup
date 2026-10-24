# M6 — Reversible Quarantine and Restore

## Intent

Provide a deliberately narrow, administrator-confirmed response to an M5 high-confidence review candidate without deleting an account or its content.

## Requirements

- A quarantine command MUST require an existing M5 candidate with the `eligible_for_quarantine` band from the specified review job.
- It MUST re-check the review job's immutable policy snapshot against the account's current email immediately before mutation. An allowlisted account MUST be rejected.
- Each command MUST require an administrator capability, WordPress nonce, and a typed, target-specific confirmation string.
- Quarantine MUST capture the exact capabilities and user-level before removing access, destroy all sessions, and block future authentication.
- Quarantine MUST be recorded durably before the account-access mutation and MUST produce a redacted audit receipt.
- Restore MUST return the stored exact capabilities and level, clear the authentication block, invalidate prior sessions, and emit a redacted audit receipt.
- Only a single account may be quarantined or restored per request. No bulk command, queue, or deletion path exists.

## Scenarios

### Quarantine an eligible candidate

Given an administrator has an eligible candidate from review job 12, when they type `QUARANTINE 44` and submit, then account 44 loses capabilities, active sessions are destroyed, future authentication is blocked, and the original access state is retained for restore.

### Reject a protected account

Given the captured policy protects an account, when quarantine is requested, then no access state changes and the UI reports the rejection.

### Restore exact access

Given an active quarantine, when an administrator types `RESTORE 44`, then the captured capabilities and user-level are restored, the quarantine marker is cleared, and existing sessions remain invalidated.

## Non-goals

No permanent deletion, content disposition, automatic action, background/bulk quarantine, external reputation data, telemetry, or credential storage.
