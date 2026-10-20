# Platform Features & Backend Frontends — Plan with Tests

**Status:** Proposed
**Date:** 2026-10-19
**Scope:** `platform-navdhya` (Laravel kernel + domain modules), the console apps that front it
(`apps/web` role workspaces, `apps/dashboard`), and the BFF that joins them (`apps/web/app/api/**`).
**Related:** `docs/IMPLEMENTATION-ROADMAP.md`, `docs/ACCEPTANCE-TESTS.md`,
`platform-navdhya/docs/19-testing/testing-strategy.md`, ADR-0024…0027.

> Every claim below was read from the working tree at the date above. Paths are relative to the repo
> root unless they start with `platform-navdhya/`. `file:line` is where the fact lives.

---

## 0. TL;DR

The **backend is the strong half**: the platform kernel plus ten domain modules are built,
capability-gated, event-emitting, and covered by ~1,154 PHPUnit tests plus a tenant-isolation
matrix. The **backend frontends are the weak half**: six role workspaces exist, but only the
`overview` section of `admin` and `platform` renders live data — every other console section across
all six roles prints *"This section is under development."* (`apps/web/components/product/RoleWorkspacePage.tsx:553`).
Worse, the console BFF proxies a long list of Laravel endpoints **that do not exist**, and the
failures are swallowed by `Promise.allSettled` + `fallback:true` envelopes, so nothing alarms today.

The plan therefore is **back-end-light and front-end-heavy**, and its first deliverable is not
a feature — it is a **contract test** that binds every console BFF proxy to a real Laravel route,
so this class of drift can never recur silently. Then the console sections are built on top of
endpoints that already exist, and only genuinely-missing ops endpoints are added.

---

## 1. Current-state inventory

### 1.1 Platform kernel — `platform-navdhya/app/Platform/*`

| Area | Controllers / entry points | Routes | Tests |
|---|---|---|---|
| **Authz** | `PolicyController`, `RoleController`, `SodRuleController`, `AuthzExplainController` | `routes/platform/api.php:31-63,175-194`; forms at `routes/platform/web.php:84-123` | `Platform/Authz/*` (11 files: `PolicyApiTest`, `RoleAdminTest`, `SodRuleAdminTest`, `AuthzExplainApiTest`, `CapabilityAuthorizerTest`, `ScopeEnforcementTest`, `SodEnforcementTest`, `SodRuntimeTest`, `StepUpTest`, `TierThrottleTest`, `DecisionCacheTest`) |
| **Identity** | `IdentitySelfServiceController`, `MfaController`, `InviteController`, `ApiKeyController`, `OAuthClientController`, `UserAdminController`, `StepUpController`, `OAuth\{Discovery,EndSession,Introspect,PushedAuthorizationRequest,Revoke}Controller`, `Federation\{FederationLogin,FederationProbe}Controller` | `routes/platform/api.php:71-163,201-226`; `routes/platform/web.php:31-69,133-148` | `Platform/Identity/*` (~45 files, incl. `PassportDualAcceptTest`, `ParEnforcementTest`, `DpopBindingTest`, `ApiKeyTest`, `UserAdminTest`, `InviteTest`, `MfaTest`) |
| **Tenancy** | `TenantSelfServiceController` + `ResolveTenantContext` middleware | `routes/platform/api.php:75`; `routes/platform/web.php` group | `Platform/Tenancy/*` (7 files) + `TenantIsolation/Phase0kIsolationMatrixTest` |
| **Api** | no controllers — `OpenApiComposer`, `WebMutations`, `ErrorEnvelope`, `CursorPagination` | `routes/platform/api.php:80` (`openapi.json`) + `api:compose` command | `Platform/Api/ApiComposeTest`, `ErrorEnvelopeTest` |
| **Features** | feature-flag service | — | `Platform/Features/FeatureFlagsTest` |
| **Outbox** | transactional outbox + `OutboxHandlers` | — (drain via command) | `Platform/Outbox/OutboxTest` |
| **Observability** | `ReadinessCheck` | `routes/platform/web.php:25` (`/ready`) | `Platform/Observability/HealthTest` |
| **Audit** | `AuditWriter`, `AuditLog` | — (written by controllers) | no dedicated test — exercised indirectly (e.g. `PaymentWebhookTest`) |
| **Modules** | `ModuleRegistry`, `ManifestValidator` | `routes/api.php:22-33` mounts every module | `Platform/Modules/*` (2 files) |
| **Console (artisan)** | 28 command classes under `app/Platform/**` + `app/Modules/**` (e.g. `api:compose`, `platform:sync-permissions`, `search:reindex`, `seo:import`) | — | `Platform/DoctorCommandTest`, `SyncPermissionsCommandTest`, `AuthzExplainCommandTest`, `RetireSigningKeyCommandTest` |

**Kernel capability catalogue** — `platform-navdhya/config/platform.php:70-82`:
`platform:policy:view|configure`, `platform:authz:explain`, `platform:user:view|manage`,
`platform:role:view|manage`, `platform:sod:view|configure`, `platform:apikey:view|manage`.

### 1.2 Domain modules — `platform-navdhya/app/Modules/*`

All ten ship a `module.json` and a `ModuleServiceProvider` (ports bound there; **routes mount
file-based** via `ModuleRegistry` in `routes/api.php:22-33` under `auth.any` + `tenant`).

| Module | `module.json` capabilities | Scopes | Admin/host endpoints | Capability-gated? | Tests |
|---|---|---|---|---|---|
| **Booking** | *(none)* | `booking:self`, `booking:read` | reservations CRUD + `:cancel/:confirm/:reschedule`, reviews, `instant-search` (`Routes/api.php:18-50`) | scope + `throttle.tier` only | 8 files (`BookingLifecycleTest`, `BookingReservationTest`, `BookingFulfillmentTest`, `InstantSearchTest`, …) |
| **Catalog** | `catalog:item:view`, `catalog:item:manage` (sensitive) | — | `items` CRUD + `:publish/:archive`, `prices`, `quote`, `public/items` (`Routes/api.php:18-44`) | ✅ `capability:catalog:item:manage` | 4 files |
| **Customers** | `customers:support:view`, `customers:support:manage` (sensitive) | `customers:self` | auth/OTP, profile, addresses, saved, notifications, settings, discover, activity, referrals, support (customer + `support/staff/*`) (`Routes/api.php:34-162`) | ✅ staff support only | 17 files |
| **Onboarding** | `onboarding:application:create|view|submit`, `onboarding:review:view|approve|reject` | `onboarding:applicant`, `onboarding:reviewer` | applications start/draft/submit/confirm, `review/queue`, `:approve/:reject/:request-info` (`Routes/api.php:16-35`) | ✅ review + `stepup:2` | 8 files |
| **Pandit** | `pandit:profile:view|manage`, `pandit:assignment:manage` | `pandit:read`, `pandit:self`, `pandit:admin` | browse, self-service, `admin/profiles` + `:verify/:suspend/:reactivate`, `admin/assignments` (`Routes/api.php:27-83`) | ✅ admin group | 4 files |
| **Payment** | *(none)* | — | webhook only (`Routes/api.php:21`) | signature-verified | 2 files |
| **Search** | `search:index:reindex`, `search:analytics:view`, `search:privileged:view`, `search:pii:view` | `search:read` | `search`, `suggest`, `trending`, `entities`, `index-docs`, `analytics`, `privileged` (`Routes/api.php:20-37`) | privileged/analytics | 5 files (+ `Unit/Search/*`) |
| **SeoContent** | *(none)* | — | `pages`, `pages/{slug}`, `registry`, `search-docs` (`Routes/api.php:22-25`) | no | 1 file |
| **Temple** | `temple:profile:view|manage`, `temple:puja:manage`, `temple:manager:manage`, `temple:dashboard:view|manage` | `temple:read|self|partner|admin` | public profiles, partner temples/pujas/managers/dashboard, `admin/profiles` + verbs (`Routes/api.php:23-116`) | ✅ | 5 files |
| **Vendor** | `vendor:profile:view|manage`, `vendor:product:manage`, `vendor:order:manage`, `vendor:payout:view` | `vendor:read|self|partner|admin` | partner vendors/products/inventory/orders/payouts, `admin/vendors` + `:verify/:suspend` (`Routes/api.php:22-65`) | ✅ | 3 files |

### 1.3 Backend frontends — the consoles

Access control lives in `apps/web/proxy.ts` — Next 16's `proxy` (middleware replacement). The file
already handles the production dashboard redirect and header seeding, but performs **no access check**;
`apps/web/app/admin/layout.tsx` and `apps/web/app/platform/layout.tsx` add only `robots: noindex`.
Page access control is therefore **absent at the edge**; it would have to come from the BFF (which
requires a session token per call) plus per-page checks that do not exist yet.

| Console | Files | Sections (`lib/product/role-config.ts`) | Section status |
|---|---|---|---|
| `/admin` | `app/admin/[[...section]]/page.tsx`, `layout.tsx` | overview, users, pandits, vendors, managers, bookings, content, catalog, seo, payouts, support, settings | **All 12 sections live** (W1b/W2/W3, this pass) |
| `/platform` | `app/platform/[[...section]]/page.tsx`, `layout.tsx` | overview, system, users, roles, security, billing, api, logs, settings | **All 9 sections live** (W1a/W2, this pass) |
| `/manager` | `app/manager/[[...section]]/page.tsx` | overview, operations, assignments, quality, cities, reports, settings | `overview` → mock `ManagerDashboard`; rest → placeholder |
| `/vendor` | `app/vendor/[[...section]]/page.tsx` | overview, orders, catalog, fulfillment, invoices, profile, settings | mock; rest → placeholder |
| `/pandit` | `app/pandit/[[...section]]/page.tsx` | overview, leads, calendar, services, earnings, verification, reviews, profile, settings | mock; rest → placeholder |
| `/user` | `app/user/[[...section]]/page.tsx` | overview, bookings, favorites, reviews, payments, profile, settings, support | mock; rest → placeholder |
| `/settings` | `app/settings/page.tsx` | single page | — |

Placeholder renderer: `apps/web/components/product/RoleWorkspacePage.tsx:538-554`.
Live dashboards: `apps/web/components/dashboard/AdminDashboardReal.tsx:100` (`AdminDashboardReal`),
`:224` (`SuperAdminDashboardReal`), which call `/api/admin/*` and `/api/platform/stats`.

> **Scope caveat (D5).** `apps/web/proxy.ts:18-26` redirects `/user`, `/pandit`, `/vendor` and
> `/manager` to `NEXT_PUBLIC_CUSTOMER_ORIGIN` (default `https://app.navdhya.com`) when
> `NEXT_PUBLIC_PRODUCTION_REDIRECTS === 'true'`. In production, therefore, **only `/admin` and
> `/platform` are served by `apps/web`**; the other four consoles are dev/staging previews. W1a/W1b
> are unambiguously `apps/web`; W1c–W1f must target whichever app serves them in production.

**BFF route handlers** (`apps/web/app/api/**/route.ts`, 52 files). The console-relevant ones and
their Laravel targets:

| BFF route | Laravel path it calls | Exists in `routes/platform/api.php`? |
|---|---|---|
| `/api/admin/roles` | `/platform/roles` | ✅ |
| `/api/admin/users` | `/platform/users` | ✅ |
| `/api/admin/stats` | `/platform/users`, `/platform/roles`, `/platform/bookings`, `/platform/content`, `/platform/payouts` | ⚠️ 2/5 exist |
| `/api/admin/bookings` | `/platform/bookings` | ❌ |
| `/api/admin/content` | `/platform/content` | ❌ |
| `/api/admin/seo` | `/platform/seo` | ❌ |
| `/api/admin/support` | `/platform/support` | ❌ |
| `/api/admin/payouts` | `/platform/payouts` | ❌ |
| `/api/platform/roles` | `/platform/roles` | ✅ |
| `/api/platform/users` | `/platform/users` (`tenant_id=all`) | ✅ (but `all` is not a real tenant) |
| `/api/platform/stats` | `/platform/stats/{users,revenue,system,cities,security}` | ❌ all 5 |
| `/api/platform/security` | `/platform/security` | ❌ |
| `/api/platform/settings` | `/platform/settings` | ❌ |
| `/api/platform/api` | `/platform/api` | ❌ |
| `/api/platform/logs` | `/platform/logs` | ❌ |
| `/api/platform/billing` | `/platform/billing` | ❌ |

Backend client: `apps/web/adapters/backend/laravel-client.ts` (`laravelEnabled()` gated on
`CUSTOMER_BACKEND=laravel` + `NAVDHYA_API_BASE`; envelope + code mapping at `:28-54`).

**Sibling apps:**
- `apps/dashboard` (`@navdhya/dashboard`) — separate, thin: `app/(app)/{bookings,customers,pujas,settings}`,
  `app/(auth)/login`, its own BFF auth (authorize/callback/logout/backchannel-logout/me) and **one** test
  (`app/api/auth/__tests__/federation.test.ts`). Overlaps `/admin` + `/manager` scope.
- `apps/customer` (`@navdhya/customer`) — no test files (root `tests/architecture/apps-customer.test.ts` is a
  known pre-existing failure).
- `apps/partner` (`@navdhya/partner-app`) — vitest suite; onboarding review + temple/vendor BFF tests.

### 1.4 Test estate and the commands that run it

| Suite | Command | Rough size |
|---|---|---|
| Laravel (sqlite) | `cd platform-navdhya; php vendor/bin/phpunit` | ~1,154 tests |
| Laravel (MySQL gate) | `cd platform-navdhya; php vendor/bin/phpunit -c phpunit.mysql.xml` | isolation matrix |
| Root vitest | `pnpm exec vitest run` | ~130 files incl. `tests/architecture/*` |
| Architecture gate | `pnpm run check:architecture` | 31 files |
| `apps/web` jest | `pnpm --filter @navdhya/web run test` | 51 test files |
| `apps/dashboard` / `apps/partner` vitest | `pnpm --filter @navdhya/dashboard run test` / `…partner…` | small |
| E2E | `pnpm --dir e2e test` (Playwright) + `e2e/run.ps1` | 1 UI spec (customer auth only) |

---

## 2. Gap analysis (ranked)

| ID | Sev | Gap | Evidence | Why it matters |
|---|---|---|---|---|
| **G1** | HIGH | **No console access control.** The edge entry point `apps/web/proxy.ts` (Next 16's `middleware` replacement) handles dashboard redirects and header seeding but performs **no access check**; admin/platform layouts only set `noindex`. | `apps/web/proxy.ts`; `apps/web/app/{admin,platform}/layout.tsx`; no `middleware.ts` anywhere | Harmless while data is mock. The moment W1/W2 wire real data, `/platform/*` and `/admin/*` become an unauthenticated control plane. |
| **G2** | HIGH | **Console BFF proxies 13+ non-existent Laravel endpoints** and hides the 404s. | §1.3 table; `apps/web/app/api/admin/stats/route.ts:29-43` uses `Promise.allSettled` + `fallback` | Dashboards silently show empty panels; the drift is invisible to CI. |
| **G3** | HIGH | **All console sections except two overviews are placeholders.** | `RoleWorkspacePage.tsx:538-554` | 6 roles × ~7 sections = ~40 surfaces that are nav entries with no function. |
| **G6** | MED | **Capability chain not seeded**, so console role/SoD/api-key management 403s in a fresh env. | documented in docs/22-runbooks/unified-auth-curl-scoreboard.md | Local/QA cannot exercise the surfaces they are testing. | **Partially resolved**: runbook SQL block now seeds all 10 platform capabilities (6 original + 4 new). A code-level seeder (ConsoleRoleSeeder) is still open as W3 follow-up. |
| **G5** | MED | **Missing ops endpoints** the console wants: platform-wide bookings, content queue, SEO queue, payouts, support escalation, stats, logs, billing, security, settings. | §1.3 ❌ rows | Must be either built (new kernel surfaces) or the console must render what exists. |
| **G7** | MED | **`apps/dashboard` overlaps `apps/web` admin/manager** with its own auth stack. | §1.3 | Two control planes → duplicated guards, drift, double maintenance. |
| **G8** | MED | **Module capabilities with no console surface:** `search:index:reindex`, `search:analytics:view`, `search:privileged:view`, `catalog:item:manage`, `temple:manager:manage`, `vendor:payout:view`, `onboarding:review:*`. | §1.2 vs §1.3 | Backend ships controls nobody can press. |
| **G9** | LOW | **No console E2E.** `e2e/ui.spec.ts` covers only the customer auth flow. | `e2e/` | Regressions in the console are only caught by unit tests. |
| **G10** | LOW | **`apps/customer` has zero tests.** | §1.3 | Known; root architecture test already red. |
| **G11** | LOW | **`platform/users` + `platform/roles` are single-tenant**, but the console asks for `tenant_id=all`. | `apps/web/app/api/platform/users/route.ts:33`; `routes/platform/api.php:152-163` | "All tenants" super-admin view is not representable by the API contract. |

---

## 3. The plan

Six workstreams. Each lists **deliverables → features/endpoints → tests → exit criteria**.
Sequencing (`W0 → W1 → W2 → W3`) is deliberate: the guard rails land before the features.

### W0 — Contract guard rails — ✅ LANDED (this pass)

**Goal:** make drift impossible and every console fail-closed before any feature work.

**Landed deliverables**
- `tests/architecture/console-bff-contract.test.ts` (new) — parses `apps/web/**` for every literal path
  passed to `laravelGet/Post/Patch/Delete`, extracts the real routes from
  `platform-navdhya/routes/platform/api.php` + each module's `Routes/api.php` (mounted at
  `/api/v1/{module code}`), and asserts each call resolves to a real route. New drift fails the build.
  The pre-existing dangling paths are pinned in `KNOWN_DANGLING` — **15 dated entries**, each naming
  `removal: 'W2'`. A second test fails if a pinned entry is no longer called or has been implemented
  (so the list cannot rot), and a third guards the parser itself (fails if it ever extracts nothing).
- `apps/web/proxy.ts` (edited) — console access guard inserted **after** the production dashboard
  redirect and the strangler cutover, so a console already handed to another origin hops rather than
  bouncing through `/login`. Fail-closed: no credential on a console path → `302 /login?next=<path>`.
  The dev-role cookie admits a **matching** console in non-production only (confined to its own console;
  `SuperAdmin` may enter any), and is **never** a credential when `NODE_ENV === 'production'`.
  The session token is opaque, so the edge proves *presence* only; validity and capabilities stay
  server-side (plan D4).
- `apps/web/lib/product/console-access.ts` (new) — pure, edge-safe policy: `consoleForPath`,
  `isConsolePath`, `canAccessConsole`, `consoleLandingForRole`. No `next/*` or `next/headers` imports.
- `apps/web/lib/product/__tests__/console-access.test.ts` (new) — 9 cases incl. segment-safety
  (`/usertools`, `/administrator` are not consoles) and SuperAdmin-everywhere.

**Files touched:** 3 new (`tests/architecture/console-bff-contract.test.ts`,
`apps/web/lib/product/console-access.ts`, `apps/web/lib/product/__tests__/console-access.test.ts`),
2 edited (`apps/web/proxy.ts`, `apps/web/__tests__/proxy.test.ts` — 8 new guard cases; the existing
"serve locally" case now carries a session cookie, since a console path needs a credential).

**Tests / gates (all green as landed)**
- `pnpm exec vitest run tests/architecture/console-bff-contract.test.ts` → 4/4.
- `pnpm --filter @navdhya/web exec jest __tests__/proxy.test.ts lib/product/__tests__/console-access.test.ts` → 23/23.
- `pnpm --filter @navdhya/web run typecheck` → clean.
- `pnpm run check:architecture` → 32 files, 89 passed, 1 failed (pre-existing `apps-customer.test.ts`).

**Exit criteria met:** drift is now a build failure; the 15 known dangling proxies are pinned with a
removal workstream; no console is reachable without a credential; the dev-role bypass cannot exist in
production.

**Deliberate scope limits (carried into W1):** the edge cannot verify token validity or capabilities,
and there is no `403` page — a mismatched dev role is redirected to its own console instead. Hard
role/capability enforcement for real sessions is a W1 layout/BFF concern.

> ⚠️ W0's contract test will be red on day one because of G2. That is the point: it converts a silent
> bug into a tracked list. The allow-list entry is temporary and must cite W2.

### W1 - Console surfaces (the bulk of the work) - **W1a done (platform: users, roles, api, logs, settings, billing), W1b done (admin: users, pandits, vendors, managers, support, bookings, content, seo, payouts)**

**Goal:** replace every "under development" placeholder with a real surface backed by an endpoint that
already exists (W1) or a new one (W2).

One slice per role, ordered by who actually uses it. Each slice = BFF route(s) + section components +
tests, and reuses the existing `ProductPageShell` / `StatCard` / `StatusBadge` primitives.

| Slice | Sections to build | Backed by (existing endpoints) |
|---|---|---|
| **W1a — SuperAdmin (`/platform`)** | users, roles, security, api, logs, settings, system, billing | `GET/POST platform/roles`, `memberships/{id}/roles`, `platform/users` + verbs, `platform/api-keys` + verbs, `platform/oauth/clients`, `authz/explain`, `policies` |
| **W1b — Admin (`/admin`)** | users, pandits, vendors, managers, bookings, content, seo, payouts, support, settings | `platform/users`, `pandit/admin/profiles` + verbs, `vendor/admin/vendors` + verbs, `onboarding/review/queue` + decisions, `customers/support/staff/tickets` |
| **W1c — Manager (`/manager`)** | operations, assignments, quality, cities, reports | `onboarding/review/queue`, `pandit/admin/assignments`, `booking/reservations`, `customers/support/staff/tickets` |
| **W1d — Vendor (`/vendor`)** | orders, catalog, fulfillment, invoices, profile | `vendor/partner/vendors/{id}/{orders,products,inventory,payouts}` |
| **W1e — Pandit (`/pandit`)** | leads, calendar, services, earnings, verification, reviews, profile | `pandit/{assignments,availability,earnings,reviews,profile}` |
| **W1f — User (`/user`)** | bookings, favorites, reviews, payments, profile, settings, support | `booking/reservations`, `customers/{saved,activity,referrals,settings,support/tickets}`, `customers/profile` |

For each slice:
- **BFF**: add one `route.ts` per section under `apps/web/app/api/console/<role>/<section>/route.ts`
  (new unified prefix; the existing `/api/admin|platform/*` routes get re-pointed, not duplicated),
  each `laravelEnabled()`-guarded with an honest empty state instead of a fake `fallback`.
- **UI**: `apps/web/components/console/<role>/<Section>.tsx`, server-fetched, table/list + action
  affordances for capability-gated verbs (step-up flows where the API requires `stepup:2`).
- **Capability awareness**: the UI hides actions the session cannot perform, but the **server remains
  the authority** (never rely on hiding).

**Tests per slice**
- BFF: `apps/web/app/api/console/<role>/__tests__/<section>.test.ts` — jest; mock the laravel client,
  assert envelope shape, 401/403 mapping, and that a missing backend renders an empty state (not a
  fake success).
- UI: `apps/web/components/console/<role>/__tests__/<Section>.test.tsx` — renders rows, empty state,
  and hides a sensitive action when the capability is absent.
- Laravel: extend existing `tests/Feature/Admin*SurfaceTest.php` where the section maps to a platform web
  action so console posts keep parity with the API (one service, two transports).
- Architecture: add every new section to `tests/architecture/console-bff-contract.test.ts` coverage.

**Exit criteria per slice:** zero "under development" strings for that role
(`grep -r "under development" apps/web/components` returns nothing for its sections), all new tests green,
`apps/web` `tsc --noEmit` clean, `pnpm --filter @navdhya/web run test` green.

### W2 - Console backend endpoints (only what W1 cannot reuse) - **W2 complete: audit logs + stats + settings + billing + ops/* (bookings, content, seo, payouts) + security + system**

**Goal:** close G2/G5 by *either* building the missing kernel surfaces *or* re-pointing the console to
existing module routes. Prefer reuse; build only platform-wide aggregates that no single module owns.

Proposed new kernel surfaces (all under the existing `auth` + `ResolveTenantContext` group in
`routes/platform/api.php`, each with a capability):

| New endpoint | Capability | Serves |
|---|---|---|
| `GET platform/ops/bookings` | `platform:ops:view` | Admin bookings, Manager operations, stats |
| `GET platform/ops/content` | `platform:ops:view` | Admin content queue |
| `GET platform/ops/seo` | `platform:ops:view` | Admin SEO queue |
| `GET platform/ops/payouts` | `platform:finance:view` | Admin payouts, Vendor payouts roll-up |
| `GET platform/ops/support` | `platform:ops:view` | Admin support escalation |
| `GET platform/stats` (one aggregate, not five) | `platform:ops:view` | SuperAdmin stats |
| `GET platform/audit/logs` | `platform:audit:view` | SuperAdmin logs (reads `AuditLog`) |
| `GET platform/billing`, `GET platform/settings` | `platform:billing:view`, `platform:config:manage` | SuperAdmin billing/settings |

Each endpoint: controller + capability declaration (`config/platform.php` **or** a module manifest) +
`WebMutations`-compatible response so a future console form can post to the same action + feature test.

**Tests**
- `platform-navdhya/tests/Platform/Ops/OpsSurfaceTest.php` — per endpoint: authorized 200, missing
  capability 403 `authorization_denied`, cross-tenant isolation, audit record written.
- Add the new capabilities to the SQL-seed block in `docs/22-runbooks/unified-auth-curl-scoreboard.md`
  (closes G6) and to the W1 capability-aware UI.
- `tests/architecture/console-bff-contract.test.ts` allow-list shrinks to empty → the assertion flips
  from "documented exception" to "must match".

**Exit criteria:** every BFF proxy path resolves; `php vendor/bin/phpunit` green; phpstan clean on
changed files; the allow-list in W0 is removed.


### W3 - Capability + seed operationalization (G6, G8) - **complete: request-info + approve/reject (admin) + catalog item manage (admin) + search reindex (platform) + temple manager manage (partner) + vendor payout view (partner, already surfaced) + capability enforcement on temple manager + vendor payout routes**

- Add a **role-template seeder** (`platform-navdhya/database/seeders/ConsoleRoleSeeder.php` or a
  `platform:seed-console-roles` command) that grants the catalogue by role: SuperAdmin → all;
  Admin → `platform:user|role|sod|apikey|policy:*` + module `*:view|manage`; Manager → onboarding review +
  pandit assignment; Vendor/Pandit → their self scopes; User → customer scopes.
- Surface the W1d/W1e controls for G8 capabilities: Search reindex button
  (`search:index:reindex` → `GET search/index-docs`), Catalog item manage, Temple manager manage,
  Vendor payout view, Onboarding review decisions (step-up 2).
- Document the mapping in `platform-navdhya/docs/22-runbooks/unified-auth-curl-scoreboard.md`.

### W4 - App consolidation decision (G7) - **decided: scope apps/dashboard to a distinct operator persona with edge guard + contract test (guard landed this pass)**
- `platform-navdhya/tests/Platform/Authz/ConsoleRoleSeederTest.php` — after seeding, each role's console
  actions authorize; a Vendor cannot reach an Admin action (negative).
- Extend `tests/feature` surfaces for the newly-exposed G8 verbs.


Two viable outcomes, each with tests:
1. **Merge** `apps/dashboard` into `apps/web` consoles; delete the duplicate auth stack; port its one test.
2. **Scope** `apps/dashboard` to a distinct operator persona (e.g. on-ground ops with a narrower
   surface) and add its own guard + contract test.
### W5 - Test estate completion (G9, G10) - **started: apps/customer smoke test suite + vitest config + console E2E spec**
Either way: a `proxy.ts` guard (the edge entry point) + a console contract test for the dashboard, and
its BFF routes join the W0 binding test.


- `apps/customer` — add a minimal vitest suite (BFF contract + `auth` + `routes`) to clear the existing
  red architecture test.
- `e2e/console.spec.ts` + `e2e/run.ps1` phase — signup → login → `/platform/overview` renders real
  stats → open `/platform/roles` → see the seeded role → logout → `/platform` redirects to login.
  Extends the existing stub/stub-tenant harness; no new infra.

---

## 4. Test strategy (what "with tests" means here)

| Layer | Where | Asserts | Command |
|---|---|---|---|
| **Contract (new, W0)** | `tests/architecture/console-bff-contract.test.ts` | every BFF proxy path ↔ a real Laravel route; pinned drift cannot rot | `pnpm exec vitest run tests/architecture/console-bff-contract.test.ts` |
| **Edge guard (new, W0)** | `apps/web/__tests__/proxy.test.ts`, `apps/web/lib/product/__tests__/console-access.test.ts` | no console without a credential; dev-role confined to its console; no prod bypass | `pnpm --filter @navdhya/web exec jest __tests__/proxy.test.ts lib/product/__tests__/console-access.test.ts` |
| **BFF unit** | `apps/web/app/api/console/**/__tests__/*.test.ts` | envelope, error mapping, honest empty state | `pnpm --filter @navdhya/web run test` |
| **UI unit** | `apps/web/components/console/**/__tests__/*.test.tsx` | rows, empty, capability-hidden actions | ″ |
| **Laravel feature** | `tests/Feature/Admin*SurfaceTest.php`, `tests/Platform/Ops/*` | authz, step-up, tenant scope, audit, parity web/API | `cd platform-navdhya; php vendor/bin/phpunit` |
| **Capability/SoD** | `tests/Platform/Authz/ConsoleRoleSeederTest.php` | positive + negative per role | ″ |
| **Architecture** | `tests/architecture/*` | boundaries, envelope doctrine, no drift | `pnpm run check:architecture` |
| **E2E** | `e2e/console.spec.ts` | real browser console flow | `pnpm --dir e2e test` |

**Guiding rules**
- A console page must never render a fabricated success. `fallback:true` empty envelopes are
  replaced by an explicit "backend disabled" state that tests assert.
- Hiding a button is UX; the capability gate stays on the server. Every hidden action has a Laravel
  negative test proving the direct API call is refused.
- New design tokens follow the locked Navdhya palette (Aubergine structural, Flame orange accent,
  Ivory canvas) — no green.

---

## 5. Sequencing, milestones, exit criteria

| Milestone | Workstreams | Exit criteria |
|---|---|---|
| **M1 — Guard rails** | W0 — ✅ landed | Contract test exists and accounts for every BFF path (15 pinned, removal = W2); consoles require a credential; edge bypass impossible in production; architecture gate green |
| **M2 - Control plane** | W1a done, W1b done (all 11 admin sections), W2 done (audit logs + stats + settings + billing + ops/* + security + system) | All console sections live on real endpoints; contract allow-list empty; architecture gate green |
| **M2 - Control plane** | W1a done, W1b done (all 12 admin sections), W2 done (audit logs + stats + settings + billing + ops/* + security + system), W3 in progress (request-info + approve/reject + catalog + search reindex) | All console sections live on real endpoints; contract allow-list empty; architecture gate green |
| **M4 — Customer + hardening** | W1f, W4, W5 | `/user` live; app overlap resolved; console E2E green; full `pnpm run verify` green |

**Full gate before any merge:**
`pnpm run verify` (turbo tests) + `cd platform-navdhya; php vendor/bin/phpunit` +
`php vendor/bin/phpstan analyse` + `vendor/bin/pint --test` + `pnpm run check:architecture`.

---

## 6. Open decisions (blocking)

- **D1 — W0 contract-test posture.** ✅ **DECIDED: dated allow-list.** Shipped in W0: the 15 known
  dangling paths are pinned in `KNOWN_DANGLING` with `removal: 'W2'`, new drift fails the build, and a
  second test fails if a pinned entry stops dangling (so the list must be cleaned up, not abandoned).
- **D2 — Build vs reuse for G5.** Do platform-wide ops aggregates belong in the kernel
  (`platform/ops/*`), or should the console read each module's existing admin routes and aggregate in
  the BFF? *Recommendation: aggregate in the BFF for module-owned data; build kernel endpoints only for
  cross-module roll-ups (stats, audit logs).*
- **D3 - `apps/dashboard` future** (W4): **DECIDED - scope.** Kept as a distinct operator persona app with its own edge guard (`apps/dashboard/proxy.ts`, landed this pass) and federation auth stack. Migration to `apps/web` consoles remains possible later; the 4 pages (bookings, customers, pujas, settings) map to existing `apps/web` admin sections.
- **D4 — Console role mapping source.** Is the session's role authoritative (`/api/auth/me`), or do we
  derive console access purely from capabilities? *Recommendation: capabilities for actions, role only
  for which console shell to render.*
- **D5 — Which app actually serves each console? (raised in W0, affects W1 scope).**
  `apps/web/proxy.ts` already redirects `/user`, `/pandit`, `/vendor`, `/manager` to
  `NEXT_PUBLIC_CUSTOMER_ORIGIN` (default `https://app.navdhya.com`) when
  served by `apps/web`; the other four consoles are preview surfaces there and belong to the customer
  app. **W1c–W1f (Manager/Vendor/Pandit/User) must target whichever app serves them in production** —
  building them in `apps/web` would ship previews. *Recommendation: confirm ownership before starting
  W1c–W1f; W1a/W1b (SuperAdmin, Admin) are unambiguously `apps/web`.*
  `apps/web/proxy.ts` redirects `/user`, `/pandit`, `/vendor` and `/manager` to `NEXT_PUBLIC_CUSTOMER_ORIGIN` (default `https://app.navdhya.com`) when `NEXT_PUBLIC_PRODUCTION_REDIRECTS === 'true'`. So in production only **`/admin` and `/platform`** are served by `apps/web`; the other four consoles are preview surfaces there.
  **RESOLUTION (D5):** The customer app (`apps/customer`, origin `app.navdhya.com`) is the intended production owner, but it currently has no `/user`, `/pandit`, `/vendor`, or `/manager` routes (only `/pandits` for browsing). So these four consoles are dev/staging-only in `apps/web` today. W1c-W1f should be implemented in `apps/customer` when those routes are added, OR remain as dev-only previews. The `apps/partner` app handles temple/vendor self-serve but not pandit/manager consoles. **D5 is resolved: apps/web owns /admin and /platform in production; /user, /pandit, /vendor, /manager are dev/staging previews pending migration to apps/customer.**
 /user is now aliased to / in apps/customer (this pass). /pandit, /vendor, /manager remain dev/staging-only pending dedicated routes in apps/customer or apps/partner. Pandit self-serve surface added to apps/partner (/pandit route + profile/assignments/earnings BFF).
---

## 7. Proposed ADRs

- **ADR-0028 — Console↔backend contract test** (the W0 binding test as the enforcement point for G2).
- **ADR-0029 — Console edge guard & capability-aware UI** (server authority, client affordance).
