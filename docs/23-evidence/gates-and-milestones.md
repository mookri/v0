# Gates & Milestones

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 15 §1–7; pasted plan §20 (GATE-SALES), §37 (GATE-0…5)

This file is the single register of **how the build is gated and sequenced**: the cumulative gate ladder GATE-0…GATE-5 plus the standing GATE-SALES lifecycle gate, the 90-day milestone map M0–M7 with exit criteria, the post-90-day phase cadence (now including the P2a Shopify-parity and P4a ERP-parity milestones), and the evidence registry that collects the proving artifacts. Gate automation (which CI stage runs what) is pinned in the [gate automation table](../19-testing/testing-strategy.md#gate-automation); suites themselves are specified in the [testing strategy](../19-testing/testing-strategy.md). The build-phase risk register lives at [ARCH-019](../02-architecture/architecture-master.md#arch-019-risk-register) and is reviewed at every gate.

A milestone is not complete because its features demo — it is complete when its gate's evidence artifacts exist and are green. Gates are cumulative: every milestone re-runs all previous gates, and standing suites never turn off once applicable.

---

## Gate register

| Gate | Milestone(s) | Automation (CI stage) | Depends on |
|------|--------------|------------------------|------------|
| [GATE-0](#gate-0-foundations--ci-spine-gate-m0) | M0 | static stage | — |
| [GATE-1](#gate-1-kernel-isolation-gate-m1) | M1 | standing suites | GATE-0 |
| [GATE-2](#gate-2-token--kernel-services-gate-m2m3) | M2 | standing: token-flow E2E suite; feature: outbox/workflow/audit suites | GATE-1 |
| [GATE-3](#gate-3-contract--commercial-core-gate-m4m5) | M4 | standing: contract tests (composed OpenAPI) + GATE-SALES lifecycle suite; Blade↔API convergence test | GATE-2 |
| [GATE-4](#gate-4-hardening-gate-m6) | M6 | load-release: k6 profiles (api-baseline, token-issuance, event-flood); security-nightly: ASVS automation | GATE-3 |
| [GATE-5](#gate-5-ga-gate-m7) | M7 | all CI stages re-run on the release branch; evidence registry completeness check | GATE-4 |
| [GATE-SALES](#gate-sales-order-lifecycle-gate) | from M5 onward | feature suite (order lifecycle E2E), re-run at every gate from GATE-3 | GATE-3 |

Each gate below lists its required evidence artifacts; rows are placeholders until implementation populates the registry.

---

## GATE-0 Foundations & CI spine gate (M0)

- id: GATE-0
- status: ACTIVE
- owner: QA + Platform Architect
- version: 1.0.0
- derived_from: doc 14 §10; pasted plan §37
- depends_on: —
- affects: M0 exit

**Milestone:** M0 — Foundations & CI Spine.

**Automation:** static stage — Deptrac layer enforcement + module manifest validators, plus health tests (`/up`, `/ready`) — see the [gate automation table](../19-testing/testing-strategy.md#gate-automation).

**Required evidence (registry placeholders):**

| Artifact | Description | Status |
|----------|-------------|--------|
| G0-DEPTRAC | Deptrac baseline report proving Platform ↛ Modules enforcement (violation test included) | PLACEHOLDER |
| G0-MANIFEST | Module manifest validation report for the skeleton `platform` module | PLACEHOLDER |
| G0-HEALTH | Health/readiness test report (`/up`, `/ready` against central DB + Redis) | PLACEHOLDER |

---

## GATE-1 Kernel isolation gate (M1)

- id: GATE-1
- status: ACTIVE
- owner: QA + Platform Architect
- version: 1.0.0
- derived_from: doc 14 §10; pasted plan §37
- depends_on: GATE-0
- affects: M1 exit

**Milestone:** M1 — Kernel Core: Identity, Tenancy, Authorization.

**Automation:** standing suites — tenant isolation v1 (model matrix) + authorization matrix against kernel routes — see the [gate automation table](../19-testing/testing-strategy.md#gate-automation).

**Required evidence (registry placeholders):**

| Artifact | Description | Status |
|----------|-------------|--------|
| G1-ISOLATION | Tenant isolation suite v1 matrix artifact — all kernel tables + fail-closed cases | PLACEHOLDER |
| G1-AUTHZ | Authorization matrix artifact for kernel routes (tenant self-service, identity) | PLACEHOLDER |
| G1-SOD | SoD engine report: every seeded conflict rule with positive + negative assignment tests | PLACEHOLDER |

---

## GATE-2 Token & kernel services gate (M2/M3)

- id: GATE-2
- status: ACTIVE
- owner: QA + Platform Architect
- version: 1.0.0
- derived_from: doc 14 §10; pasted plan §37
- depends_on: GATE-1
- affects: M2 exit

**Milestone:** M2 — Token Platform, M3 — Kernel Services.

**Automation:** standing: token-flow E2E suite; feature: outbox/workflow/audit suites — see the [gate automation table](../19-testing/testing-strategy.md#gate-automation).

**Required evidence (registry placeholders):**

| Artifact | Description | Status |
|----------|-------------|--------|
| G2-TOKEN | Token-flow E2E report (code+PKCE, refresh rotation + reuse detection, device code, step-up) | PLACEHOLDER |
| G2-OUTBOX | Outbox relay report incl. event-flood behavior (SKIP LOCKED batching) | PLACEHOLDER |
| G2-WF | Workflow & event suite report (state machines, compensation, audit coverage) | PLACEHOLDER |

---

## GATE-3 Contract & commercial core gate (M4/M5)

- id: GATE-3
- status: ACTIVE
- owner: QA + Platform Architect
- version: 1.0.0
- derived_from: doc 14 §10; pasted plan §37
- depends_on: GATE-2
- affects: M4 exit

**Milestone:** M4 — API Platform & Admin Spine, M5 — Phase 1 Commercial Core.

**Automation:** standing: contract tests (composed OpenAPI) + GATE-SALES lifecycle suite; Blade↔API convergence test — see the [gate automation table](../19-testing/testing-strategy.md#gate-automation).

**Required evidence (registry placeholders):**

| Artifact | Description | Status |
|----------|-------------|--------|
| G3-CONTRACT | Contract test report — composed OpenAPI v1 validates; every Phase 1 operation covered | PLACEHOLDER |
| G3-SALES | GATE-SALES order lifecycle E2E report (exact event sequence + causation ids) | PLACEHOLDER |
| G3-CONVERGE | Blade↔API convergence report — zero console-only mutations in Phase 1 modules | PLACEHOLDER |

---

## GATE-4 Hardening gate (M6)

- id: GATE-4
- status: ACTIVE
- owner: QA + Platform Architect
- version: 1.0.0
- derived_from: doc 14 §10; pasted plan §37
- depends_on: GATE-3
- affects: M6 exit

**Milestone:** M6 — Hardening & Load Baseline.

**Automation:** load-release: k6 profiles (api-baseline, token-issuance, event-flood); security-nightly: ASVS automation — see the [gate automation table](../19-testing/testing-strategy.md#gate-automation).

**Required evidence (registry placeholders):**

| Artifact | Description | Status |
|----------|-------------|--------|
| G4-LOAD | Load baseline report — all profiles at target rps with p95 table verification | PLACEHOLDER |
| G4-SEC | Security suite report — ASVS automation + abuse cases on public/OAuth/SCIM surfaces; zero open high findings | PLACEHOLDER |
| G4-DRILL | Restore drill report (signed) + tenant promotion dry-run checksums + key rotation drill | PLACEHOLDER |

---

## GATE-5 GA gate (M7)

- id: GATE-5
- status: ACTIVE
- owner: QA + Platform Architect
- version: 1.0.0
- derived_from: doc 14 §10; pasted plan §37
- depends_on: GATE-4
- affects: M7 exit

**Milestone:** M7 — v1.0 GA Gate.

**Automation:** all CI stages re-run on the release branch; evidence registry completeness check — see the [gate automation table](../19-testing/testing-strategy.md#gate-automation).

**Required evidence (registry placeholders):**

| Artifact | Description | Status |
|----------|-------------|--------|
| G5-RERUN | Release-branch full gate re-run report (standing suites incl. token-flow E2E + isolation) | PLACEHOLDER |
| G5-REGISTRY | Evidence registry completeness check — every artifact above populated and linked | PLACEHOLDER |
| G5-GONOGO | Go/no-go record against the NFR table (availability posture, latency baselines, scale margins) | PLACEHOLDER |

---

## GATE-SALES Order lifecycle gate

- id: GATE-SALES
- status: ACTIVE
- owner: QA + Backend engineer 1
- version: 1.0.0
- derived_from: pasted plan §20; doc 07 §11
- depends_on: GATE-3
- affects: M5 exit and every subsequent gate

**Automation:** feature suite — the quote-to-cash order lifecycle E2E (quote → order → reserve → fulfill → invoice → capture → paid) asserting the exact event sequence and causation ids. Once introduced at GATE-3 it is **re-run at every later gate**, including the P2a and P4a parity phase exits, so parity work can never regress the commercial spine.

**Required evidence (registry placeholders):**

| Artifact | Description | Status |
|----------|-------------|--------|
| SALES-E2E | Order lifecycle E2E suite report with event-sequence assertions | PLACEHOLDER |
| SALES-RERUN | Re-run attestations at GATE-4, GATE-5, P2a exit, P4a exit | PLACEHOLDER |

---

## Sequencing principles

1. **Kernel before business logic.** No domain module starts until its
   kernel dependencies exist and are tested ([architecture master §2](../02-architecture/architecture-master.md), principle 1).
2. **Vertical slices over horizontal layers.** Every milestone ships a
   request-path slice (route → policy → domain → outbox → audit) rather
   than "all migrations, then all services".
3. **Gates are cumulative.** Each milestone re-runs every previous gate;
   standing suites (tenant isolation, authz matrix, token E2E) run from
   the first week they become applicable and never turn off.
4. **Docs track code.** Any contract deviation discovered during build is
   fixed in the spec (additive diff) in the same PR ([testing strategy §12.6](../19-testing/testing-strategy.md)).
5. **No phase overlap within a milestone.** Phases 2–6 start only after
   the 90-day plan's exit review.

---
---

## Milestone map (90 days)

```text
Week  1─2   M0  Foundations & CI spine
Week  2─4   M1  Platform kernel core (identity, tenancy, authZ)
Week  4─6   M2  Token platform (OAuth2/OIDC, grants, federation, SCIM)
Week  5─7   M3  Kernel services (events/outbox, workflow, audit, files,
                notifications, search, settings, flags, observability)
Week  6─8   M4  API platform + Blade admin spine
Week  7─11  M5  Phase 1 commercial core (catalog → invoicing)
Week 11─12  M6  Hardening, load baseline, release candidate
Week 13     M7  v1.0 GA gate
```

Dependencies: M0 → M1 → M2/M3 (partially parallel) → M4 → M5 → M6 → M7.

---
---

## Milestone details

### M0 — Foundations & CI Spine (Weeks 1–2)

**Build:**
- Repo scaffolding per [domain framework §1](../09-domain-framework/domain-framework.md): `app/Platform/` skeleton,
  `app/Modules/`, `app/Support/`, `tests/` layout.
- Toolchain wiring: Pint, PHPStan (level 6 + baseline file), Deptrac
  with the layer sketch ([domain framework §7](../19-testing/testing-strategy.md)), PHPUnit on MySQL 8 service.
- CI pipeline stages per [testing strategy §10](../19-testing/testing-strategy.md) (static → unit → feature skeleton).
- Laravel base: config for central/tenant connections ([tenancy & data §2.3](../06-tenancy/tenancy-and-data.md)),
  environment matrix, `/up` + `/ready` stubs.
- Module registry + manifest loader + `module.json` JSON schema
  ([domain framework §2](../02-architecture/architecture-master.md)/§3.2); `module:list` command.
- Kernel test helpers: `TenantTestCase`, factories base,
  envelope assertions ([testing strategy §3](../19-testing/testing-strategy.md)).
- Git hygiene: branch model, PR template with OpenAPI-diff checklist,
  pre-commit Pint + secrets scan.

**Exit criteria:**
- CI green end-to-end on a skeleton module ("platform" module) with
  manifest validation running.
- Deptrac enforces Platform ↛ Modules (violation test proves failure).
- `/ready` checks central DB + Redis; health tests pass.
- Zero `App\\Modules` imports in kernel (architecture test).

### M1 — Kernel Core: Identity, Tenancy, Authorization (Weeks 2–4)

**Build ([tenancy & data](../06-tenancy/tenancy-and-data.md), 03 §1, 04):**
- Central schema: users, tenants, memberships, organizations,
  org_assignments, roles, permissions, role_permissions,
  membership_role_assignments, sod_rules, plans.
- Tenant registry + `TenantContext` + resolution middleware (web
  session + API header paths) + fail-closed exceptions.
- `BelongsToTenant` trait + global scopes + architecture guards.
- Connection resolver (shared mode; dedicated-mode plumbing with
  resolver tests against a second DB).
- Permission registry + `platform:sync-permissions`; role seeding
  (built-in roles per [authorization & policy §3.2](../04-security/authorization-and-policy.md)).
- RBAC evaluation order ([authorization & policy §5.1](../04-security/authorization-and-policy.md)) as kernel middleware + policies
  base; org-scope resolution.
- SoD engine (assignment-time) with seeded conflict rules.
- User lifecycle state machine + invites + MFA (TOTP) enrollment.
- Tenant isolation suite v1 (model matrix generator, [testing strategy §5](../19-testing/testing-strategy.md)).
- Authorization matrix generator ([testing strategy §6](../19-testing/testing-strategy.md)) running against kernel
  routes.

**Exit criteria:**
- Tenant isolation suite green for all kernel tables + fail-closed cases.
- AuthZ matrix: kernel routes (tenant self-service, identity) fully
  covered; matrix artifact published.
- SoD: every seeded rule has positive + negative assignment tests.
- Multi-membership user switches tenants; suspended membership denies
  immediately.
- PHPStan/Pint/Deptrac green; coverage ratchet initialized.

### M2 — Token Platform (Weeks 4–6)

**Build ([identity & token platform](../04-security/identity-and-token-platform.md)):**
- Passport integration + kernel OIDC layer: discovery, JWKS, userinfo.
- Client registry profiles (first-party web, internal service) +
  audience enforcement.
- Grants: code+PKCE, refresh rotation + reuse detection, client
  credentials (tenant-bound, sensitive-scope exclusion), device code
  (RFC 8628) with approval UI.
- Token claims per §4.1 (tenant_ids, org_id, acr, amr, sid) + jti
  revocation list + introspection endpoint (restricted).
- Step-up middleware (ACR 2) + `/oauth/stepup` flow + API
  `step_up_required` envelope.
- Sessions: Redis-backed, drift re-auth, session list/revoke API.
- Federation: IdP config schema, email-domain routing, OIDC RP, SAML SP
  (library), JIT provisioning, account linking rules.
- SCIM 2.0 endpoints behind `module.platform_scim` flag.
- Token-flow E2E suite ([testing strategy §7](../19-testing/testing-strategy.md)) — all nine scenarios.
- Brute-force controls ([identity & token platform §11](../04-security/identity-and-token-platform.md)) + security event stream wiring.

**Exit criteria:**
- All [identity & token platform §13](../04-security/identity-and-token-platform.md) E2E scenarios pass in CI (real MySQL, real Passport).
- Reuse-detection scenario kills lineage + sessions + emits event.
- Enforced-domain SSO blocks password login; JIT creates
  user+membership; revoked membership cannot re-enter.
- SCIM deactivation revokes tokens within one request cycle.
- JWKS rotation overlap verified; introspection audience-restricted.

### M3 — Kernel Services (Weeks 5–7, overlaps M2)

**Build ([kernel services](../07-kernel/kernel-services.md)):**
- DomainEvent envelope + EventPublisher + outbox table + relay worker
  (SKIP LOCKED claims, backoff, dead-letter, replay command).
- Event catalog sync (`platform:sync-events`) + schema validation.
- Workflow engine: state machine definitions, guards, hooks,
  `workflow_instances`; approval policies + `approval_requests`
  with SoD runtime checks.
- Audit: `audit_logs`, hash chain, observers, redaction list,
  chain-verification job + command.
- Notifications: templates, channel drivers (in-app, email), delivery
  log, quiet hours.
- Files: FileStore, tenant paths, signed URLs, upload limits.
- Search: driver interface + MySQL FULLTEXT driver + reindex command.
- Settings registry + precedence walk; feature flags + plan bundles.
- Observability: structured logging, request-id middleware, `/ready`
  full implementation, usage meters, alert classes.
- Idempotency infrastructure ([kernel services §12](../07-kernel/kernel-services.md)).

**Exit criteria:**
- Outbox rollback/replay/ordering/dead-letter tests green ([kernel services §13](../07-kernel/kernel-services.md)).
- Audit hash chain: tamper injection detected by verification command;
  chain break emits P1 event.
- Workflow: guard/hook rollback/approval SoD/expiry tests green.
- Settings precedence + unregistered-key fail-fast tests green.
- `/ready` dependency-failure matrix (each stub → non-200).

### M4 — API Platform & Admin Spine (Weeks 6–8, overlaps M3 tail)

**Build ([API platform](../07-kernel/api-platform.md)):**
- Kernel API middleware stack: scope enforcement, idempotency gate,
  rate limiting tiers (Redis sliding window), error envelope renderer +
  error-code registry.
- Pagination/filter/sort kernel helpers; ETag/If-Match support;
  colon-verb action routing convention.
- OpenAPI composition (`api:compose`, `api:diff`) + shared
  components + discovery endpoint.
- Webhook platform: subscriptions, HMAC delivery, retries, circuit
  breaker, delivery log, secret rotation.
- Blade admin spine: Velzon layout wiring, auth screens, tenant
  switcher, manifest-driven sidebar, step-up confirmation components,
  dashboard shell.
- Convergence harness: shared controller action pattern + architecture
  test (web action set == API action set).
- Contract test harness ([testing strategy §4](../19-testing/testing-strategy.md)) with kernel components.

**Exit criteria:**
- Error-code registry: every code produced by a test; unknown codes in
  responses fail CI.
- Idempotency: replay / mismatched reuse / concurrent in-progress green.
- Webhooks: signature vectors, retry schedule, SSRF guards green.
- Admin login → tenant switcher → manifest nav renders; throttle headers
  present on API responses.
- Composed OpenAPI validates; discovery endpoint serves it.

### M5 — Phase 1 Commercial Core (Weeks 7–11)

**Build ([commercial core domains](../14-domains/sales/pricing.md)), in dependency order:**

| Week | Module | Key slices |
|------|--------|-----------|
| 7 | catalog | products/variants/categories/uom; activation flow; ProductLookup contract |
| 7–8 | customers, suppliers | master data, credit limit ledger, supplier onboarding workflow + SoD |
| 8 | pricing | price lists, overrides, PriceResolver with provenance |
| 8–9 | sales | quotes, orders, state machine, credit-check guard, approval thresholds |
| 9 | inventory | warehouses/locations, movements ledger, reservations, concurrency (FOR UPDATE), transfers/adjustments/cycle counts |
| 9–10 | payments, invoicing | payment accounts, capture/apply, payout batches + SoD, invoices/credit notes/bills, 3-way match, numbering schemes, dunning |
| 10–11 | integration | order lifecycle E2E wiring (reserve → fulfill → invoice → capture → paid), Blade screens for every module, OpenAPI fragments per module |

Per-module checklist ([domain framework §8](../02-architecture/architecture-master.md)) enforced at PR level.

**Exit criteria ([commercial core domains §11](../14-domains/sales/pricing.md), all automated):**
- Order lifecycle E2E with exact event sequence + causation ids.
- Stock race: concurrent confirmation — one wins, one gets
  `inventory_insufficient_stock`; no negative stock.
- 3-way match hold/release + self-approval SoD block.
- Idempotent order create + invoice issue replays.
- All Phase 1 tables in tenant isolation matrix; contract tests green
  for every operation; AuthZ matrix extended with Phase 1 routes.
- Convergence test: no console-only mutations in any Phase 1 module.
- Blade admin: full quote-to-cash walkthrough possible in UI.

### M6 — Hardening & Load Baseline (Weeks 11–12)

**Build/verify:**
- Full security suite ([testing strategy §9](../19-testing/testing-strategy.md)) against staging: ASVS automation,
  abuse cases on public/OAuth/SCIM surfaces, redaction audit.
- Load profiles v1 ([testing strategy §11](../19-testing/testing-strategy.md)): api-baseline, token-issuance,
  event-flood; baseline numbers recorded.
- Observability pass: alert classes wired, dashboards for outbox lag /
  queue depth / auth failures.
- Operational runbooks drafted: backup/restore drill executed once,
  promotion runbook dry-run on staging ([tenancy & data §7](../06-tenancy/tenancy-and-data.md)), key rotation drill.
- Performance fixes from load findings; p95 table ([architecture master §6.2](../02-architecture/architecture-master.md))
  verified for implemented API classes.
- Documentation freeze: spec diff review against implemented contracts.

**Exit criteria:**
- Security suite green; zero open high findings.
- Load baseline recorded; no failing profile at target rps.
- Restore drill report signed; promotion dry-run checksums match.
- All P1 alert paths exercised in a game-day (simulated chain break +
  refresh reuse).

### M7 — v1.0 GA Gate (Week 13)

**Release checklist:**
- All [testing strategy §10](../19-testing/testing-strategy.md) gates green on the release branch; standing suites
  re-run.
- Token-flow E2E + tenant isolation re-run on release candidate.
- OpenAPI v1.0.0 artifact published; changelog + deprecation register
  initialized.
- Ops readiness: `/ready` in LB, log shipping, metering visible,
  break-glass tested.
- Go/no-go against [architecture master §6](../02-architecture/architecture-master.md) NFR table (availability posture, latency
  baselines, scale margins).

**GA definition:** a new tenant can be provisioned, configure COA-free
commercial basics (catalog → customers → quote → order → fulfill →
invoice → payment), invite users with roles, and operate via Blade admin
and `/api/v1` with full audit — with all gates green.

---
---

## Post-90-day phase cadence

After M7, phases ship as **phase milestones** with the same exit-gate model (each ≈ 6–10 weeks depending on team size; sequence fixed). The parity phases **P2a (Shopify parity)** and **P4a (ERP parity)** are first-class milestones in this cadence; their full task specs live in the [execution specs register](../specs/README.md).

| Milestone | Content | Gate additions |
|-----------|---------|----------------|
| P2-Commerce | store, checkout, discounts, shipping, returns, giftcards, subscriptions, POS, channels | checkout-storm load profile; public-allowlist pinning; offline POS sync suite |
| **P2a-Shopify parity** | markets, order operations, customer accounts, storefront content, selling plans, local commerce, discovery, data tools ([tasks](../specs/README.md)) | GATE-SALES re-run on parity flows; markets price-overlay suite; import idempotency suite; public-allowlist re-pin |
| P3-Finance | gl + posting engine, tax, ar, ap, banking, assets, budgets, reports | double-entry property tests; golden-dataset report regression; period-lock suite |
| P4-Enterprise Ops | hr, payroll, projects, service, manufacturing, supply chain | payroll determinism + SoD suite; PII-leakage suite (sensitive fields absent from events/logs) |
| **P4a-ERP parity** | CRM, expenses, quality, serial/lot tracking, India statutory, contracts, finance extensions ([tasks](../specs/README.md)) | statutory sandbox conformance; CRM read-model reconciliation; quality-gate event suite; GATE-SALES re-run |
| P5-Ecosystem | sdk, partners, webhooks GA, marketplace, custom objects, SCIM GA | RFC 7644 conformance; hook latency budget tests; partner grant revocation latency |
| P6-Intelligence | analytics, forecasting, anomaly, recommendations, copilot, agents | projection reconciliation; copilot privilege + budget tests; agent human-in-the-loop suite |

Every phase milestone ends with: standing suites green, new tables in the isolation matrix, contract tests for all new operations, load profile delta check, and the phase's verification requirements fully automated.

### P2a — Shopify parity milestone

**Entry:** M7 GA green and P2-Commerce exited (checkout, payments, POS, channel baseline present).

**Exit criteria:**
- All 49 NEW-v1 Shopify parity rows trace to `Done` tasks in the [2A register](../specs/README.md) with passing acceptance criteria.
- GATE-SALES re-run green including parity flows: draft order → edit → deposit capture; BOPIS pickup lifecycle; subscription contract lifecycle.
- Import/export suite green on the single shared surface; idempotent replay proven for every v1 importer.
- Public-surface allowlist re-pinned with the Phase 2a additions; cross-store leakage probes green.

### P4a — ERP parity milestone

**Entry:** P4-Enterprise Ops exited (hr, payroll, manufacturing, supply chain baseline present).

**Exit criteria:**
- All 47 NEW-v1 ERP parity rows trace to `Done` tasks in the [4A register](../specs/README.md) with passing acceptance criteria.
- India statutory adapters pass sandbox conformance against the pinned schema versions (IRN, e-way, GSTR-1/3B, TDS/TCS, PF/ESI/PT exports).
- CRM read models reconcile against their source event streams; quality gates block and release stock correctly.
- GATE-SALES re-run green (ERP extensions must not regress the commercial spine).

---

## Ownership matrix

| Area | Owner role | Milestones |
|------|-----------|-----------|
| Module system, registry, enforcement tooling | Platform engineer | M0, standing |
| Tenancy, connections, promotion runbook | Platform engineer | M1, M6 |
| Identity, token platform, federation, SCIM | Security engineer | M1, M2 |
| Authorization, SoD, policies | Security engineer + backend | M1 |
| Events/outbox, workflow, audit, kernel services | Backend engineer 1 | M3 |
| API platform, webhooks, OpenAPI tooling | Backend engineer 2 | M4 |
| Blade admin spine + module screens | Frontend engineer | M4, M5 |
| Phase 1 modules (paired) | Backend 1 + 2 | M5 |
| Standing suites, fixtures, CI | QA + all | M0–M7 |
| Load/security automation, runbooks | QA + security | M6, M7 |

---
---

## Evidence registry

The registry below is the consolidated checklist of proving artifacts for v1.0 GA. Every row stays `PLACEHOLDER` until implementation produces the artifact; GATE-5 refuses sign-off while any row is open. Artifacts are attached to their gate section above and mirrored here for audit.

| Gate | Artifact | Owner | Status |
|------|----------|-------|--------|
| GATE-0 | G0-DEPTRAC / G0-MANIFEST / G0-HEALTH | Platform engineer | PLACEHOLDER |
| GATE-1 | G1-ISOLATION / G1-AUTHZ / G1-SOD | QA + Security engineer | PLACEHOLDER |
| GATE-2 | G2-TOKEN / G2-OUTBOX / G2-WF | QA + Backend 1 | PLACEHOLDER |
| GATE-3 | G3-CONTRACT / G3-SALES / G3-CONVERGE | QA + Backend 2 | PLACEHOLDER |
| GATE-4 | G4-LOAD / G4-SEC / G4-DRILL | QA + Security | PLACEHOLDER |
| GATE-5 | G5-RERUN / G5-REGISTRY / G5-GONOGO | Platform Architect | PLACEHOLDER |
| GATE-SALES | SALES-E2E / SALES-RERUN | QA + Backend 1 | PLACEHOLDER |

---

## Acceptance

The sequence is accepted when:
1. Every milestone's exit criteria are automatable via [testing strategy](../19-testing/testing-strategy.md) gates
   (no manual-only criteria except go/no-go judgment).
2. No milestone depends on undocumented kernel behavior (every
   referenced capability has a spec section).
3. The critical path (M0→M1→M2→M4→M5→M6→M7) fits 13 weeks at the
   assumed team shape with ≤ 20% slack consumed by M6 hardening.

In addition, this register is complete only when the P2a and P4a parity milestones above carry exit criteria for every NEW-v1 row in the [Shopify](../01-product/shopify-feature-inventory.md) and [ERP](../01-product/erp-feature-inventory.md) feature inventories.
