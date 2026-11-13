# ADR-0048: Independent Partner Appeals, Claim Expiry & Material-Change Reverification

## Status
Accepted (2026-11-11)

## Context
`STATUS_APPEALED` existed without HTTP handlers, and verified claims lacked expiry schedules or targeted revocation on material changes.

## Decision
Implement `AppealService` enforcing independent appeal adjudicators, automate claim expiration via `verification:expire` using `ExpiryPolicyResolver`, and selectively revoke affected claims via `MaterialChangeReverificationListener`.

