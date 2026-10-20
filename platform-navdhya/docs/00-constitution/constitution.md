# Navdhya Constitution

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** pasted master plan §5; architecture README principles; doc 14 §12 DoD; planning/04 governance

The constitution is the root normative document of the Navdhya documentation tree. It records the immutable architectural principles, the documentation lifecycle and metadata schema, change-impact classes, the definition of done, and the governance rules that bind every other document. Every ID below is a constitutional provision.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| CON-001 | [Supremacy & scope](#con-001-supremacy--scope) | This constitution is the root normative document of the Navdhya |
| CON-002 | [Architectural principles](#con-002-architectural-principles) | The platform is governed by seven immutable principles (full statement in |
| CON-003 | [Fail-closed posture](#con-003-fail-closed-posture) | Unresolved tenant, missing permission, unknown scope, invalid audience, |
| CON-004 | [Everything is auditable](#con-004-everything-is-auditable) | Every state-changing operation produces an audit record with actor, |
| CON-005 | [Manifest-driven platform](#con-005-manifest-driven-platform) | Permissions, scopes, events, navigation, and feature flags are declared |
| CON-006 | [Additive evolution](#con-006-additive-evolution) | API versions and event schemas evolve additively; breaking changes |
| CON-007 | [Document lifecycle states](#con-007-document-lifecycle-states) | Every document and every registered ID carries a lifecycle state: |
| CON-008 | [Document metadata schema](#con-008-document-metadata-schema) | Each registered ID section carries a metadata block with, at minimum: |
| CON-009 | [Change-impact classes](#con-009-change-impact-classes) | Every change to a normative document or contract is classified: |
| CON-010 | [Change management](#con-010-change-management) | Normative changes land as documented amendments: the change-impact class |
| CON-011 | [Definition of Done](#con-011-definition-of-done) | A unit of work is done only when all of the following hold (full |
| CON-012 | [Documentation governance](#con-012-documentation-governance) | Three governance rules are absolute: |

---


## CON-001 Supremacy & scope

- id: CON-001
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: —
- affects: every document in this tree

This constitution is the root normative document of the Navdhya
documentation tree. Every other document derives its authority from, and
MUST NOT contradict, the principles and governance rules recorded here.
Where a lower document conflicts with this one, this document wins and
the conflict is a review blocker.

The constitution binds three things: (a) the architecture and its
evolution, (b) the documentation set itself (structure, IDs, lifecycle,
cross-references), and (c) the definition of "done" for any change to
either.

## CON-002 Architectural principles

- id: CON-002
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: CON-001
- affects: 02-architecture, 09-domain-framework

The platform is governed by seven immutable principles (full statement in
[ARCH-001](../02-architecture/architecture-master.md#arch-001-layered-architecture)):

1. Domains depend on the platform. The platform never depends on domains.
2. Integration via contracts and events only.
3. API-first, extraction-ready.
4. Fail closed.
5. Everything is auditable.
6. Manifest-driven platform.
7. Additive evolution.

No feature, phase, or parity task may suspend a principle. A change that
requires relaxing a principle is a constitutional change (CON-010).

## CON-003 Fail-closed posture

- id: CON-003
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: CON-002
- affects: 04-security, 06-tenancy

Unresolved tenant, missing permission, unknown scope, invalid audience,
or forged working context all result in denial (401/403), never in
degraded permissive behavior. This is a hard invariant and a named test
class ([testing strategy](../19-testing/testing-strategy.md)).

## CON-004 Everything is auditable

- id: CON-004
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: CON-002
- affects: 07-kernel, 12-events

Every state-changing operation produces an audit record with actor,
tenant, request id, and before/after state. Audit coverage is part of the
definition of done (CON-011), not an afterthought.

## CON-005 Manifest-driven platform

- id: CON-005
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: CON-002
- affects: 08-control-plane, 09-domain-framework

Permissions, scopes, events, navigation, and feature flags are declared
in module manifests; the kernel is the single source of truth that
materializes them. Undeclared capabilities MUST NOT be enforced or
exposed.

## CON-006 Additive evolution

- id: CON-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: CON-002
- affects: 07-kernel/api-platform, 12-events

API versions and event schemas evolve additively; breaking changes
require a new version with an explicit deprecation window. This applies
to public APIs, webhooks, and inter-module contracts alike.

## CON-007 Document lifecycle states

- id: CON-007
- status: APPROVED
- owner: Documentation Owner
- version: 1.0.0
- depends_on: CON-001
- affects: every document

Every document and every registered ID carries a lifecycle state:

| State | Meaning |
|-------|---------|
| `DRAFT` | Under authorship; not yet authoritative. |
| `APPROVED` | Reviewed and accepted as normative. |
| `ACTIVE` | Approved and currently in force. |
| `SUPERSEDED` | Replaced by a named successor; retained for history. |
| `RETIRED` | No longer relevant; archived. |

Migrated (previously approved) content is `ACTIVE`. Newly authored
canonical content is `APPROVED` until first implementation confirms it,
then promoted to `ACTIVE`.

## CON-008 Document metadata schema

- id: CON-008
- status: APPROVED
- owner: Documentation Owner
- version: 1.0.0
- depends_on: CON-007
- affects: every document

Each registered ID section carries a metadata block with, at minimum:
`id`, `status`, `owner`, `version`, `depends_on`, `affects`. Newly
authored content additionally records `derived_from` pointing at the
source plan or prior architecture document. No document claims runtime
truth: documentation records intent and contract, not live state.

## CON-009 Change-impact classes

- id: CON-009
- status: APPROVED
- owner: Documentation Owner
- version: 1.0.0
- depends_on: CON-008
- affects: CON-010

Every change to a normative document or contract is classified:

| Class | Scope | Approval |
|-------|-------|----------|
| C0 | Typo / formatting, no semantic change | self-merge |
| C1 | Clarification, no contract change | doc owner |
| C2 | Contract-affecting within one concern | doc owner + module owner |
| C3 | Cross-contract / cross-module | Platform Architect |
| C4 | Constitutional / principle change | Platform Architect + sign-off record |

## CON-010 Change management

- id: CON-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: CON-009
- affects: every document

Normative changes land as documented amendments: the change-impact class
is stated, affected IDs are listed, and dependent documents are updated
in the same change. Silent edits to `ACTIVE` content are prohibited.

## CON-011 Definition of Done

- id: CON-011
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- depends_on: CON-004
- affects: 19-testing, specs/

A unit of work is done only when all of the following hold (full
statement in [testing strategy — Definition of Done](../19-testing/testing-strategy.md#definition-of-done)):

1. All acceptance criteria pass in CI.
2. Lint, static analysis, and architecture gates are green.
3. Manifest reflects any permission/scope/event/navigation change.
4. OpenAPI fragment + contract tests green for changed endpoints.
5. Audit coverage present for every state change introduced.
6. No new cross-module joins or non-contract imports.
7. Documentation deltas landed; doc drift is a review blocker.

## CON-012 Documentation governance

- id: CON-012
- status: APPROVED
- owner: Documentation Owner
- version: 1.0.0
- depends_on: CON-007
- affects: every document

Three governance rules are absolute:

1. **No isolated nice-to-have docs.** Every document belongs to a named
   concern directory and registers at least one ID, or it is a runbook /
   evidence artifact referenced from a register.
2. **Register completeness.** Every ID family is fully numbered with no
   gaps; gaps are a CI gate failure.
3. **Cross-reference integrity.** Every internal link resolves; the
   legacy `doc NN §x` citation style is prohibited outside the README
   mapping table.
