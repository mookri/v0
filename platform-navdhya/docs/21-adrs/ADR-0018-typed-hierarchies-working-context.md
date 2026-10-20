# ADR-0018: Typed hierarchies and Working Context generalize the org tree

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

The v1 authorization model ([authorization & policy §4.2](../04-security/authorization-and-policy.md)) has a single org tree used for ABAC scoping. The master plan's enterprise authorization semantics require multiple coexisting structure types (organization, management, sales_territory, warehouse, legal_entity, project, custom), authority roots anchored to hierarchy nodes, scope modes (SELF|NODE|DIRECT_CHILDREN|SUBTREE), and a Working Context — the principal's current operating node — governed by a hard invariant.

## Decision

Adopt typed hierarchies and Working Context as specified in the hierarchy register (HIER-001…016). The existing single org tree becomes the `organization`-type hierarchy; existing `org_id` columns become references to `organization`-type hierarchy nodes via a mapping table (backward-compatible, additive, reversible until cutover). The invariant `WorkingContext ⊆ MaximumAuthority` is enforced server-side on every authorization decision; client-supplied context never expands authority; forged-context denial is a named test class.

## Consequences

Authority becomes the pair (hierarchy node, scope mode); scope modes are mechanisms whose business meaning is defined per domain. Phase 0K kernel tasks (T-0K02-*) implement schema, resolution, and the migration. Existing org-scope grants map to (organization node, SUBTREE) by default, preserving current decisions.
