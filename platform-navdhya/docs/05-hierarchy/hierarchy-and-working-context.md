# Typed Hierarchies & Working Context

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** pasted master plan (hierarchy & Working Context); doc 04 §4.2 org tree

The major new canonical spec. Navdhya generalizes the single legacy org tree into multiple coexisting typed hierarchies, and introduces the Working Context — the principal's current operating node — governed by the hard invariant WorkingContext ⊆ MaximumAuthority. The existing org tree migrates backward-compatibly to the `organization` hierarchy type.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| HIER-001 | [Typed hierarchies — concept](#hier-001-typed-hierarchies--concept) | A **hierarchy** is a typed, named tree of nodes used both to model |
| HIER-002 | [Built-in hierarchy types](#hier-002-built-in-hierarchy-types) | v1 ships these built-in types; tenants may add `custom`: |
| HIER-003 | [Nodes and relationships](#hier-003-nodes-and-relationships) | A **node** belongs to exactly one hierarchy and carries `node_key`, |
| HIER-004 | [Authority roots](#hier-004-authority-roots) | An **authority root** is the node from which a principal's grant descends. |
| HIER-005 | [Scope modes (mechanisms)](#hier-005-scope-modes-mechanisms) | Scope modes are **mechanisms**, not business meanings; each domain defines |
| HIER-006 | [Working Context — concept](#hier-006-working-context--concept) | The **Working Context** is the principal's current operating node — |
| HIER-007 | [Working Context resolution](#hier-007-working-context-resolution) | Working Context is resolved server-side from the session/API context into |
| HIER-008 | [Working Context ⊆ MaximumAuthority (hard invariant)](#hier-008-working-context--maximumauthority-hard-invariant) | Server-side invariant, enforced on every authorization decision: |
| HIER-009 | [Context switching](#hier-009-context-switching) | A principal may switch Working Context to any node within their |
| HIER-010 | [Context stack](#hier-010-context-stack) | The Working Context is a stack, not a single slot: switching pushes a |
| HIER-011 | [Forged-context denial (named test class)](#hier-011-forged-context-denial-named-test-class) | A request that presents a Working Context not derived from the server-side |
| HIER-012 | [Multi-hierarchy resolution](#hier-012-multi-hierarchy-resolution) | When a domain needs a scope, it names the hierarchy type it cares about. |
| HIER-013 | [Versioning and change rules](#hier-013-versioning-and-change-rules) | Hierarchy structure is versioned. Node/relationship mutations emit events |
| HIER-014 | [Context security summary](#hier-014-context-security-summary) | Context security is the conjunction of: server-side resolution (HIER-007), |
| HIER-015 | [Backward-compatible org-tree migration](#hier-015-backward-compatible-org-tree-migration) | The existing org tree migrates to a hierarchy of type `organization`. |
| HIER-016 | [Hierarchy verification](#hier-016-hierarchy-verification) | Standing suites: (a) cycle rejection on write; (b) `WorkingContext ⊆ |

---


## HIER-001 Typed hierarchies — concept

- id: HIER-001
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan; doc 04 §4.2 org tree
- depends_on: CON-002, SEC-005
- affects: 04-security, 08-control-plane, 14-domains

A **hierarchy** is a typed, named tree of nodes used both to model
structure (org chart, territory map, warehouse network) and to anchor
authorization. Navdhya generalizes the single legacy org tree into
multiple coexisting typed hierarchies per tenant. Each hierarchy has a
`type`, a `name`, versioned node/relationship data, and zero or more
**authority roots**.

## HIER-002 Built-in hierarchy types

- id: HIER-002
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan §hierarchy
- depends_on: HIER-001
- affects: 08-control-plane

v1 ships these built-in types; tenants may add `custom`:

| Type | Purpose | Typical authority use |
|------|---------|-----------------------|
| `organization` | Legal/reporting org (the legacy org tree) | default authority root |
| `management` | Manager/report supervision | approval routing |
| `sales_territory` | Regional/account coverage | sales data scope |
| `warehouse` | Physical/logical fulfillment network | stock scope |
| `legal_entity` | Accounting/reporting entity | finance scope |
| `project` | Delivery/work breakdown structure | project data scope |
| `custom` | Tenant-defined | as configured |

The existing single org tree becomes the `organization`-type hierarchy —
a backward-compatible generalization, not a break.

## HIER-003 Nodes and relationships

- id: HIER-003
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-001
- affects: 13-data

A **node** belongs to exactly one hierarchy and carries `node_key`,
display `name`, `type`-specific attributes, and lifecycle state. A
**relationship** is a directed parent→child edge within one hierarchy.
The node/relationship schema is kernel-owned (`hierarchies`,
`hierarchy_nodes`, `hierarchy_edges` on the tenant connection) and
registered as kernel contracts (K-007). Cycles are prohibited and
enforced at write time.

## HIER-004 Authority roots

- id: HIER-004
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-003, SEC-009
- affects: 04-security

An **authority root** is the node from which a principal's grant descends.
An Authority is the pair `(hierarchy node, scope mode)`. A principal may
hold multiple authorities across hierarchies. Effective authority is the
union of grants intersected with token scopes and membership, per the
authorization chain ([authorization register](../04-security/authorization-and-policy.md)).

## HIER-005 Scope modes (mechanisms)

- id: HIER-005
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-004
- affects: 04-security, 14-domains

Scope modes are **mechanisms**, not business meanings; each domain defines
what a mode means for its data. The four modes:

| Mode | Mechanism |
|------|-----------|
| `SELF` | Only the node itself. |
| `NODE` | The named node. |
| `DIRECT_CHILDREN` | The node plus its immediate children. |
| `SUBTREE` | The node and all descendants. |

A domain maps a mode onto its resource filter (e.g. sales maps `SUBTREE`
on a territory node to "orders in that territory and below"). Domains MUST
NOT invent new modes; they interpret existing ones.

## HIER-006 Working Context — concept

- id: HIER-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-004
- affects: 10-experience, 04-security

The **Working Context** is the principal's current operating node —
"where I am working right now." It is persisted per session/API, is
switchable, and is the default scope applied to operations that do not
name an explicit node. Working Context is a convenience and a UX concept;
it is **never** a grant of authority.

## HIER-007 Working Context resolution

- id: HIER-007
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-006
- affects: 07-kernel

Working Context is resolved server-side from the session/API context into
a concrete `(hierarchy_id, node_id, scope_mode)`. If a request carries an
explicit node, that node is used; otherwise the persisted Working Context
applies; if neither is present and the operation requires a node, the
request fails closed. Resolution is deterministic and audited.

## HIER-008 Working Context ⊆ MaximumAuthority (hard invariant)

- id: HIER-008
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan (named invariant)
- depends_on: HIER-007, HIER-004
- affects: 04-security, 19-testing

Server-side invariant, enforced on every authorization decision:

`WorkingContext ⊆ MaximumAuthority`

The Working Context can only ever narrow what the principal already holds.
A client-supplied context that would expand authority beyond the
principal's grants is rejected (403), and the rejection is logged in the
authorization decision log (SEC-016). This is a named test class.

## HIER-009 Context switching

- id: HIER-009
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-006
- affects: 10-experience

A principal may switch Working Context to any node within their
MaximumAuthority. Switching persists the new node, emits
`platform.context.switched`, and is audited. Switching to a node outside
authority is denied. The switcher UX contract feeds
[experience architecture](../10-experience/experience-architecture.md)
(EXP-011…013).

## HIER-010 Context stack

- id: HIER-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-009
- affects: 10-experience

The Working Context is a stack, not a single slot: switching pushes a
frame, and the principal can pop back to a prior node. The stack is
bounded and session-scoped. Each frame records the node, hierarchy, scope
mode, and the switch timestamp.

## HIER-011 Forged-context denial (named test class)

- id: HIER-011
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-008
- affects: 19-testing

A request that presents a Working Context not derived from the server-side
session (forged, replayed from another session, or referencing a node the
principal cannot see) MUST be denied, and the denial MUST be recorded as a
`forged_context` class in the decision log. This is a standing negative
suite.

## HIER-012 Multi-hierarchy resolution

- id: HIER-012
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-002
- affects: 14-domains

When a domain needs a scope, it names the hierarchy type it cares about.
Resolution selects the node for that type from the principal's authorities.
If a principal has authorities in multiple hierarchies of the same type,
the domain's mapping rule selects (or the Working Context disambiguates).
Ambiguity that cannot be resolved deterministically fails closed.

## HIER-013 Versioning and change rules

- id: HIER-013
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-003
- affects: 13-data, 12-events

Hierarchy structure is versioned. Node/relationship mutations emit events
(`platform.hierarchy.*`), are audited, and take effect at a defined
effective time. Structural reparenting preserves audit lineage. Grants
that reference a removed node degrade to their nearest surviving ancestor
or are suspended, per the grant's rule — never silently widened.

## HIER-014 Context security summary

- id: HIER-014
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-008, HIER-011
- affects: 04-security

Context security is the conjunction of: server-side resolution (HIER-007),
the subset invariant (HIER-008), forged-context denial (HIER-011), and
audit of every switch (HIER-009). No client-supplied context value is
trusted without re-validation against the principal's MaximumAuthority.

## HIER-015 Backward-compatible org-tree migration

- id: HIER-015
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan; doc 04 §4.2
- depends_on: HIER-002
- affects: 06-tenancy, 13-data

The existing org tree migrates to a hierarchy of type `organization`.
Existing `org_id` columns become references to `organization`-type
hierarchy nodes (a mapping table preserves old id → node id). Existing
org-scope ABAC behavior ([SEC-010](../04-security/authorization-and-policy.md#sec-010-scopes--permissions-mapping)) is preserved: an org-scope grant
maps to `(organization node, SUBTREE)` by default. The migration is
additive and reversible until cutover is declared.

## HIER-016 Hierarchy verification

- id: HIER-016
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: HIER-001…HIER-015
- affects: 19-testing

Standing suites: (a) cycle rejection on write; (b) `WorkingContext ⊆
MaximumAuthority` holds under random context injection; (c) forged-context
denial returns 403 and logs `forged_context`; (d) scope-mode mapping is
deterministic per domain; (e) org-tree migration preserves existing
org-scope decisions (regression). Each maps to a named test in
[testing strategy](../19-testing/testing-strategy.md).
