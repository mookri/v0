# Architecture Master

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 00; doc 01 dependency rules; doc 15 §5 risks; pasted plan

The architecture master records the platform's immutable structural decisions: the layered kernel/domain split, deployment topology, dependency rules, enforcement tooling, non-functional targets, fitness functions, and the build-phase risk register. It is the top-level reference for all structural questions.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| ARCH-001 | [ARCH-001](#arch-001-layered-architecture) | Platform kernel vs domain modules |
| ARCH-002 | [ARCH-002](#arch-002-deployment-topology) | Stateless web tier, workers, MySQL 8 |
| ARCH-003 | [ARCH-003](#arch-003-in-process-structure) | Kernel under app/Platform, modules under app/Modules |
| ARCH-004 | [ARCH-004](#arch-004-request-lifecycle) | Tenant → authn → authz → command/query → outbox |
| ARCH-005 | [ARCH-005](#arch-005-dependency-rules) | Kernel↛domains, module↛module internals |
| ARCH-006 | [ARCH-006](#arch-006-module-system-shape) | Manifest, bounded context, Contracts surface |
| ARCH-007 | [ARCH-007](#arch-007-contract-surfaces) | Contracts/ is the only importable module surface |
| ARCH-008 | [ARCH-008](#arch-008-event-driven-integration) | Outbox guarantees no event loss on commit |
| ARCH-009 | [ARCH-009](#arch-009-enforcement-tooling) | Deptrac, PHPStan, Pint, architecture tests |
| ARCH-010 | [ARCH-010](#arch-010-api-first--extraction-readiness) | Versioned OpenAPI + event catalog per module |
| ARCH-011 | [ARCH-011](#arch-011-fail-closed-posture) | Unresolved tenant/permission/scope → deny |
| ARCH-012 | [ARCH-012](#arch-012-auditability) | Every state change audited with actor/tenant/request |
| ARCH-013 | [ARCH-013](#arch-013-manifest-driven-platform) | Kernel materializes manifest-declared capabilities |
| ARCH-014 | [ARCH-014](#arch-014-additive-evolution) | APIs/events evolve additively; breaks need new version |
| ARCH-015 | [ARCH-015](#arch-015-nfr-targets--availability--durability) | 99.9%, RPO≤5min, RTO≤1h |
| ARCH-016 | [ARCH-016](#arch-016-nfr-targets--latency) | p95/p99 budgets per API class |
| ARCH-017 | [ARCH-017](#arch-017-nfr-targets--scale) | 1,000 tenants, 10M audit rows/month horizon |
| ARCH-018 | [ARCH-018](#arch-018-fitness-functions) | Automated architecture tests in CI |
| ARCH-019 | [ARCH-019](#arch-019-risk-register) | Build-phase risks and mitigations |
| ARCH-020 | [ARCH-020](#arch-020-cross-cutting-flow-map) | Tenant/authn/authz/events/idempotency entry points |

---

## ARCH-001 Layered architecture

- id: ARCH-001
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Navdhya is layered into a **platform kernel** (identity, tenancy, authorization, audit, events, workflow, files, search, settings, feature flags, observability) and **domain modules** (catalog, sales, finance, commerce, hr, manufacturing, etc.). Domains depend on the kernel; the kernel never depends on domains. This is the root structural rule from which all dependency enforcement derives.

## ARCH-002 Deployment topology

- id: ARCH-002
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

v1 deploys as a modular monolith: a stateless PHP-FPM/web tier (n≥2), separate queue workers, a lock-based single-instance scheduler, a dedicated outbox relay, Redis for cache/queue/prod sessions, object storage for files, and MySQL 8 (primary + async replica) with a central DB and per-tenant data connections. The app tier makes no local-disk assumptions.

## ARCH-003 In-process structure

- id: ARCH-003
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Within the process, the kernel lives under `app/Platform/` and each domain module under `app/Modules/<Domain>/` with a manifest, bounded context, and published `Contracts/` surface. The modular monolith is a deployment choice, not an architectural commitment: every module is carve-out-safe.

## ARCH-004 Request lifecycle

- id: ARCH-004
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Every request resolves tenant (fail closed), authenticates, authorizes against effective authority, then dispatches a command or query against the owning module aggregate. State-changing operations write domain events to the transactional outbox in the same DB transaction and produce an audit record.

## ARCH-005 Dependency rules

- id: ARCH-005
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Normative dependency rules: Platform ↛ Modules; a module ↛ another module's internals (only the other module's `Contracts/`); Http ↛ Domain direction is one-way; Support ↛ Modules. Cross-module reads go through published contracts; cross-module reactions go through domain events. Violations are Deptrac failures and review blockers.

## ARCH-006 Module system shape

- id: ARCH-006
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Each module ships a `module.json` manifest (code, version, phase, dependencies, permissions, events, scopes, feature flag, navigation), a bounded context, internal Domain/Events/Listeners/Jobs/Policies/Http/Database layers, and its own migrations. Only `Contracts/` is public to other modules.

## ARCH-007 Contract surfaces

- id: ARCH-007
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

A module's `Contracts/` directory is the sole code other modules may import. Contracts expose read APIs and typed interfaces; no cross-module SQL joins, no cross-module Eloquent relationships, and no direct reads of another module's tables are permitted.

## ARCH-008 Event-driven integration

- id: ARCH-008
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Modules integrate asynchronously via domain events written to the transactional outbox and relayed at-least-once. The outbox guarantees no event loss on commit. Event consumers are idempotent. This is the only legal asynchronous cross-module mechanism.

## ARCH-009 Enforcement tooling

- id: ARCH-009
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Architecture is enforced by tooling: Deptrac for layer rules, PHPStan (level 6 baseline ratcheting to 8), Laravel Pint for formatting, and architecture tests asserting route authorization, manifest validity, no cross-module migration touches, and zero kernel imports of `App\Modules`. These gates are CI-blocking.

## ARCH-010 API-first & extraction readiness

- id: ARCH-010
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Every module ships a versioned OpenAPI contract and an event catalog. API-first means Blade and API converge on the same commands and authorization path. Extraction-ready means any module can be carved out into a standalone service without rework.

## ARCH-011 Fail-closed posture

- id: ARCH-011
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Unresolved tenant, missing permission, unknown scope, or invalid audience all result in denial (401/403), never degraded permissive behavior. Fail-closed is a hard invariant and a named test class.

## ARCH-012 Auditability

- id: ARCH-012
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Every state-changing operation produces an audit record with actor, tenant, request id, and before/after state. Audit records are hash-chained for tamper evidence. Audit coverage is part of the definition of done.

## ARCH-013 Manifest-driven platform

- id: ARCH-013
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Permissions, scopes, events, navigation, and feature flags are declared in module manifests; the kernel is the single source of truth that materializes them. Undeclared capabilities are neither enforced nor exposed.

## ARCH-014 Additive evolution

- id: ARCH-014
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

API versions and event schemas evolve additively; breaking changes require a new version with an explicit deprecation window. Consumers must tolerate unknown added fields; schema versions are recorded on every event envelope.

## ARCH-015 NFR targets — availability & durability

- id: ARCH-015
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

v1 targets: API availability 99.9% (≤ ~43 min/month downtime), RPO ≤ 5 min (binlog point-in-time recovery), RTO ≤ 1 h (primary failover + app redeploy), webhook delivery at-least-once retried with exponential backoff for 24 h, and event durability via the outbox.

## ARCH-016 NFR targets — latency

- id: ARCH-016
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Latency budgets (p95/p99, excluding client network): token issuance 300/600 ms; simple reads 120/250 ms; list/search 250/500 ms; single-aggregate writes 300/700 ms; complex operations (checkout, posting, payroll) 1.5/3 s; pre-aggregated reports 2/5 s.

## ARCH-017 NFR targets — scale

- id: ARCH-017
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Design-horizon scale (not launch load): 1,000 tenants on the shared DB before first dedicated-mode promotions; 50 req/s sustained per web node with horizontal scale-out; 10M audit rows/month with partition/archival; 1M products and 10M inventory movement rows/year per tenant DB.

## ARCH-018 Fitness functions

- id: ARCH-018
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

Fitness functions are automated architecture tests run in CI: route-authorization declaration coverage, manifest↔code consistency, no cross-module joins, no kernel→module imports, tenant-isolation matrix, and public-allowlist pin. A failing fitness function blocks merge. Full suite catalog lives in the testing strategy.

## ARCH-019 Risk register

- id: ARCH-019
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

The build-phase risk register (migrated from implementation-sequence §5) records likelihood/impact/mitigation for: Passport OIDC gaps, outbox relay performance, MySQL FULLTEXT quality, Blade↔API convergence drift, tenant-isolation regression, phase scope creep, Windows dev friction, and golden-data maintenance. It is reviewed at each milestone gate.

## ARCH-020 Cross-cutting flow map

- id: ARCH-020
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00; doc 01; doc 15 §5; pasted plan
- depends_on: —
- affects: —

The cross-cutting flow map names the authoritative document for each platform flow: tenant resolution & fail-closed behavior (tenancy), authentication/token issuance/step-up (identity), effective authority (authorization), domain event publication & relay (events), and idempotency/rate limiting/error envelope (API platform). It is the first stop for tracing a request.

---

## Migrated source content

## 1 System Context

```text
                        ┌────────────────────────────────────────┐
                        │            EXPERIENCE LAYER            │
                        ├────────────────────────────────────────┤
                        │ Blade Admin (Velzon) │ Mobile (future) │
                        │ POS │ Partner API │ Webhooks │ SCIM    │
                        └───────────────────┬────────────────────┘
                                            │ HTTPS
                        ┌───────────────────▼────────────────────┐
                        │          PLATFORM APPLICATION          │
                        │  Laravel 12 modular monolith           │
                        │  Routing · Middleware · Tenant Context │
                        │  Rate Limits · Idempotency · Audit     │
                        └───┬───────────────┬───────────────┬────┘
                            │               │               │
                 ┌──────────▼───┐   ┌───────▼───────┐  ┌────▼───────────┐
                 │   PLATFORM   │   │   DOMAIN      │  │ INTELLIGENCE   │
                 │   KERNEL     │◄──│   MODULES     │  │ (Phase 6)      │
                 │              │   │               │  │                │
                 │ Identity/SSO │   │ P1 Commercial │  │ Analytics      │
                 │ Tenancy      │   │ P2 Commerce   │  │ Forecasting    │
                 │ AuthZ/Policy │   │ P3 Finance    │  │ Anomaly        │
                 │ Events/Outbox│   │ P4 Enterprise │  │ Copilot/Agents │
                 │ Workflow     │   │ P5 Ecosystem  │  │                │
                 │ Audit/Files  │   │               │  │                │
                 │ Search/Flags │   │               │  │                │
                 └──────┬───────┘   └───────┬───────┘  └────┬───────────┘
                        │                   │               │
                 ┌──────▼───────────────────▼───────────────▼──────┐
                 │ MySQL 8 (central + tenant data) · Redis · Disks │
                 └──────────────────────────────────────────────────┘
```

Normative dependency rule: **domains depend on the kernel; the kernel never
depends on any domain.** Intelligence consumes kernel APIs and domain events;
nothing depends on Intelligence.

---

---

## 2 Layered Responsibilities

| Layer | Responsibility | Explicitly NOT responsible for |
|-------|----------------|-------------------------------|
| Experience | Rendering, UX flows, channel-specific UX concerns | Business rules, authorization decisions, persistence |
| Platform Application | Request routing, middleware pipeline, tenant resolution, rate limiting, idempotency, request-scoped context | Domain logic |
| Platform Kernel | Identity/SSO, tenancy, authorization & policy, events/outbox, workflow, audit, notifications, files, search, settings, feature flags, observability, API platform | Any business entity or business rule |
| Domain Modules | Business entities, state machines, domain services, module-scoped persistence, OpenAPI fragments, event publication/consumption | Tenancy, authN/authZ primitives, audit plumbing, queue plumbing |
| Intelligence | Read-models, forecasts, recommendations, copilot actions | Writing authoritative business state (acts only through kernel-authorized APIs) |

---

---

## 3 In-Process Structure

The v1 deployable is a single Laravel application (see [domain framework](../09-domain-framework/domain-framework.md)):

- `app/Platform/` — kernel primitives, framework-agnostic where practical.
- `app/Modules/<Domain>/` — business modules with uniform internal shape.
- `app/Support/` — shared value objects (e.g. `Money`, `Ulid` helpers) with zero domain logic.
- `routes/` — thin route registration that defers to module route files.

Extraction readiness is a design constraint, not a v1 activity: every module
MUST satisfy the carve-out rules in [domain framework §6](./architecture-master.md) so it can later become a
standalone service consuming the kernel over HTTP (token introspection per
[identity & token platform §10](../04-security/identity-and-token-platform.md)).

---

---

## 4 Request Lifecycle

### 4.1 Web (Blade admin, session auth)

```text
HTTPS → trusted proxy → Laravel route middleware chain:
  ThrottleRequests → StartSession → Authenticate (session)
  → ResolveTenantFromMembership → SetLocaleAndTimezone
  → Authorize (can:<permission>, step-up check if sensitive)
  → Controller (shared FormRequest + Policy with API)
  → Blade render (Velzon) → AuditLog (state-changing ops)
```

### 4.2 API (`/api/v1`, token auth)

```text
HTTPS → route middleware chain:
  ThrottleRequests(tier=tenant+client) → AuthenticateToken (Passport JWT:
    signature, exp, aud, jti revocation check)
  → ResolveTenantFromToken (X-Tenant-Id ∩ token tenant claims)
  → EnforceScopes (route scope ∩ user grants ∩ membership)
  → IdempotencyGate (POST/PUT with Idempotency-Key)
  → Controller (same FormRequest + Policy as web)
  → JSON resource → Outbox (domain events) → AuditLog
```

Both channels MUST converge on the same FormRequest validation and Policy
authorization so the Blade console continuously exercises the API contract
(see [API platform](../07-kernel/api-platform.md) §8).

### 4.3 Asynchronous

```text
Domain operation commits DB transaction including outbox rows
  → OutboxRelay worker polls/claims unpublished rows
  → publishes to queue topics + webhook dispatcher
  → listeners run idempotently (event_id dedupe)
  → failures → retry with backoff → dead-letter + alert
```

---

---

## 5 Deployment Topology (v1)

Single region, one logical environment shape (dev/staging/prod parity):

```text
                 Internet
                    │
              ┌─────▼─────┐
              │  LB/TLS   │  TLS 1.2+ termination (TLS 1.3 preferred)
              └─────┬─────┘
        ┌───────────┼───────────────┐
  ┌─────▼─────┐ ┌───▼───────┐ ┌─────▼─────┐
  │ web: php-fpm │ web (n≥2) │ │ web (n≥2) │   stateless app tier
  └─────┬─────┘ └───┬───────┘ └─────┬─────┘
        └───────────┼───────────────┘
        ┌───────────┼───────────────┐
  ┌─────▼─────┐ ┌───▼─────┐ ┌───────▼─────┐
  │ queue     │ │ scheduler│ │ outbox relay │  workers (supervisor)
  │ workers   │ │ (cron)   │ │ (dedicated)  │
  └─────┬─────┘ └───┬─────┘ └───────┬─────┘
        └───────────┼───────────────┘
        ┌───────────▼───────────┐   ┌──────────────┐
        │ Redis (cache, queue,  │   │ object storage│
        │ sessions in prod)     │   │ (files disk)  │
        └───────────┬───────────┘   └──────────────┘
        ┌───────────▼───────────────────────────┐
        │ MySQL 8: primary + async replica      │
        │  central DB (identity, tenants,       │
        │  manifests, catalogs, flags)          │
        │  tenant data connection (per tenant:  │
        │  module tables + audit + outbox)      │
        │  dedicated DBs (promoted tenants)     │
        └───────────────────────────────────────┘
```

Normative deployment rules:
- The app tier MUST be stateless: sessions in Redis, files on object storage,
  no local-disk assumptions, safe to scale horizontally.
- Queue workers and the outbox relay MUST be deployable as separate processes
  with independent scaling.
- The scheduler MUST run exactly one instance per environment (lock-based).
- Local development MAY use database cache/queue/session drivers and SQLite
  only for throwaway exploration; CI and staging MUST run MySQL 8.

---

---

## 6 Non-Functional Targets (v1)

### 6.1 Availability & durability

| Target | Value |
|--------|-------|
| Availability (API, monthly) | 99.9% (≤ ~43 min downtime/month) |
| RPO | ≤ 5 min (binlog-based point-in-time recovery) |
| RTO | ≤ 1 h (primary failover + app redeploy) |
| Webhook delivery | at-least-once, retried with exponential backoff for 24 h |
| Event durability | outbox guarantees no event loss on commit |

### 6.2 Latency (p95, excluding client network)

| API class | p95 | p99 |
|-----------|-----|-----|
| Token issuance (`/oauth/token`) | 300 ms | 600 ms |
| Simple reads (single resource, cached metadata) | 120 ms | 250 ms |
| List/search (indexed, ≤ 100 items/page) | 250 ms | 500 ms |
| Writes (single aggregate, incl. outbox commit) | 300 ms | 700 ms |
| Complex operations (checkout, posting, payroll run) | 1.5 s | 3 s |
| Reports (pre-aggregated) | 2 s | 5 s |

### 6.3 Scale targets (design horizon, not launch load)

- 1,000 tenants on the shared DB before first dedicated-mode promotions.
- 50 requests/s sustained per web node; horizontal scale-out beyond that.
- 10M audit rows per month with partition/archival strategy ([kernel services §5](../07-kernel/kernel-services.md)).
- 1M products, 10M inventory movement rows per year on shared tenant DB.

### 6.4 Security & compliance posture

OWASP ASVS Level 2 target; SOC 2 / ISO 27001 evidence readiness via the audit
log. Full baseline in [security baseline](../04-security/security-baseline.md).

### 6.5 Operability

- Health endpoints: `/up` (liveness) and `/ready` (DB + Redis + queue connectivity).
- Structured JSON logs with `request_id`, `tenant_id`, `user_id`, `client_id` on every line.
- Every tenant-visible error returns a stable machine-readable `code` ([API platform §4](../07-kernel/api-platform.md)).

---

---

## Module dependency rules ([domain framework §5](./architecture-master.md))

1. **No cross-module table access.** A module's models may only reference its
   own tables. Foreign keys to another module's tables are prohibited;
   referential integrity across modules is maintained by events + local
   shadow records (e.g. Sales keeps `customer_id` + denormalized display
   fields refreshed from Customers events).
2. **Contracts for synchronous reads.** If Sales needs a customer's credit
   limit, it injects `Customers\Contracts\CustomerLookup`. Contract methods
   MUST accept/return DTOs or scalars, never Eloquent models.
3. **Events for reactions.** Inventory reacts to `sales.order.confirmed` via
   a listener; it MUST NOT be called directly by Sales.
4. **No shared transactions.** A module MUST NOT open a DB transaction that
   spans another module's writes; orchestration uses the saga/outbox pattern.
5. **Kernel is the only common dependency.** Shared code lives in
   `app/Support/` or the kernel, never duplicated per module.

---

## Enforcement tooling ([domain framework §7](../19-testing/testing-strategy.md))

| Tool | Config location | Enforces |
|------|-----------------|----------|
| **Deptrac** | `deptrac.yaml` | Layer rules: Platform ↛ Modules; Module ↛ Module internals (only `Contracts/`); Http ↛ Domain direction; Support ↛ Modules |
| **PHPStan** | `phpstan.neon` | Level 6 baseline at kernel/CI start, ratcheting to 8; no new baseline entries for module code after phase exit |
| **Laravel Pint** | `pint.json` (PSR-12) | Formatting gate, `--test` in CI |
| **Architecture tests** | `tests/Architecture/` | (a) every registered route declares `can:` or a kernel public marker; (b) every manifest permission/scope/event matches naming rules and exists in code; (c) no module migration touches another module's tables; (d) every module has `module.json` + provider + tests directory; (e) kernel has zero imports of `App\Modules` |
| **Contract tests** | per-module `Tests/Contract/` | Requests/responses validated against the module's OpenAPI fragment ([testing strategy §4](../19-testing/testing-strategy.md)) |

Deptrac layer sketch:

```text
layers:
  Kernel:      App\Platform\**
  Support:     App\Support\**
  ModuleCore:  App\Modules\*\(Domain|Events|Listeners|Jobs|Policies)
  ModuleHttp:  App\Modules\*\Http
  Contracts:   App\Modules\*\Contracts
ruleset:
  Kernel:      may depend on Support only
  Contracts:   may depend on Support only
  ModuleCore:  may depend on Kernel, Support, Contracts
  ModuleHttp:  may depend on its ModuleCore, Kernel, Support, Contracts
  (Module A core/http) MUST NOT depend on Module B non-Contracts layers
```

---

## Risk register ([gates & milestones §5](./architecture-master.md))

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| Passport OIDC gaps (discovery/id_token edge cases) | medium | M2 slip | kernel OIDC layer isolates Passport; conformance scenarios written first (test-first for §[identity & token platform §13](../04-security/identity-and-token-platform.md)) |
| Outbox relay performance under event flood | medium | M3/M6 | SKIP LOCKED + batching from day 1; event-flood profile in M6; relay horizontally scalable |
| MySQL FULLTEXT search quality | medium | M3 | driver interface makes swap to Meilisearch operational, not architectural ([kernel services §8.1](../07-kernel/kernel-services.md)) |
| Blade ↔ API convergence drift | medium | M4–M5 | convergence architecture test in CI from M4; shared FormRequest/Policy mandatory |
| Tenant isolation regression as module count grows | low | critical | generated model matrix scales automatically; suite never skippable; P1 policy on main breakage |
| Scope creep into Phase 2 features during M5 | high | schedule | phase-boundary PR labels; manifest phase field enforced at review |
| Windows dev-environment friction (paths, symlinks) | medium | velocity | Sail/Docker compose provided; CI is the truth (Linux) |
| Fixture/golden-data maintenance burden | low | M6+ | versioned golden datasets with diff notes ([testing strategy §13](../19-testing/testing-strategy.md)) |

---
