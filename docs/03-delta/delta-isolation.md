# Delta Isolation

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** architecture principles; docs 01/02/05/11/13 boundary rules; pasted master plan

A Delta is any crossing of a normative boundary (layer, module, tenant, trust zone, human/machine). This document defines the Delta taxonomy, the normative Delta matrix, and the verification strategy that maps every matrix row to a standing test suite.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| DELTA-001 | [Delta definition and taxonomy](#delta-001-delta-definition-and-taxonomy) | A **Delta** is any crossing of a normative boundary: between layers |
| DELTA-002 | [Platform to domain boundary](#delta-002-platform-to-domain-boundary) | Domains depend on the platform; the platform never depends on domains. |
| DELTA-003 | [Module to module boundary](#delta-003-module-to-module-boundary) | Modules MUST NOT touch another module's tables or internals. The only |
| DELTA-004 | [Tenant boundary — database surface](#delta-004-tenant-boundary--database-surface) | Every tenant-scoped row carries `tenant_id`; every query on a |
| DELTA-005 | [Tenant boundary — cache surface](#delta-005-tenant-boundary--cache-surface) | All cache keys for tenant-scoped data MUST be prefixed with the tenant |
| DELTA-006 | [Tenant boundary — search surface](#delta-006-tenant-boundary--search-surface) | Search indices are per-tenant (index-per-tenant or mandatory tenant |
| DELTA-007 | [Tenant boundary — files surface](#delta-007-tenant-boundary--files-surface) | Object-storage paths are namespaced by tenant. Signed URLs and file |
| DELTA-008 | [Tenant boundary — queues and jobs](#delta-008-tenant-boundary--queues-and-jobs) | Every queued job carries `TenantContext` and restores it before |
| DELTA-009 | [Tenant boundary — events and outbox](#delta-009-tenant-boundary--events-and-outbox) | The outbox is written on the tenant connection; every event envelope |
| DELTA-010 | [Tenant boundary — exports and bulk operations](#delta-010-tenant-boundary--exports-and-bulk-operations) | Import/export jobs are bound to exactly one tenant for their whole |
| DELTA-011 | [Tenant boundary — AI surface](#delta-011-tenant-boundary--ai-surface) | AI features (forecasting, anomaly, copilot, agents) consume only the |
| DELTA-012 | [Extension isolation](#delta-012-extension-isolation) | Extensions (hooks, partner apps, custom objects) run behind declared |
| DELTA-013 | [Public surface boundary](#delta-013-public-surface-boundary) | The unauthenticated surface is a pinned allowlist. Any route not on the |
| DELTA-014 | [Blast radius and degradation](#delta-014-blast-radius-and-degradation) | Failure propagation across boundaries MUST be bounded: queue backlog does |
| DELTA-015 | [Delta matrix (normative)](#delta-015-delta-matrix-normative) | Legend: ✅ = allowed through the named mechanism · ⛔ = prohibited · |
| DELTA-016 | [Verification strategy](#delta-016-verification-strategy) | Each matrix row maps to a standing suite: |

---


## DELTA-001 Delta definition and taxonomy

- id: DELTA-001
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: architecture principles; pasted master plan
- depends_on: CON-002
- affects: every boundary rule in this tree

A **Delta** is any crossing of a normative boundary: between layers
(platform vs domain), between modules, between tenants, between trust
zones (authenticated vs public), or between humans and machines. Every
Delta is one of: **read**, **write**, **call**, **event**, **import**,
**export**, or **failure propagation**. Deltas are the unit of
architecture verification: each matrix row maps to at least one standing
test suite ([testing strategy](../19-testing/testing-strategy.md)).

## DELTA-002 Platform to domain boundary

- id: DELTA-002
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: principle 1 (README); doc 01 §4
- depends_on: DELTA-001
- affects: 07-kernel, 09-domain-framework

Domains depend on the platform; the platform never depends on domains.
No kernel code MAY import, query, or reference any `app/Modules/*`
symbol, table, event name, or route. The kernel exposes primitives;
modules register into them via manifests. Violation is a Deptrac failure
and a review blocker.

## DELTA-003 Module to module boundary

- id: DELTA-003
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: principle 2; doc 01 §5
- depends_on: DELTA-001
- affects: 09-domain-framework, 14-domains

Modules MUST NOT touch another module's tables or internals. The only
legal module↔module Deltas are: **call** through the owning module's
published `Contracts/` surface, and **event** reaction through domain
events. Cross-module joins are prohibited and CI-enforced.

## DELTA-004 Tenant boundary — database surface

- id: DELTA-004
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §5
- depends_on: DELTA-001
- affects: 06-tenancy, 13-data

Every tenant-scoped row carries `tenant_id`; every query on a
tenant-scoped table is constrained by it (`BelongsToTenant`). No query,
index, unique constraint, or migration may enable one tenant to read or
write another tenant's rows in shared mode; in dedicated mode the
connection itself is the boundary.

## DELTA-005 Tenant boundary — cache surface

- id: DELTA-005
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §5.3; doc 05 §9
- depends_on: DELTA-004
- affects: 07-kernel

All cache keys for tenant-scoped data MUST be prefixed with the tenant
identifier. Global (non-tenant) cache entries MUST hold no tenant data.
The isolation suite asserts a cache read with tenant A's context never
returns tenant B's value.

## DELTA-006 Tenant boundary — search surface

- id: DELTA-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §8
- depends_on: DELTA-004
- affects: 07-kernel

Search indices are per-tenant (index-per-tenant or mandatory tenant
filter on every query). Cross-tenant search results are a named negative
test in the isolation suite.

## DELTA-007 Tenant boundary — files surface

- id: DELTA-007
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §7
- depends_on: DELTA-004
- affects: 07-kernel

Object-storage paths are namespaced by tenant. Signed URLs and file
references are validated against the requesting tenant at access time; a
file reference from tenant A presented in tenant B's context is denied.

## DELTA-008 Tenant boundary — queues and jobs

- id: DELTA-008
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §4.4
- depends_on: DELTA-004
- affects: 07-kernel, 12-events

Every queued job carries `TenantContext` and restores it before
execution. A job enqueued for tenant A MUST NOT execute with tenant B's
context, and MUST fail closed if its tenant cannot be re-resolved.

## DELTA-009 Tenant boundary — events and outbox

- id: DELTA-009
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §2–3
- depends_on: DELTA-004
- affects: 12-events

The outbox is written on the tenant connection; every event envelope
carries `tenant_id`. Relays and consumers verify the envelope tenant
against the execution context. Webhook deliveries are tenant-scoped; no
subscriber receives another tenant's events.

## DELTA-010 Tenant boundary — exports and bulk operations

- id: DELTA-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 16 §14 (data tools)
- depends_on: DELTA-004
- affects: 07-kernel/data-tools

Import/export jobs are bound to exactly one tenant for their whole
lifetime; export artifacts are stored in the tenant's file namespace and
their download URLs re-check tenant + permission. Bulk operations can
never aggregate across tenants.

## DELTA-011 Tenant boundary — AI surface

- id: DELTA-011
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 12 §6; doc 13 §8.3
- depends_on: DELTA-004
- affects: 17-ai

AI features (forecasting, anomaly, copilot, agents) consume only the
invoking tenant's data; model prompts, retrieval sets, and cached
inferences are tenant-partitioned. No cross-tenant training or inference
pooling in v1.

## DELTA-012 Extension isolation

- id: DELTA-012
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 11
- depends_on: DELTA-003
- affects: 18-extensions

Extensions (hooks, partner apps, custom objects) run behind declared
extension points with scoped tokens; they cannot reach module internals,
cannot escalate scopes, and their failures are contained (hook timeout
and retry budgets, no extension-driven schema changes outside the custom
objects engine).

## DELTA-013 Public surface boundary

- id: DELTA-013
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 08 §10.2; doc 16 §16.2
- depends_on: DELTA-001
- affects: 10-experience, 19-testing

The unauthenticated surface is a pinned allowlist. Any route not on the
allowlist that lacks tenant + principal resolution is denied by default.
Public endpoints MUST NOT leak cross-tenant data and MUST be throttled;
the allowlist is CI-pinned.

## DELTA-014 Blast radius and degradation

- id: DELTA-014
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §8.3
- depends_on: DELTA-001
- affects: 20-sre

Failure propagation across boundaries MUST be bounded: queue backlog does
not stop web requests; one tenant's bulk job does not starve others
(fair-share limits); a failing connector degrades to DLQ + alert, never
to blocked core flows. Degradation behavior for each boundary is recorded
in [HA/DR](../20-sre/ha-dr.md).

## DELTA-015 Delta matrix (normative)

- id: DELTA-015
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: docs 01/02/05/11/13 rules
- depends_on: DELTA-002…DELTA-014
- affects: 19-testing

Legend: ✅ = allowed through the named mechanism · ⛔ = prohibited ·
◐ = allowed only under the stated control.

| Source → Target | read | write | call | event | import/export | failure |
|-----------------|------|-------|------|-------|---------------|---------|
| domain → kernel | ✅ contracts | ⛔ (kernel tables owned by kernel) | ✅ kernel APIs | ✅ platform events | ◐ via kernel jobs | contained |
| kernel → domain | ⛔ | ⛔ | ⛔ | ⛔ (kernel never subscribes to domain events for logic) | ⛔ | contained |
| module A → module B | ⛔ tables; ✅ via B's `Contracts/` read APIs | ⛔ | ✅ `Contracts/` only | ✅ react to B's published events | ⛔ | DLQ/timeout budgets |
| tenant A → tenant B | ⛔ all surfaces (DB, cache, search, files, queues, events, exports, AI) | ⛔ | ⛔ | ⛔ | ⛔ | ⛔ |
| extension → platform | ✅ declared read APIs | ⛔ | ✅ hooks only | ✅ subscribed webhooks | ◐ marketplace-reviewed | sandboxed |
| public → tenant data | ◐ allowlisted routes + tenant resolution | ◐ allowlisted mutations, throttled | ⛔ | ⛔ | ⛔ | fail closed |
| AI agent → any | ◐ per-call re-evaluation, budget-guarded | ◐ capability-checked tool calls | ◐ machine token, no sensitive scopes | ⛔ | ⛔ | budget cutoff |

## DELTA-016 Verification strategy

- id: DELTA-016
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 14 suites
- depends_on: DELTA-015
- affects: 19-testing

Each matrix row maps to a standing suite:

| Delta row | Suite |
|-----------|-------|
| kernel↛domains, module↛module | Deptrac layers + architecture tests (`AssertNoCrossModuleJoins`) |
| tenant↔tenant (DB/cache/search/files/queues/events/exports/AI) | `AssertTenantIsolation` matrix suite |
| public surface | allowlist pin test + throttle tests |
| extension isolation | hook sandbox suite (Phase 5) |
| blast radius | chaos/degradation drills (SRE schedule) |
| AI agent safety | agent authorization suite (sampled allow / full deny log assertions) |
