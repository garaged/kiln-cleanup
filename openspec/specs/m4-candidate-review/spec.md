# M4: Read-only Candidate Review

## Intent

Show administrators exactly how the currently saved local policy evaluates the current inventory page, without persisting a candidate or changing an account.

## Requirements

### R1 — Policy-applied result
Each displayed user MUST show the score, confidence band, and human-readable reason set produced by the currently loaded policy.

### R2 — Allowlist clarity
An allowlisted account MUST display as protected and MUST not expose stale matching reasons.

### R3 — Read-only boundary
M4 MUST NOT persist candidates, alter users, alter authentication/session state, enqueue work, or perform a network request.

### R4 — Operator disclosure
The page MUST state that scores are explanations rather than proof and that no account has changed.

## Manual acceptance

1. Configure a protected email and a blocked domain in Tools → Kiln Cleanup Policy.
2. Open Tools → Kiln Cleanup and confirm a matching protected user is shown as protected with score zero.
3. Confirm a matching blocked-domain user shows its exact reason and high band.
4. Confirm pagination/filtering do not duplicate users.
5. Confirm no user role, login, session, profile, or database user record changes.
