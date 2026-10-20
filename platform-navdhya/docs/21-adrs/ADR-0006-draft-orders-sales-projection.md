# ADR-0006: Draft orders are a sales-module projection, not a new order model

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-05](./ADR-0006-draft-orders-sales-projection.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

`order_ops` ([Shopify parity extensions §3](../14-domains/sales/sales.md)) creates `sales_orders` with `status=draft`
(source `draft_order`); there is exactly one order aggregate in the
platform. Draft-specific behavior (invoice link, deposit, payment terms)
lives in `order_ops` tables referencing the sales order id.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
