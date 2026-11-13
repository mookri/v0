# ADR-0074: Payment-Aggregator Posture & Settlement Boundaries

## Status
Accepted (2026-11-11)

## Context
Escrow, TCS/TDS withholding, and third-party marketplace settlement obligations implicate payment-aggregator regulatory boundaries (`G-PA-0`, `G-TAX-0`, `G-BILL-1`).

## Decision
Keep `platform-navdhya` Finance subledger (`SettlementObligation`, `Payout`, `DoubleEntryLedgerService`) strictly separated from SaaS tenant subscription invoices (`booking_id IS NULL` on `BillingController`), mask counterparty tax identifiers in `TaxController` (`tax_purpose_required` fail-closed audit logging), and route statutory tax/PA sign-off through counsel/finance gates rather than embedding unverified constants in code.

