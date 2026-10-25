# M8 — Explicit Permanent Deletion After Grace Period

## Intent

Provide a narrow, irreversible, per-account deletion command only after an account has remained actively quarantined for at least 30 days. The command makes authored-content disposition visible before the operator confirms deletion.

## Requirements

- Deletion MUST be available only to an administrator through the local WordPress administration interface. It MUST never be available through Cron, WP-CLI, a queue, or a bulk command.
- The grace period is fixed at a minimum of 30 elapsed days from the active quarantine record’s `quarantined_at` timestamp. A target before that deadline MUST be rejected without mutation.
- Each deletion request MUST include a WordPress nonce and a typed, target-specific confirmation string of `DELETE <account-id>`.
- Before confirmation, the UI MUST show the account email, source review job, quarantine timestamp, remaining grace time, and an authored-content summary. The summary MUST be recomputed at execution time.
- Confirmed deletion MUST delete the WordPress account and its authored content. The UI MUST state this disposition explicitly; reassignment is not offered in M8.
- Immediately before deletion, the service MUST re-check that the account has an active quarantine record, still exists, is not protected by its source review job’s immutable policy snapshot, is past the grace deadline, and is not running on WordPress Multisite.
- Multisite MUST be rejected before any content or account mutation. M8 introduces no network-user deletion behavior.
- The deletion service MUST use WordPress lifecycle APIs for account removal. It MUST not issue direct SQL against WordPress user or content tables.
- A successful deletion MUST emit a redacted audit event containing only target ID, source job ID, disposition name, and aggregate content counts. It MUST never retain email, titles, bodies, URLs, IPs, or user meta values.
- A CSV audit export for Kiln audit events MUST be administrator-only, nonce-protected, and contain only existing redacted audit fields.

## Scenarios

### Preflight before grace expiry

Given account 44 is actively quarantined 12 days ago, when an administrator opens the deletion preflight, then its grace deadline is shown and deletion is unavailable.

### Delete an eligible account and authored content

Given account 44 has been actively quarantined for 30 days and owns 3 posts and 2 media items, when an administrator sees that summary, types `DELETE 44`, and confirms, then WordPress deletes the account and the selected authored content and Kiln records a redacted deletion receipt.

### Reject stale or protected targets

Given an account has been restored, deleted already, or is newly protected by the immutable source policy, when deletion is submitted, then no account or content mutation occurs.

### Reject Multisite

Given WordPress Multisite is active, when an administrator opens or submits deletion, then the operation is rejected before content or account mutation.

## Non-goals

No automatic deletion, bulk deletion, configurable grace period, content reassignment, network-user deletion, external archive, background deletion worker, telemetry, or retention of raw content/email in audit data.
