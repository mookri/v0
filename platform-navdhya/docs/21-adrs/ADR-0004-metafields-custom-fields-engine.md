# ADR-0004: Metafields are the v1 preview of custom fields

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-03](./ADR-0004-metafields-custom-fields-engine.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Shopify-style metafields ([Shopify parity extensions §6](../14-domains/commerce/content.md)) are implemented on the same storage
model as Phase 5 custom fields ([ecosystem §5.2](../18-extensions/ecosystem.md)) with a fixed definition
registry; Phase 5 promotes the engine to tenant-defined custom
fields/objects. Two implementations are banned: [Shopify parity extensions §6](../14-domains/commerce/content.md) MUST be the
[ecosystem §5](../18-extensions/ecosystem.md) engine with a restricted surface.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
