# ADR-0010: Quality is an event-driven gate module

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-09](./ADR-0010-quality-event-driven-gates.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Quality ([ERP parity domains §7](../14-domains/manufacturing/quality.md)) never blocks writes synchronously except at declared
inspection gates (GRN inspection flag, work-order gate operations). Gate
evaluation consumes the same workflow engine approval policies ([kernel services](../07-kernel/kernel-services.md)
§4.3); NCR/CAPA are workflow-managed records.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
