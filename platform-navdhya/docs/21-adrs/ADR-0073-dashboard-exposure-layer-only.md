# ADR-0073: Dashboard as Exposure Layer Only

## Status
Accepted (2026-11-11)

## Context
`apps/dashboard` previously lacked live payout, reconciliation, tax ledger, dispatch, and billing consoles while hardcoded fallback KPIs risked masking backend outages (`A-15`, `A-08`).

## Decision
`apps/dashboard` acts strictly as an exposure BFF over Laravel's existing finance/ops controls (`MakerCheckerPayoutService`, `PaymentReconciliationService`, `DoubleEntryLedgerService` with `AccountingPeriodLock` and contra reversals, `tax_rule_versions`). All `/api/ops/*` handlers enforce fail-closed authentication, `private, no-store` headers, step-up (`RequiresAcr:2`) escalation on 403 step-up challenges, `Idempotency-Key` + `expected_beneficiary_id` anti-substitution on approvals, mandatory compliance `purpose` on tax views, and explicit `503 backend_unavailable` on upstream unavailability.

