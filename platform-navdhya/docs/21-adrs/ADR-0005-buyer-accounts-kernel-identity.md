# ADR-0005: Buyer accounts reuse kernel identity

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-04](./ADR-0005-buyer-accounts-kernel-identity.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Customer/buyer accounts ([Shopify parity extensions §5](../14-domains/crm/customers.md)) are platform users ([gap analysis](../01-product/gap-analysis.md)) with a
buyer profile attached; no second user system. Buyer auth uses the same
OAuth authorization server (PKCE for the future storefront SPA, session
for Blade-rendered account pages in v1). Buyer tokens MUST NOT carry
admin scopes (client profile restriction, [identity & token platform §2.3](../04-security/identity-and-token-platform.md)).

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
