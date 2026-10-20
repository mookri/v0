# ADR-0016: Authorization policies are tenant data with platform defaults

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-15](./ADR-0016-policies-tenant-data.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Policies ([authorization & policy §13](../04-security/authorization-and-policy.md)) follow the SoD pattern: platform-default policies
(`tenant_id NULL`) are overridable but not silently removable; tenant
policies are audited, versioned, and evaluated at decision time with a
bounded condition grammar. Free-form expression languages are banned.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
