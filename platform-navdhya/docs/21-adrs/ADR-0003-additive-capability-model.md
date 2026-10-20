# ADR-0003: Capability model is additive, never breaking

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-02](./ADR-0003-additive-capability-model.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

Doc 04 §1–§10 behavior is unchanged: roles grant capabilities; scopes cap
clients; effective authority stays
`scopes ∩ grants ∩ membership` constrained by ABAC + SoD. The capability
upgrade adds: parsed capability metadata (§12), declarative policies
(§13), deterministic DENY precedence (§14), delegations (§15), and a
decision log (§16). No existing permission code, scope, or route
middleware changes.

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
