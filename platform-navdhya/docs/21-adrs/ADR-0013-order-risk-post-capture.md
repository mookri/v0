# ADR-0013: Order risk is a consumer, not a gateway

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-12](./ADR-0013-order-risk-post-capture.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Order risk assessment ([Shopify parity extensions §3.6](../14-domains/sales/sales.md)) annotates orders post-capture and
feeds anomaly detection ([intelligence §3](../17-ai/intelligence.md)); it MUST NOT block payment capture
synchronously in v1 (fraud-blocking rules are a post-v1 policy layer).

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
