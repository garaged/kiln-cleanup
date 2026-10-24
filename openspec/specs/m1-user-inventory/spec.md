# M1: Read-only User Inventory

## Intent

Give an administrator a safe way to inspect a large user population before any detection or cleanup policy exists.

## Requirements

### R1 — Read-only boundary

M1 MUST NOT create, modify, quarantine, delete, authenticate, or otherwise change a user account.

### R2 — Keyset pagination

Inventory MUST request users using an ascending ID cursor. It MUST NOT use SQL OFFSET pagination.

#### Scenario: page has further results
- Given the repository receives 101 matching rows for a requested page size of 100
- Then it returns 100 records
- And it exposes the 100th ID as the next cursor.

### R3 — Bounded page size

The service MUST reject page sizes below 25 or above 500 before it queries WordPress.

### R4 — Safe filter parsing

The initial registered-before filter MUST accept only exact YYYY-MM-DD values and MUST reject malformed or ambiguous dates.

### R5 — Administrator-only interface

The inventory page MUST require the manage_options capability and escape all displayed values.
