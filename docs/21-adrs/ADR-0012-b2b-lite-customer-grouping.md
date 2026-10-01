# ADR-0012: B2B-lite reuses customers; companies are grouping objects

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-11](./ADR-0012-b2b-lite-customer-grouping.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

B2B ([Shopify parity extensions §15](../14-domains/crm/customers.md)) does not create a parallel counterparty model. A
`company` groups customer records with shared payment terms, tax
exemption, and buyer users. Full B2B (price lists per company, net terms
workflows beyond v1) is deferred with the seam recorded (§3.3).

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
