# ADR-0014: Import/export is one kernel-backed job surface

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-13](./ADR-0014-single-import-export-surface.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

CSV/XLSX import and export ([Shopify parity extensions §14](../07-kernel/data-tools.md)) is a single shared job
infrastructure (kernel Jobs + Files + idempotency) used by every module;
per-module importers declare column mappings declaratively. No module
ships its own ad-hoc import UI/API mechanics.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
