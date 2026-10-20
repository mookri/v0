# ADR-0011: CRM read-models are built from events, not cross-module queries

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-10](./ADR-0011-crm-read-models-from-events.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Customer 360 and pipeline forecasting ([ERP parity domains §2.8](../14-domains/crm/crm.md)) are read-models
refreshed from `customers.*`, `sales.*`, `service.*` events and contract
lookups, honoring the no-cross-module-join rule ([domain framework §5](../02-architecture/architecture-master.md)).

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
