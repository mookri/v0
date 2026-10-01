# ADR-0008: Statutory adapters are channel adapters behind feature flags

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-07](./ADR-0008-statutory-channel-adapters.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

GST e-invoicing, e-way bills, GSTR exports, PF/ESI filing ([ERP parity domains §9](../15-integrations/connectors/india-statutory.md)) are
implemented as **channel adapters** ([API platform §10](../15-integrations/integration-framework.md)) + report exporters,
enabled per tenant by the `module.india_statutory` feature flag. No
statutory logic is hard-coded into the tax engine core; the tax engine
([finance domains §2](../14-domains/finance/tax.md)) stays jurisdiction-generic. Provider endpoints (NIC IRN,
portal APIs) are configuration, not code dependencies.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
