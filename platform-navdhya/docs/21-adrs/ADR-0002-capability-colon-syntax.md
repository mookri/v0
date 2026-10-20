# ADR-0002: Capability identifier syntax stays `<module>:<resource>:<action>`

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

Locked decision [LD-01](./ADR-0002-capability-colon-syntax.md) from the gap analysis (planning/03 §2), adopted verbatim during the documentation tree migration. The full rationale is preserved below as recorded in the gap analysis.

## Decision

The reviewed authorization analysis recommends dot notation
(`sales.order.approve`). Navdhya **retains the colon form** as the
canonical capability identifier because: (a) every manifest, route
middleware, scope string, SoD rule, and OpenAPI `x-permission` in
docs 01–15 already uses it; (b) OAuth scopes mirror permission codes
1:1 ([gap analysis](../01-product/gap-analysis.md), [authorization & policy §6](../04-security/authorization-and-policy.md)) and changing separators would force an API
version bump. The *substance* of the recommendation — structured
capability metadata, business actions over CRUD, capability/policy
separation, scopes and conditions as separate objects, explicit DENY,
delegation, decision logging — is adopted in full ([authorization & policy §12–§16](../04-security/authorization-and-policy.md)).

## Consequences

This decision is normative for the registers that implement it. See the gap analysis register for the cross-decision context and the affected architecture documents.
