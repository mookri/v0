# ADR-0007: Markets are a pricing/display overlay, never a stock or money truth

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-06](./ADR-0007-markets-pricing-overlay.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Markets ([Shopify parity extensions §2](../14-domains/commerce/markets.md)) compose store + currency + duty + tax-display rules.
They read FX rates from finance ([finance domains §1.5](../14-domains/finance/gl.md)) and never store their own
prices of record; market prices are derived (base price × FX × rounding
rule) and cached as projections.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
