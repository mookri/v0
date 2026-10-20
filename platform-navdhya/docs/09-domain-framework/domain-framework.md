# Domain Framework

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 01 module system; doc 06 §8; pasted master plan §30 transaction classes

The normative shape every business module under 14-domains/ MUST follow: module shape and manifest contract, aggregate/command/query/invariant rules, the T0–T4 transaction classes mapped onto outbox/saga usage, cross-domain communication and consistency rules, and the domain document template used by every domain file.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| DF-001 | [Module shape](#df-001-module-shape) | A module is a directory under `app/Modules/<Domain>/` with a fixed shape: |
| DF-002 | [Module manifest contract](#df-002-module-manifest-contract) | `module.json` is decision-complete and schema-validated at boot. The |
| DF-003 | [Aggregate rules](#df-003-aggregate-rules) | Each module owns its aggregates. An aggregate is the consistency boundary |
| DF-004 | [Command rules](#df-004-command-rules) | A command is a named intent (`module.resource.action`) with a payload and |
| DF-005 | [Query rules](#df-005-query-rules) | A query reads within one module's data, filtered by the caller's working |
| DF-006 | [Cross-module reference rule](#df-006-cross-module-reference-rule) | The only legal cross-module data pointer is `ResourceReference` |
| DF-007 | [Event publication rule](#df-007-event-publication-rule) | State changes that other modules may react to MUST be published as domain |
| DF-008 | [Idempotency rule](#df-008-idempotency-rule) | Every mutating endpoint and every command accepts an idempotency key and |
| DF-009 | [Error rule](#df-009-error-rule) | Every tenant-visible failure returns the stable error envelope with a |
| DF-010 | [Versioning rule](#df-010-versioning-rule) | Event schemas and API versions evolve additively. Breaking changes require |
| DF-011 | [Dependency rules (module layers)](#df-011-dependency-rules-module-layers) | Allowed dependencies: module → kernel contracts; module → its own layers; |
| DF-012 | [Extraction readiness (carve-out rules)](#df-012-extraction-readiness-carve-out-rules) | Every module MUST be carve-out-safe: extractable into a standalone service |
| DF-013 | [Transaction classes T0–T4](#df-013-transaction-classes-t0t4) | Every flow is classified by its transactional character, which dictates |
| DF-014 | [Cross-domain communication rules](#df-014-cross-domain-communication-rules) | Synchronous reads go through `Contracts/` read APIs; asynchronous reactions |
| DF-015 | [Consistency classification per flow](#df-015-consistency-classification-per-flow) | Each named flow declares: transaction class (T0–T4), consistency |
| DF-016 | [Domain document template](#df-016-domain-document-template) | Every `14-domains/` file follows the same template so the tree is uniform |

---


The domain framework is the normative shape every business module under
`14-domains/` MUST follow. It restates the module system (migrated from
doc 01), defines the aggregate/command/query/invariant rules, introduces
the T0–T4 transaction classes mapped onto outbox/saga usage, and fixes the
cross-domain communication and consistency rules. Every domain file uses
the template in DF-016.

## DF-001 Module shape

- id: DF-001
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §3
- depends_on: CON-005, K-015
- affects: 14-domains

A module is a directory under `app/Modules/<Domain>/` with a fixed shape:
a `module.json` manifest (K-015), a bounded context, a published
`Contracts/` surface (the only code other modules may import), internal
`Entities/`, `Services/`, `Events/`, `Http/`, and `Database/` layers, and
its own migrations. The manifest declares permissions, events, scopes,
feature flag, navigation, and configuration classes. Internal layout is
private; only `Contracts/` is public.

## DF-002 Module manifest contract

- id: DF-002
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §3.2
- depends_on: DF-001, K-015
- affects: 08-control-plane

`module.json` is decision-complete and schema-validated at boot. The
kernel materializes manifests into the module registry (CTRL-003). A
module whose manifest fails validation does not install. Manifest fields
are additive-only across versions; removing a declared permission or event
is a breaking change requiring a new module major version.

## DF-003 Aggregate rules

- id: DF-003
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §5; doc 07 §5.2
- depends_on: DF-001
- affects: 14-domains

Each module owns its aggregates. An aggregate is the consistency boundary
for a write: one command mutates exactly one aggregate instance, and the
mutation commits in one transaction on the tenant connection. Aggregates
expose their identity via `ResourceReference` (K-012); other modules MUST
NOT hold direct foreign keys to a module's tables.

## DF-004 Command rules

- id: DF-004
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §8
- depends_on: DF-003, K-018
- affects: 14-domains

A command is a named intent (`module.resource.action`) with a payload and
an optional idempotency key. Commands are authorized before execution
(capability + working context), validated, applied to the aggregate, and
then emit events. Blade and API converge on the same command objects; the
transport differs, the authorization path does not.

## DF-005 Query rules

- id: DF-005
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §2
- depends_on: DF-003, K-019
- affects: 14-domains

A query reads within one module's data, filtered by the caller's working
context and capabilities. Queries MUST NOT join across module tables; a
cross-module read either goes through the owning module's `Contracts/` read
API or through a read-model rebuilt from events (LD-10 pattern).

## DF-006 Cross-module reference rule

- id: DF-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §5
- depends_on: DF-005, K-012
- affects: 14-domains, 19-testing

The only legal cross-module data pointer is `ResourceReference`
(module + resource_type + resource_id). No cross-module SQL joins, no
cross-module Eloquent relationships, no direct reads of another module's
tables. `AssertNoCrossModuleJoins` is a standing architecture test.

## DF-007 Event publication rule

- id: DF-007
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2
- depends_on: DF-003, K-020
- affects: 12-events

State changes that other modules may react to MUST be published as domain
events through the transactional outbox. Events are the only asynchronous
integration mechanism; a module MUST NOT call another module's write path
across a process/queue boundary synchronously. Event names and payloads are
declared in the manifest and registered in the event catalog.

## DF-008 Idempotency rule

- id: DF-008
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §12; doc 06 §5
- depends_on: DF-004, K-023
- affects: 14-domains

Every mutating endpoint and every command accepts an idempotency key and
replays the stored response on duplicate. Event consumers MUST be
idempotent (at-least-once delivery is assumed). Idempotency records are
tenant-scoped and expire.

## DF-009 Error rule

- id: DF-009
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §4
- depends_on: DF-004, K-024
- affects: 14-domains

Every tenant-visible failure returns the stable error envelope with a
machine-readable `code`, a human message, and the `request_id`. Modules
declare their error codes in the manifest; undeclared codes are a review
blocker. Errors never leak stack traces or other tenants' data.

## DF-010 Versioning rule

- id: DF-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: CON-006, K-026
- depends_on: DF-007
- affects: 14-domains

Event schemas and API versions evolve additively. Breaking changes require
a new version with an explicit deprecation window. Consumers must tolerate
unknown added fields. Schema versions are recorded on every envelope
(K-021).

## DF-011 Dependency rules (module layers)

- id: DF-011
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §4–5
- depends_on: DF-001
- affects: 02-architecture, 19-testing

Allowed dependencies: module → kernel contracts; module → its own layers;
module A → module B's `Contracts/` only. Prohibited: kernel → module;
module A → module B internals; module → another tenant's data. These are
enforced by Deptrac and the architecture test suite.

## DF-012 Extraction readiness (carve-out rules)

- id: DF-012
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §6
- depends_on: DF-006, DF-007
- affects: 02-architecture

Every module MUST be carve-out-safe: extractable into a standalone service
without rework. This requires no cross-module joins (DF-006), event-based
integration (DF-007), a versioned OpenAPI contract, and no shared mutable
state. The monolith is a deployment choice, not an architectural
commitment.

## DF-013 Transaction classes T0–T4

- id: DF-013
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan §30
- depends_on: DF-003, DF-007
- affects: 12-events, 11-workflow

Every flow is classified by its transactional character, which dictates
outbox/saga usage:

| Class | Character | Mechanism |
|-------|-----------|-----------|
| T0 | Read-only | plain query, no outbox |
| T1 | Single-aggregate write, one DB transaction | aggregate commit + outbox in same tx |
| T2 | Multi-step within one module | local tx per step + outbox; module saga if ordering matters |
| T3 | Cross-module, eventually consistent | outbox events + consumer sagas; compensation defined |
| T4 | Long-running / human-in-the-loop | workflow engine state machine + events |

A task spec MUST declare the transaction class of each flow it introduces.

## DF-014 Cross-domain communication rules

- id: DF-014
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §5
- depends_on: DF-006, DF-007
- affects: 14-domains

Synchronous reads go through `Contracts/` read APIs; asynchronous reactions
go through events; long-running coordination goes through the workflow
engine. A module MUST NOT mix these to bypass a rule (e.g. poll another
module's tables instead of subscribing to its events). The contract map in
each phase summary names every consumer→contract edge.

## DF-015 Consistency classification per flow

- id: DF-015
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan §30
- depends_on: DF-013
- affects: 14-domains

Each named flow declares: transaction class (T0–T4), consistency
requirement (strong within the aggregate; eventual across modules), and its
compensation/reconciliation path when the eventual-consistency window is
violated. Flows without a declared class are a review blocker.

## DF-016 Domain document template

- id: DF-016
- status: APPROVED
- owner: Documentation Owner
- version: 1.0.0
- derived_from: docs 07–10/16/17 module section shape
- depends_on: DF-001
- affects: 14-domains

Every `14-domains/` file follows the same template so the tree is uniform
and machine-checkable: (1) module header + register metadata; (2) bounded
context; (3) core entities; (4) state machine(s) where applicable;
(5) primary flows with declared transaction class (DF-015); (6) event
catalog; (7) API surface; (8) integration points (contracts consumed and
provided); (9) permission summary; (10) verification requirements. Migrated
phase content is reorganized into this shape.
