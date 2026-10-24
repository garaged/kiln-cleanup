# M3: Local Detection Policy and Review

## Intent

Let administrators configure the local, explainable detection policy while retaining the read-only account boundary.

## Requirements

### R1 — Administrator-only policy changes

Only administrators with manage_options may view or save policy. Saves MUST validate a WordPress nonce.

### R2 — Bounded local policy

Each list contains at most 500 lines. Protected IDs must be positive integers; protected emails and blocked domains must pass DetectionPolicy validation.

### R3 — No account mutation

Saving policy MUST NOT create, modify, quarantine, delete, authenticate, or otherwise change users.

### R4 — Review-only meaning

The interface MUST state that the policy changes review signals only. It MUST NOT offer a cleanup action.
