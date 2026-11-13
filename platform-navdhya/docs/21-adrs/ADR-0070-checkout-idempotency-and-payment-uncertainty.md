# ADR-0070: Checkout Idempotency & Payment Uncertainty

## Status
Accepted (2026-11-11)

## Context
Timed-out checkout or payment confirmation responses previously risked mapping transport uncertainty (`unknown` / `504`) into `FAILED`, creating double-charge or orphaned-hold risks (`A-04`, `A-05`).

## Decision
Propagate deterministic `Idempotency-Key` headers through BFF routes, transition payments to `FAILED` strictly upon authoritative gateway/backend denial, render explicit `UNKNOWN` / reconciling states backed by `PaymentReconciliationService`, and cover webhook replay/timeout paths in E2E and backend tests.

