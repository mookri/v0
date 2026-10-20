# ADR-0015: Theme rendering stays deferred; the storefront API is the contract

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-14](./ADR-0015-headless-storefront-contract.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Native theme/template rendering (Liquid-class) is DEFERRED to v2. The
v1 storefront is contract-complete headless ([commerce domains §1.5](../14-domains/commerce/store-checkout.md) public buyer
API + [Shopify parity extensions §12](../14-domains/commerce/store-checkout.md) feeds/sitemaps); any v1 rendered storefront consumes the
same public API.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
