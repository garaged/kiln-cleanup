# M2: Explainable Detection and Review

## Intent

Provide deterministic, local, reviewable account signals without presenting a classifier as certainty or exposing any account mutation.

## Requirements

### R1 — Explainable result

Every non-zero result MUST contain stable rule IDs, points, and human-readable reasons. It MUST retain the policy version used for evaluation.

### R2 — Allowlist precedence

An explicit protected user ID or protected email MUST yield score zero and no reasons, even when other rules match.

### R3 — Confidence bands

Scores 0–29 are informational, 30–69 require review, and 70–100 are only eligible for a future quarantine workflow. M2 MUST NOT implement that workflow.

### R4 — Deterministic local rules

M2 MUST run without network access, external reputation services, machine learning, telemetry, or an undisclosed rule source.

### R5 — Bounded built-ins

The initial built-ins are exact blocked email-domain matches and generated identifier patterns. Generated-pattern signals MUST remain review-only unless corroborated by another rule.

### R6 — No mutation

M2 MUST NOT create, modify, quarantine, delete, authenticate, or otherwise change a WordPress user account.
