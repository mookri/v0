# ADR-0009: Serial/lot tracking lives in inventory, exposed by contract

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-08](./ADR-0009-serial-lot-inventory-contracts.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Serial & batch ledgers ([ERP parity domains §8](../14-domains/inventory/inventory.md)) extend the inventory module's
movement model (every movement may carry serial/batch refs); commerce and
service consume via `StockAvailability`/new `TraceQuery` contracts. No
other module stores serial state.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
