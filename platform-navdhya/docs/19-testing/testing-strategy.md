# Testing Strategy

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 14; DELTA-016 verification strategy; EXP-006 public-surface allowlist

The testing strategy is the single normative statement of how the platform is verified: the test pyramid and principles, the standing suites (tenant isolation, authorization matrix, token-flow E2E, contract), the CI pipeline and quality gates, load testing, and the Definition of Done referenced by CON-011. Every domain file's *Verification requirements* section feeds the suites defined here; every gate in the evidence register automates through the CI stages below.


---

## Test Strategy Overview

```text
                    ┌────────────────────────────┐
                    │   ARCHITECTURE & STATIC    │  Deptrac, PHPStan, Pint,
                    │   (fast, every commit)     │  manifest/route validators
                    └─────────────┬──────────────┘
                    ┌─────────────▼──────────────┐
                    │   UNIT                     │  domain services, value
                    │   (every commit)           │  objects, state machines
                    └─────────────┬──────────────┘
                    ┌─────────────▼──────────────┐
                    │   FEATURE (MySQL 8)        │  HTTP routes, workflows,
                    │   (every commit)           │  events/outbox, policies
                    └─────────────┬──────────────┘
                    ┌─────────────▼──────────────┐
                    │   STANDING SUITES          │  tenant isolation,
                    │   (every commit, parallel) │  authz matrix, contract,
                    │                            │  token-flow E2E
                    └─────────────┬──────────────┘
                    ┌─────────────▼──────────────┐
                    │   SECURITY & LOAD          │  scheduled + per release
                    │   (nightly / release)      │  ASVS checks, k6 profiles
                    └────────────────────────────┘
```

Principles:

1. **MySQL 8 everywhere in tests.** SQLite is banned in CI (behavioral
   divergence: locking, FULLTEXT, partitions, JSON). Local dev may use
   Sail/Docker MySQL; CI spins MySQL 8 + Redis services per job.
2. **Tests run against the modular monolith as deployed** — same
   middleware stack, same module registry, same outbox (queue sync driver
   in tests except outbox-specific tests which use the real relay).
3. **Deterministic time and ULIDs** in tests via kernel test helpers
   (frozen clock factory) so state machines, SLA clocks, and scheduling
   are testable without sleeps.
4. **No cross-suite state**: every test owns its fixtures via factories;
   shared fixtures are factory definitions, not seeded rows.
5. **Every bug fix ships with a regression test** that fails before the
   fix.

---
---

## Unit Tests

- Scope: domain services, value objects (Money, Period, Ulid), state
  machine definitions (transition tables asserted), pricing resolution
  algorithm, tax computation determinism, promotion evaluator, posting
  engine template resolution, checkout calculation pipeline order.
- Framework: PHPUnit 11; no framework boot for pure unit tests
  (`Tests\Unit\Platform` extends plain PHPUnit TestCase).
- Coverage expectations (guideline, not vanity gate):
  | Area | Target |
  |------|--------|
  | `app/Platform` | ≥ 90% line |
  | `app/Modules/*/Domain` | ≥ 80% line |
  | `app/Modules/*/Http` | covered via feature/contract suites instead |
- Property-based tests where invariants matter: double-entry balance
  invariant ([finance domains §10.1](../14-domains/finance/gl.md)), gift card ledger balance invariant, audit hash
  chain continuity, money rounding laws.
---

## Feature Tests

- Scope: HTTP routes (validation, authorization outcomes, state
  transitions, idempotency, error envelope codes), workflow engine
  transitions end-to-end, event publication (outbox row assertions),
  listeners (including cross-module reactions), scheduler commands.
- Kernel feature-test helpers:
  | Helper | Purpose |
  |--------|---------|
  | `TenantTestCase` | boots n tenants + memberships with roles; one-call context switching |
  | `actingAsMembership()` | authenticates web or API with chosen tenant/org |
  | `assertEventPublished(name, payloadSubset)` | outbox assertion |
  | `assertAudit(action, subject)` | audit log assertion |
  | `assertEnvelopeCode(response, http, code)` | [API platform §4](../07-kernel/api-platform.md) envelope |
  | `freezeTime()`, `asWorker()` | deterministic scheduling/queue |
- Per-module verification requirements (docs 07–12 §"Verification") are the
  feature-test checklist for that module — each numbered item maps to ≥ 1
  named test.
---

## Contract Tests (OpenAPI)

Normative ([API platform §9.1](../07-kernel/api-platform.md)):

1. Each module's `Http/openapi.yaml` fragment is validated for syntax
   + composition (no path collisions, unique operationIds, resolvable
   component refs) in CI via `api:compose`.
2. Every operation is exercised by ≥ 2 tests: a **valid** request/response
   pair and an **invalid** case; responses are validated against the
   fragment's schemas (status, headers, body) — drift fails CI.
3. Vendor extensions enforced:
   - `x-permission` codes exist in the permission catalog;
   - declared `x-idempotency: required` operations reject requests
     without the header (400 `idempotency_key_missing` tested);
   - `x-sensitive` operations return `step_up_required` at
     ACR 1.
4. Event schemas: every manifest event has a JSON Schema; a round-trip
   test publishes the event and validates the serialized envelope+payload
   against the schema ([kernel services §2.2](../12-events/events-and-outbox.md)).
5. OpenAPI diff artifact (`api:diff`) is attached to every PR; the
   review checklist requires acknowledging additions/removals
   ([API platform §9.2](../07-kernel/api-platform.md)).
---

## Tenant Isolation Suite (standing)

`tests/TenantIsolation/` runs on every CI build against MySQL 8:

1. **Model matrix**: for every `BelongsToTenant` model (kernel +
   all modules + projections): seed tenants A/B → create rows in A →
   assert invisible from B across: reads, updates, deletes, soft-delete
   restore, relations (belongsTo/hasMany eager loading), counts, and
   search queries.
2. **Raw-path guard**: architecture scan fails CI on raw SQL against
   tenant tables outside kernel scopes ([tenancy & data §5.3](../06-tenancy/tenancy-and-data.md)).
3. **Resolution fail-closed**: request without tenant context writing a
   tenant model throws `TenantContextMissingException`; API call
   without `X-Tenant-Id` → 400 `tenant_header_missing`;
   token/tenant mismatch → 401 `tenant_not_in_token`.
4. **Cross-tenant id probing**: GET with a foreign tenant's ULID → 404
   `not_found` (existence non-leakage, [API platform §4.2](../07-kernel/api-platform.md)) for every
   resource route (generated sweep).
5. **Async isolation**: jobs dispatched in tenant A context replayed
   under tenant B worker context refuse to execute (payload mismatch
   guard, [tenancy & data §4.4](../06-tenancy/tenancy-and-data.md)).
6. **Public surfaces**: [commerce domains §10.2](../14-domains/commerce/store-checkout.md) allowlist endpoints probed with
   cross-store/cross-tenant slugs → never leak.
7. **Dedicated mode**: promotion runbook tested end-to-end in CI
   (second MySQL database): rows identical (checksums), scopes still
   active post-cutover, demotion rollback works ([tenancy & data §7](../06-tenancy/tenancy-and-data.md)).
---

## Authorization Matrix Suite

Generated from the route registry × permission catalog ([authorization & policy §8.4](../04-security/authorization-and-policy.md)):

1. For every route: (anonymous, wrong tenant, no permission, wrong scope,
   wrong org, ACR 1 on sensitive route, full authority) → expected
   status + envelope code asserted. The matrix artifact is published in CI
   for security review.
2. Scope ∩ grant ∩ membership intersection cases ([authorization & policy §10](../04-security/authorization-and-policy.md)):
   broad-scope/low-grant, narrow-scope/high-grant, revoked-membership
   with valid token.
3. SoD suite: every seeded rule ([authorization & policy §7.1](../04-security/authorization-and-policy.md)) — assignment-time block,
   runtime re-check, self-approval rejection, warn-mode emission.
4. Step-up suite: sensitive route at ACR 1 → 403 `step_up_required`
   with `details.stepup_url`; after step-up → success; client-
   credentials token on sensitive route → 403 permanently.
---

## Token-Flow E2E Suite

The [identity & token platform §13](../04-security/identity-and-token-platform.md) scenarios, automated as E2E tests (real HTTP, real
Passport, MySQL):

1. Code+PKCE happy path incl. down-scoping at consent.
2. PKCE verifier mismatch → `invalid_grant`.
3. Refresh rotation + reuse detection → lineage revoked, sessions killed,
   `platform.token.reuse_detected` emitted.
4. Client credentials: scope capping, tenant binding, sensitive-scope
   denial.
5. Device flow: pending / slow_down / expired / approved / denied.
6. Revocation latency: revoked `jti` rejected within cache bound;
   membership revocation denies immediately.
7. Federation: enforced-domain password blocked; JIT provisioning;
   revoked membership cannot re-enter via SSO; SAML assertion replay
   rejected.
8. SCIM: provisioning, deactivation revokes tokens, group→role mapping,
   RFC 7644 error conformance, drift reconciliation.
9. JWKS/discovery endpoints serve correct keys incl. rotation overlap.
---

## Workflow & Event Suites

1. Every registered state machine: full transition table asserted
   (legal transitions succeed; illegal ones return
   `state_forbidden`); guard failures return
   `workflow_guard_failed`.
2. Approval flows: approver ≠ submitter, expiry, multi-level chains,
   notification dispatch.
3. Outbox: rollback leaves no rows; relay publishes exactly-once to the
   queue with idempotent listeners (event_id dedupe); dead-letter after
   max attempts + replay command works; per-aggregate ordering preserved.
4. Event causality chains: lifecycle E2Es (e.g. [commercial core domains §11.1](../14-domains/sales/pricing.md) order
   lifecycle) assert the exact event sequence with correlation/causation
   ids.
---

## Security Test Suite (scheduled + release gate)

- **Abuse cases** per public endpoint ([security baseline §10.2](../04-security/security-baseline.md)): rate-limit
  boundaries, oversized payloads, malformed JSON, Unicode/confusables in
  emails, header injection attempts.
- **ASVS L2 automation** ([security baseline §10.1](../04-security/security-baseline.md)): security headers, cookie flags,
  error leakage probe (prod config), TLS config scan on staging.
- **Secrets & redaction**: assert Authorization headers, passwords,
  ciphertext fields never appear in logs, audit before/after, events, or
  error responses (instrumented log-capture tests).
- **SSRF/XSS probes**: webhook URL validation cases; stored-HTML
  sanitization cases for knowledge base/listing fields.
- **Dependency audit**: `composer audit` + `npm audit`
  (fail on high/critical).
---

## CI Pipeline & Quality Gates

```yaml
# .github/workflows/ci.yml (shape — normative stages)
stages:
  static:            # ~2 min, every push
    - composer validate + lockfile check
    - Pint --test
    - PHPStan (level per ratchet, baseline tracked)
    - Deptrac (fail on any violation)
    - manifest validators (module.json schema, permission/event naming,
      route-permission coverage, public-allowlist pinning, error-code
      registry conformance)
  build-assets:      # vite build + openapi compose
    - api:compose + api:diff artifact
  unit:              # ~3 min, parallel shards
  feature:           # ~12 min, MySQL 8 + Redis services, sharded by module
  standing:          # parallel with feature
    - tenant isolation suite
    - authorization matrix
    - token-flow E2E
    - contract tests
  security-nightly:  # scheduled
    - dependency audit, secrets scan, ASVS automation on staging
  load-release:      # per release candidate
    - k6 profiles (§11) against staging
gates:
  - all stages green to merge; standing suites never skippable
  - coverage may not decrease on Platform/ (ratchet)
```

Quality gate table (phase-exit criteria reference [gates & milestones](../23-evidence/gates-and-milestones.md)):

| Gate | Tool | Failure policy |
|------|------|----------------|
| Formatting | Pint | block merge |
| Static analysis | PHPStan level 6 baseline → 8 ratchet | block merge on new errors; baseline entries forbidden for new module code |
| Boundaries | Deptrac | block merge |
| Unit + feature | PHPUnit | block merge |
| Tenant isolation | standing suite | **block merge; P1 if broken on main** |
| AuthZ matrix | standing suite | block merge |
| Contract conformance | OpenAPI validation + tests | block merge |
| Token flows | E2E suite | block release |
| Security automation | nightly | P2 ticket on failure; release blocked on open high findings |
| Load profile | k6 release run | release blocked on regression > 20% vs baseline |
---

## Load & Performance Testing

Tool: **k6**; environment: staging with production-shaped data volumes
(seeded tenant with 1M products, 10M movement rows — [architecture master §6.3](../02-architecture/architecture-master.md)).

Profiles (per [architecture master §6.2](../02-architecture/architecture-master.md) targets):

| Profile | Scenario | Target |
|---------|----------|--------|
| `api-baseline` | mixed read/write ramp to 50 rps per node | p95 within table; error rate < 0.1% |
| `token-issuance` | /oauth/token storms per client | p95 ≤ 300 ms; throttle engages correctly |
| `checkout-storm` | concurrent checkout captures incl. stock races | zero negative stock; idempotency holds; p95 ≤ 1.5 s |
| `event-flood` | burst 100k events through outbox | relay lag recovers < 60 s; no dead letters at nominal rates |
| `webhook-fanout` | 1k endpoints × event burst | retry schedule honored; delivery log bounded |
| `report-heavy` | concurrent balance sheets over 36 months | queue artifacts path engaged; inline ≤ 2 s p95 for cached |

Release criteria: no p95 regression > 20% vs previous baseline; outbox
lag and queue depth return to steady state within the profile window.
---

## Definition of Done

A feature (module capability) is done when:

1. Code merged with unit + feature tests incl. the module's verification
   items.
2. Manifest updated (permissions/events/scopes/nav) + OpenAPI fragment +
   event schemas; `api:compose` green.
3. Tenant isolation suite covers any new tables; AuthZ matrix covers new
   routes.
4. Audit + events asserted in tests; sensitive operations wired to
   step-up; SoD rules updated where money/approval semantics changed.
5. Admin console screen exercises the same actions (convergence test,
   [API platform §12](../07-kernel/api-platform.md)).
6. Docs: this spec's phase doc section updated if the contract changed
   (additive diff attached to PR).
7. Observability: new errors have envelope codes; new metrics/alerts
   declared.
---

## Test Data & Environments

| Environment | Purpose | Data |
|-------------|---------|------|
| local (dev) | development | factories + demo seeders (flag-gated, [domain framework §3.1](../09-domain-framework/domain-framework.md)) |
| CI | gates above | factories only; MySQL 8 service containers |
| staging | E2E, load, security, DR drills | synthetic volume tenant ([architecture master §6.3](../02-architecture/architecture-master.md)) + golden fixtures |
| sandbox (partner) | partner dev (Phase 5) | weekly-reset tenant, [ecosystem §1.3](../18-extensions/ecosystem.md) |

Golden datasets: finance regression fixtures ([finance domains §10.9](../14-domains/finance/gl.md)), tax
jurisdiction fixtures, pricing/checkout fixtures — versioned, reviewed,
never mutated without a diff note.
---

## Assumptions

- CI provider: GitHub Actions (or equivalent); stage names normative,
  tooling swappable.
- Test sharding by module keeps the feature suite under ~15 min at the
  full-platform scale; beyond that, shard by suite.
- Load tests run on staging only; production smoke = the health +
  promotion smoke suites ([tenancy & data §7](../06-tenancy/tenancy-and-data.md) step 5).
---

## Public-surface allowlist (CI-pinned)

The unauthenticated (public) surface is one consolidated allowlist spanning Phase 2 and Phase 2a additions, owned by the experience register (EXP-006) and **pinned by the CI static stage**: any public route not on the list fails the build; every listed route is probed for cross-store/cross-tenant leakage by the tenant isolation suite (item 6 above).

### Phase 2

| Route | Module | Guard |
|-------|--------|-------|
| `GET /api/v1/public/stores/{slug}/...` (catalog browse) | store | edge cache + IP throttle |
| `POST|GET /api/v1/public/stores/{slug}/carts...` | checkout | guest token + bot throttle |
| `POST /api/v1/public/checkout/{id}:capture` | checkout | strict throttle + idempotency |
| `POST /api/v1/public/returns`, `GET .../returns/{number}` | returns | order+email verification |
| `POST /api/v1/public/giftcards/balance` | giftcards | masked response |
| `GET /api/v1/public/tracking/{trackingNumber}` | shipping | coarse status only |
| `POST /api/v1/public/webhooks/channels/{provider}` | channels | provider HMAC only |

This list is exhaustive for Phase 2; CI pins it ([authorization & policy §8](../04-security/authorization-and-policy.md)).

### Phase 2a additions

| Route | Guard |
|-------|-------|
| `GET /api/v1/public/stores/{slug}/pages|articles|menus|metaobjects` | edge cache + IP throttle |
| `GET /api/v1/public/stores/{slug}/locations` | edge cache |
| `GET /api/v1/public/stores/{slug}/feeds/{code}` | 302 signed file; coarse throttle |
| `GET /api/v1/public/stores/{slug}/sitemap.xml` | edge cache |
| Draft-order invoice payment entry | via existing public checkout funnel paths ([commerce domains §10.2](../14-domains/commerce/store-checkout.md)) |

Exhaustive for Phase 2a; CI pins it alongside the Phase 2 list ([authorization & policy §8](../04-security/authorization-and-policy.md)).
---

## Delta verification mapping

Every row of the [Delta matrix](../03-delta/delta-isolation.md#delta-015-delta-matrix-normative) maps to a standing suite (DELTA-016):

| Delta row | Suite |
|-----------|-------|
| kernel↛domains, module↛module | Deptrac layers + architecture tests (`AssertNoCrossModuleJoins`) |
| tenant↔tenant (DB/cache/search/files/queues/events/exports/AI) | `AssertTenantIsolation` matrix suite |
| public surface | allowlist pin test (above) + throttle tests |
| extension isolation | hook sandbox suite (Phase 5) |
| blast radius | chaos/degradation drills (SRE schedule) |
| AI agent safety | agent authorization suite (sampled allow / full deny log assertions) |

Domain-level verification items (migrated from the phase documents into each domain file) are owned by the module's feature suite; the standing suites above never turn off once applicable (sequencing principle 3).
---

## Gate automation

The evidence gates in the [gates & milestones register](../23-evidence/gates-and-milestones.md) automate through these CI stages:

| Gate | Automating stage(s) |
|------|---------------------|
| GATE-0 (M0) | static: Deptrac + manifest validators; health tests |
| GATE-1 (M1) | standing: tenant isolation v1 + authorization matrix (kernel routes) |
| GATE-2 (M2/M3) | standing: token-flow E2E; feature: outbox/workflow/audit suites |
| GATE-3 (M4/M5) | standing: contract tests + GATE-SALES lifecycle suite; convergence test |
| GATE-4 (M6) | load-release: k6 profiles; security-nightly: ASVS automation |
| GATE-5 (M7) | all stages re-run on the release branch; evidence registry check |
| GATE-SALES | feature suite (order lifecycle E2E), re-run at every gate from GATE-3 |

A gate's evidence artifacts are the CI reports named in its registry entry; manual artifacts (drill reports, go/no-go) attach to the milestone record.