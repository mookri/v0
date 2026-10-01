# Control Plane

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** pasted master plan §feature state/blueprint/provisioning; doc 02 §3; doc 01 §3.2

The control plane turns product intent (plans, entitlements, blueprints) into observed tenant reality (installed, licensed, configured, activated, healthy modules). It is the materializer behind the manifest-driven principle: it reads desired state, observes actual state, and reconciles the difference.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| CTRL-001 | [Control plane — concept](#ctrl-001-control-plane--concept) | The **control plane** is the platform subsystem that turns product intent |
| CTRL-002 | [Plans and entitlements](#ctrl-002-plans-and-entitlements) | A **plan** is a named, versioned bundle of **entitlements**. An |
| CTRL-003 | [Module registry and manifest](#ctrl-003-module-registry-and-manifest) | The **module registry** is the kernel-materialized catalog of every module |
| CTRL-004 | [Feature state model](#ctrl-004-feature-state-model) | Each module in a tenant passes through a normative feature-state |
| CTRL-005 | [Tenant blueprint — desired state](#ctrl-005-tenant-blueprint--desired-state) | A **tenant blueprint** is the desired-state document for one tenant: the |
| CTRL-006 | [Blueprint validation](#ctrl-006-blueprint-validation) | Before a blueprint is accepted it is validated: module codes exist in the |
| CTRL-007 | [Blueprint versioning](#ctrl-007-blueprint-versioning) | Every accepted blueprint produces an immutable version. The tenant's |
| CTRL-008 | [Desired vs observed state](#ctrl-008-desired-vs-observed-state) | The control plane continuously compares **desired** (blueprint) against |
| CTRL-009 | [Reconciliation loop](#ctrl-009-reconciliation-loop) | The reconcile loop is the engine that drives observed toward desired. It |
| CTRL-010 | [Provisioning pipeline](#ctrl-010-provisioning-pipeline) | Provisioning a new tenant is a pipeline: create tenant record → resolve |
| CTRL-011 | [Provisioning idempotency](#ctrl-011-provisioning-idempotency) | Every provisioning stage is idempotent: re-running a stage that already |
| CTRL-012 | [Provisioning failure and recovery](#ctrl-012-provisioning-failure-and-recovery) | A failed provisioning run leaves the tenant in a named partial state, not |
| CTRL-013 | [Configuration precedence](#ctrl-013-configuration-precedence) | Configuration resolves through a fixed precedence chain: |
| CTRL-014 | [Configuration class declaration](#ctrl-014-configuration-class-declaration) | Every module manifest declares its configuration classes with: key, |
| CTRL-015 | [Module activation gating](#ctrl-015-module-activation-gating) | Routes, navigation entries, and public surfaces for a module are exposed |
| CTRL-016 | [Health contract](#ctrl-016-health-contract) | Each module declares health checks consumed by the control plane. Module |
| CTRL-017 | [Plan change impact](#ctrl-017-plan-change-impact) | Changing a plan's entitlements is a C2-or-higher change (CON-009). Removing |
| CTRL-018 | [Reconciliation verification](#ctrl-018-reconciliation-verification) | Standing suites: (a) provisioning is idempotent (double-run is a no-op); |
| CTRL-019 | [Control-plane events](#ctrl-019-control-plane-events) | The control plane emits kernel-namespace events: `platform.blueprint.*`, |
| CTRL-020 | [Control-plane authorization](#ctrl-020-control-plane-authorization) | Control-plane mutations (plan edits, blueprint changes, provisioning, |
| CTRL-021 | [Observability of control plane](#ctrl-021-observability-of-control-plane) | The control plane exposes: desired-vs-observed diff per tenant, reconcile |

---


## CTRL-001 Control plane — concept

- id: CTRL-001
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan; doc 02 §3 plans
- depends_on: CON-005, PROD-008
- affects: 08-control-plane, 09-domain-framework

The **control plane** is the platform subsystem that turns product intent
(plans, entitlements, blueprints) into observed tenant reality (installed,
licensed, configured, activated, healthy modules). It is the materializer
behind the manifest-driven principle (CON-005): it reads desired state,
observes actual state, and reconciles the difference.

## CTRL-002 Plans and entitlements

- id: CTRL-002
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 02 §3 plans + feature flags
- depends_on: PROD-008
- affects: 08-control-plane

A **plan** is a named, versioned bundle of **entitlements**. An
entitlement grants a module to a plan with a license tier. Plans are
central (platform) data; entitlements are the join between plans and
module codes. Changing a plan's entitlements is a versioned, audited
operation and never retroactively mutates an already-materialized tenant
blueprint without an explicit reconcile.

## CTRL-003 Module registry and manifest

- id: CTRL-003
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §3.2 module.json; K-015
- depends_on: CTRL-001
- affects: 09-domain-framework

The **module registry** is the kernel-materialized catalog of every module
manifest (`module.json`): code, version, phase, dependencies, permissions,
events, scopes, feature flag, and navigation. The registry is the single
source of truth for what a module declares; the control plane consumes it
to compute what a tenant may install. Manifest contract is K-015 in the
[kernel contracts register](../07-kernel/kernel-contracts.md).

## CTRL-004 Feature state model

- id: CTRL-004
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan §feature state
- depends_on: CTRL-003
- affects: 08-control-plane

Each module in a tenant passes through a normative feature-state
lifecycle:

| State | Meaning |
|-------|---------|
| `installed` | Code + schema present for the tenant. |
| `licensed` | Entitled by the tenant's plan. |
| `configured` | Required configuration supplied. |
| `activated` | Feature flag on; live to users. |
| `healthy` | Passing its health checks. |

A module is usable only when it reaches `activated` and reports `healthy`.
Each transition is audited and observable.

## CTRL-005 Tenant blueprint — desired state

- id: CTRL-005
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-002, CTRL-003
- affects: 06-tenancy

A **tenant blueprint** is the desired-state document for one tenant: the
resolved set of modules (from plan entitlements), per-module configuration
classes, feature-flag intent, and hierarchy/working-context defaults. The
blueprint is declarative and versioned; it says what *should* be true, not
what is.

## CTRL-006 Blueprint validation

- id: CTRL-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-005
- affects: 08-control-plane

Before a blueprint is accepted it is validated: module codes exist in the
registry; module dependencies are satisfied; configuration values pass
each module's declared schema; no module is entitled that the plan does
not grant. Invalid blueprints are rejected with a stable error code and
never partially applied.

## CTRL-007 Blueprint versioning

- id: CTRL-007
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-005
- affects: 08-control-plane

Every accepted blueprint produces an immutable version. The tenant's
current target is a pointer to a blueprint version; rollbacks repoint to a
prior version. Blueprint changes emit `platform.blueprint.*` events and
are audited with actor and reason.

## CTRL-008 Desired vs observed state

- id: CTRL-008
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-005
- affects: 20-sre

The control plane continuously compares **desired** (blueprint) against
**observed** (actual feature state, health, configuration drift). The
difference is the reconcile worklist. Drift — observed state that diverges
from desired without a blueprint change — is flagged, alerted, and either
auto-reconciled or escalated per the drift policy.

## CTRL-009 Reconciliation loop

- id: CTRL-009
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-008
- affects: 20-sre

The reconcile loop is the engine that drives observed toward desired. It
is idempotent (running twice has no additional effect), convergent (each
pass reduces the diff), and bounded (a failing module does not block
reconcile of others). Reconcile actions are audited and emit
`platform.reconcile.*` events.

## CTRL-010 Provisioning pipeline

- id: CTRL-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-009
- affects: 22-runbooks/provisioning

Provisioning a new tenant is a pipeline: create tenant record → resolve
plan to blueprint → validate → install entitled modules (schema + seed
data) → apply configuration → activate flags → verify health. Each stage
is resumable; the pipeline records its progress so a failed provision can
be continued rather than restarted.

## CTRL-011 Provisioning idempotency

- id: CTRL-011
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-010
- affects: 22-runbooks/provisioning

Every provisioning stage is idempotent: re-running a stage that already
succeeded is a no-op. Idempotency keys and stage checkpoints prevent
duplicate schema application, double seeding, or duplicate flag
activation. This is a named acceptance condition for the provisioning
runbook.

## CTRL-012 Provisioning failure and recovery

- id: CTRL-012
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-010
- affects: 22-runbooks/provisioning

A failed provisioning run leaves the tenant in a named partial state, not
an undefined one. Recovery either resumes from the last checkpoint or
compensates (rolls back applied stages) per the failure class. Partial
tenants are never licensed for production traffic until health passes.

## CTRL-013 Configuration precedence

- id: CTRL-013
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan §precedence
- depends_on: CTRL-005, PROD-010
- affects: 07-kernel

Configuration resolves through a fixed precedence chain:

`platform → plan → tenant → org → module → user`

Later entries override earlier ones **only within the configuration
class's declared valid precedence**. Each configuration class declares how
far down the chain it may be overridden; a class that stops at `tenant`
cannot be overridden at `org`/`module`/`user`. Precedence resolution is
deterministic and audited.

## CTRL-014 Configuration class declaration

- id: CTRL-014
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-013
- affects: 09-domain-framework

Every module manifest declares its configuration classes with: key,
schema, default, sensitivity, and the maximum precedence depth at which it
may be overridden. Undeclared configuration keys are rejected. This keeps
configuration surface manifest-driven (CON-005).

## CTRL-015 Module activation gating

- id: CTRL-015
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan; PROD-011
- depends_on: CTRL-004
- affects: 10-experience

Routes, navigation entries, and public surfaces for a module are exposed
only when the module is `licensed` and `activated` for the tenant. A
request to an unlicensed module is denied with a stable code (fail closed,
CON-003). Navigation reflects entitlement; it never grants it.

## CTRL-016 Health contract

- id: CTRL-016
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00 §6.5; doc 05 §11
- depends_on: CTRL-004
- affects: 20-sre

Each module declares health checks consumed by the control plane. Module
health rolls up into tenant health and platform health. Health is
observable via `/up` (liveness) and `/ready` (dependency connectivity),
and degraded health raises a named alert class.

## CTRL-017 Plan change impact

- id: CTRL-017
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-002
- affects: 08-control-plane

Changing a plan's entitlements is a C2-or-higher change (CON-009). Removing
an entitlement from a plan that tenants already use triggers a reconcile
that deactivates (not deletes) the module and preserves tenant data under
retention rules. Entitlement removal never hard-deletes tenant data.

## CTRL-018 Reconciliation verification

- id: CTRL-018
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted master plan
- depends_on: CTRL-009
- affects: 19-testing

Standing suites: (a) provisioning is idempotent (double-run is a no-op);
(b) reconcile is convergent (worklist drains); (c) drift is detected and
alerted; (d) configuration precedence resolves deterministically; (e)
unlicensed module access is denied with the stable code. Each maps to a
named test in [testing strategy](../19-testing/testing-strategy.md).

## CTRL-019 Control-plane events

- id: CTRL-019
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2.4 kernel catalog
- depends_on: CTRL-001
- affects: 12-events

The control plane emits kernel-namespace events: `platform.blueprint.*`,
`platform.reconcile.*`, `platform.provisioning.*`, and feature-state
transitions. Envelope, outbox, and relay semantics follow the
[events register](../12-events/events-and-outbox.md).

## CTRL-020 Control-plane authorization

- id: CTRL-020
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §4 operator surface
- depends_on: CTRL-001, SEC-005
- affects: 04-security

Control-plane mutations (plan edits, blueprint changes, provisioning,
manual reconcile) are platform-operator operations restricted to the
operator surface with step-up authentication, break-glass controls, and
full operator audit. They are not reachable from tenant admin surfaces.

## CTRL-021 Observability of control plane

- id: CTRL-021
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §11
- depends_on: CTRL-008
- affects: 20-sre

The control plane exposes: desired-vs-observed diff per tenant, reconcile
worklist depth, provisioning stage latency, and drift counts as kernel
meters. These feed SRE dashboards and alert classes
([SRE operations](../20-sre/sre-operations.md)).
