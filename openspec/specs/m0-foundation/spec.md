# M0: Safety Foundation

## Intent

Establish the non-destructive foundation for a WordPress cleanup plugin that can later manage extremely large bot-account populations without weakening operator control.

## Requirements

### R1 — Compatibility gate

The plugin MUST refuse activation below PHP 8.2 or WordPress 6.9 and MUST explain the unmet requirement.

#### Scenario: unsupported PHP
- Given PHP 8.1
- When an administrator activates the plugin
- Then activation is blocked with the minimum supported PHP version.

### R2 — No mutation in M0

M0 MUST NOT alter, delete, quarantine, or authenticate as a WordPress user.

### R3 — Durable job model

The schema MUST store a cleanup job's state, immutable policy snapshot, cursor, totals, creator, and timestamps.

### R4 — Candidate integrity

Candidates MUST be unique for a job and user pair and MUST retain their score, reasons, and action state.

### R5 — Privacy-preserving audit model

Audit events MUST be append-only by design and MUST NOT require raw email, IP address, or arbitrary user meta.

### R6 — Terminal jobs

Completed and cancelled jobs MUST NOT transition back into an executable state.
