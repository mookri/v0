# ADR-0017: Delegations are time-boxed, revocable, and SoD-checked

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-16](./ADR-0017-delegation-time-boxed.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Delegation ([authorization & policy §15](../04-security/authorization-and-policy.md)) grants are always bounded by an expiry, carry the
grantor's scope restrictions, are re-checked against SoD at assignment
and at use, and emit dedicated events. Self-delegation and upward
delegation (to a membership with broader grants) are rejected.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
