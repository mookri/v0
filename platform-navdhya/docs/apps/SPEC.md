# Multi-App Frontend Portfolio — `apps/` Specification v1

**Scope:** `apps/web`, `apps/customer`, `apps/partner`, `apps/dashboard` + the shared package layer,
workspace/task configuration, cross-app identity, tenant isolation, and per-app acceptance
**Repository:** pnpm workspaces + Turborepo `2.11.5`; all four apps on Next.js `16.4.2` / React `19`
**Slices:** S165 → S184 (Search S65–84 · Calendar S85–104 · Onboarding S105–124 · Comms S125–144 · Trust S145–164)
**Status:** Implemented (S165–S184; ADRs 0068–0078 recorded under `platform-navdhya/docs/21-adrs/`).
**Read this first:** the brief for this module is written as a *greenfield blueprint*. The repository
is not greenfield, and in the most consequential respect it is **inverted**: the hard backend
controls the brief asks for (checkout holds, double-entry ledger, maker/checker with runtime SoD,
effective-dated tax rules, signed webhooks, federated per-app sessions) **already exist and are
largely correct**, while the frontends that should expose them are duplicated four ways, missing
three of the four console surfaces, and carry a **booking state machine that disagrees with the
backend's**. §2 lists what not to build.

**Related specs:** [`search`](../search/SPEC.md) ·
[`seo-content`](../seo-content/SPEC.md) · [`onboarding`](../onboarding/SPEC.md) ·
[`communications`](../communications/SPEC.md) · [`trust`](../trust/SPEC.md)

**Methodology:** tasks follow [`docs/specs/README.md`](../specs/README.md)
§2 template, §3 AC shapes, §4 Definition of Done. Existing platform plans this spec must reconcile
with rather than restate: [`docs/CI-CD-PIPELINE-PLAN.md`](../../../docs/CI-CD-PIPELINE-PLAN.md),
[`docs/API-CONTRACT.md`](../../../docs/API-CONTRACT.md),
[`docs/ACCEPTANCE-TESTS.md`](../../../docs/ACCEPTANCE-TESTS.md).

---

## 0. How to read this document

```text
Epic       E-AP-<nn>           e.g. E-AP-02
Task       T-AP<epic>-<nn>     e.g. T-AP02-03
AC         AC-T-AP02-03-<n>
Defect     A-<nn>              verified defect in the shipped portfolio (§1.2)
App        web | customer | partner | dashboard
Principal  devotee | partner-org-member | staff (three authorization contexts)
```

Normative words: **MUST**, **SHOULD**, **MAY**.

Two honesty notes about evidence in this document:
1. **Version-specific claims are checked against the installed toolchain, not memory.** Anything
   touching Turborepo configuration follows the repository's `AGENTS.md` rule and is read from the
   bundled docs at `node_modules/.pnpm/turbo@2.11.5/node_modules/turbo/docs/` first — those pages
   match the installed version and are cited inline in §3.
2. **Legal and standards positions from the source brief are marked (unverified here)** — this
   repository contains no copy of the RBI Payment Aggregator Master Direction, CBIC Section 52
   material, OWASP ASVS, or WCAG. They are routed to counsel/finance as gates (§11), never
   converted into constants. One factual correction is available locally: the brief cites
   "Next.js 16.4.6"; this repo pins **16.4.2** across all four apps (§3.1).

Finally, a note on what this portfolio is actually for. Four apps is not the goal; four
*contexts* is. A devotee booking a grandparents' shraddha at 11pm, a pandit accepting three
assignments from a phone in a temple courtyard with one bar of signal, and a checker approving
₹4.8 lakh of payouts on a corporate laptop have genuinely different failure tolerances. The
recurring risk in this document is that a single shared abstraction smooths those differences away —
and the second is that the same rule comes to exist in two places with two answers, which §1.2 shows
has already happened to the booking lifecycle.

---

## 1. Evidence baseline

### 1.1 What actually ships

**Scale, measured (TS+TSX LOC, excluding `.next`):**

| App | Files | LOC | Route groups | Reality |
| --- | --- | --- | --- | --- |
| `web` | 591 | **87,628** | `(auth) (legal) (seo)` | The real product. Public portal, 1,279-file content corpus, search, console panels, ad network, ~40 route groups' worth of surface. |
| `customer` | 203 | 15,671 | `(auth) (protected)` | Devotee commerce: booking, family/spiritual profiles, reminders, privacy, pujas. |
| `partner` | 137 | 10,741 | **none** | Pandit + temple operations: assignments, earnings, approvals, council queue. |
| `dashboard` | **32** | **3,705** | `(app) (auth)` | Nearly empty: 5 pages + OIDC federation plumbing. |

**Backend controls the brief asks for — already built:**

| Capability the brief requests | Shipped | Verified quality |
| --- | --- | --- |
| Availability holds with expiry | ✅ `Booking/Application/InitiateCheckoutHoldUseCase.php:32` (15 min), `:38` `lockForUpdate()`, `:45-74` quote-drift guard, `:84` transaction; `Models/StockReservation.php:28-32` `HELD/COMMITTED/CONSUMED/RELEASED/EXPIRED`; reaped by `Console/ReapExpiredHoldsCommand.php:48` | **Correct.** This is the brief's §5.3 design, implemented. |
| Double-entry ledger | ✅ `Finance/Domain/DoubleEntryLedgerService.php` with `UnbalancedJournalException`; `ledger_journals` / `ledger_entries` (migration `2026_11_08_000008_…:66,82`), fixed account codes `LedgerEntry.php:26-37` | Real, with an imbalance guard. Gaps in §1.2 A-10. |
| Maker/checker payout | ✅ `Finance/Domain/MakerCheckerPayoutService.php:70-71` throws `MakerCheckerViolationException` when maker == checker; `:98-99` conditional `PENDING_APPROVAL → APPROVED` update | **This is the brief's §9.1 rule, enforced in code**, with the correct atomic form. |
| Segregation of duties | ✅ `Platform/Authz/SodEvaluator.php` — assignment-time `evaluate()` (`:32`), self-grant severity (`:62`), and a genuine **runtime** `blockingForCapability()` (`:78`) reading live grants; wired at `PolicyEvaluationEngine.php:65,218`; seeded rules `sod.po_vendor_bill`, `sod.vendor_master_payments`, `sod.role_grant_self`; tested in `tests/Platform/Authz/SodEnforcementTest.php` | Better than the brief asked: SoD is checked at grant time *and* at action time. |
| Effective-dated tax rules | ✅ `tax_rule_versions (jurisdiction, version, effective_from, effective_to, rules_json)` unique on `(jurisdiction, version)` (migration `2026_11_08_000005_…:43-53`), plus `IndiaGstCalculator` and AE/GB/US jurisdictions. **No hardcoded tax rates found** in apps or packages | Matches the brief's §10.2 requirement. |
| Signed payment callbacks + event dedup | ✅ `PaymentWebhookController.php:52-55` `hash_equals(hash_hmac('sha256', …))`; `Models/Central/ProviderEventInbox.php` inbox | Correct signature discipline. |
| Tenant resolution fail-closed | ✅ `Platform/Tenancy/Http/Middleware/ResolveTenantContext.php:20-44` — header-derived **and cross-checked against authenticated memberships**, with `tenant_header_missing / tenant_not_in_token / tenant_suspended / membership_inactive` (`:88-98`); `AssertTenantIsolation` matrix + 562-line tenancy doc (`TEN-005…TEN-017`) | Matches the brief's §13.2 requirement 1. |
| MFA + step-up | ✅ `Platform/Identity/MfaService.php` (316 LOC), `StepUpController.php`, `MfaStepUp`, `MfaLockedException`; enforced as `RequiresAcr::class.':2'` on ~9 privileged platform routes | Server-side only — the gap is the UI (A-14). |
| Per-app isolated sessions | ✅ four independent federated OIDC sessions (`apps/*/server/federation.ts`, `oidc.ts`, `backchannel-logout.ts`), `SameSite=Lax`, per-host `__session` cookie | Answers the brief's §12.2 cookie-scope concern by construction — see A-01 for the cost. |
| Dependency-aware CI | ✅ `dorny/paths-filter` → `apps_changed`/`packages_changed` (`.github/workflows/ci.yml:59-75`), per-app typecheck/build matrices (`:102-124`, `:285-318`), `architecture + drift + contract` job (`:214-237`) incl. `tooling/scripts/domain-check.js`, gitleaks + `pnpm audit --prod` blocking + SBOM/Trivy | A good foundation with three specific holes (§1.2 A-12/A-13). |

**So the portfolio's actual problem is not missing architecture. It is that four frontends
re-implement the same transport, auth and domain rules independently, one of those copies has
already diverged from the backend authority, and the console that should expose mature
financial controls does not exist yet.**

### 1.2 Defect register

**A-01 — The API transport layer is copied four times and the copies have drifted.**
`apps/web/adapters/backend/laravel-client.ts` (216), `apps/customer/adapters/backend/laravel-client.ts`
(215), `apps/partner/adapters/backend/laravel-client.ts` (178), plus `partner/adapters/backend/partner-api-client.ts`
(235), `web|customer/adapters/backend/customer-api-client.ts` (52 / 208 — **same filename, different
content**), and `dashboard/api/client.ts` (83) + `dashboard/server/backend.ts` (94).
A direct diff shows semantic drift, not cosmetic drift: `customer` adds `dpopProof` and maps
`quote_stale`, `resource_unavailable`, `invalid_state_transition` → `CONFLICT`; `web` maps
`step_up_required → 'FORBIDDEN'` which `customer` lacks — and a comment inside `customer`
explicitly records this as *"mismatch M9"*. **A known, documented, unresolved divergence.**
Meanwhile `packages/api-client` is a 26-LOC stub that **no app's `package.json` depends on**. The
shared layer the brief proposes already exists as a directory and is empty of content while four
private copies carry the behaviour. Consequence in practice: a new backend error code is handled in
one app and silently mishandled in three, and step-up — the control that gates payout approval —
is not uniformly surfaced.

**A-02 — The booking state machine exists twice and the two copies disagree about legality.**
`packages/booking/src/domain/booking-rules.ts:4-17`: 11 statuses, **`NO_SHOW` absent entirely**, and
no `PENDING_PAYMENT → CONFIRMED` or `ASSIGNED → CONFIRMED` edges.
`platform-navdhya/app/Modules/Booking/Domain/BookingLifecycle.php:13-50`: 12 statuses **including
`NO_SHOW`**, and permits exactly those edges.
Two authoritative-looking transition tables with different answers is the most serious structural
defect in the portfolio, because the TypeScript copy is what drives UI affordances: the devotee app
will refuse to render a transition the backend accepts, or offer one the backend rejects, and the
disagreement is invisible until a user hits it. It is also the **sixth** instance of the
"declaration without enforcement" pattern catalogued in `docs/onboarding/SPEC.md` §1.3 — here the
declaration is *duplicated* rather than unenforced, which is worse, because one copy looks tested.
And it corroborates the trust spec's TS-02 independently: `NO_SHOW` is declared, unwritten,
**and unknown to the frontend**.

**A-03 — Auth/session/federation plumbing is copied four times.**
`web/server/session-cookie.ts` (150) + `web/lib/auth/auth-client.ts` (164);
`customer/server/auth.ts` + `session-refresh.ts` (323) + `adapters/session.ts`;
`partner/server/auth.ts` (47) + `session.ts` (~40); `dashboard/server/session.ts` (105);
and a **~230-line `server/federation.ts` in each of the four apps**. No `@navdhya/auth-client`
exists to hold any of it. CSRF handling, cookie flags, refresh timing and backchannel-logout
behaviour are therefore four independent implementations of the same security contract, with
different line counts — which is precisely the surface where drift becomes a vulnerability rather
than a bug.

**A-04 — Idempotency is enforced on one booking path and silently auto-generated on the newer one.**
`ReservationController.php:141-149` requires the `Idempotency-Key` header **fail-closed** (400 when
absent), with conflict codes at `:190`, `:295`, and a uniqueness constraint
`UNIQUE(tenant_id, actor_id, verb, idempotency_key)` on `booking_commands`
(migration `2026_11_08_000002_…:30`) consumed via `Domain/CommandDeduplicator.php:103,107`.
But `ReservationV2Controller.php:134,343,448` does `header('Idempotency-Key', Str::uuid())` —
**the server manufactures a fresh key per request, so the guarantee is void on that path.**
Git history says the customer BFF is being migrated *to* V2 (commit `d7c7278`, "migrate customer BFF
to Booking V2"). So the actively-migrated-to code path is the one without the protection the older
path has. This is the worst possible direction of regression: a control that exists, works, and is
being abandoned.

**A-05 — There is no uncertain-payment state, and no reconciliation.**
`Payment/Models/PaymentIntent.php:43-49` defines exactly
`PENDING / SUCCEEDED / FAILED / REFUNDED`. `'UNKNOWN'` appears only as a `match` default for
*operation type*, never as a status (`PaymentWebhookController.php:309`), and a repo search finds
**no `*Reconcil*` file in `Modules/Payment` or `Modules/Booking`** (the only hit is
`Vendor/Console/ReconcileStockCommand.php`, unrelated). `DurablePaymentService.php:74,149-154`
maps a failed gateway call straight to `STATUS_FAILED`, and `BookingLifecycle.php:40` permits
`PENDING_PAYMENT → FAILED`.
The brief's §5.3 "important failure scenario" — provider accepts, client times out, booking must not
be marked failed — is therefore **live, reachable, and unhandled**: a paid devotee can be recorded as
failed with no state available to express "we don't know yet", and no job to find out. Whether a
specific timeout path has produced this in production is **(unverified)**; that the model cannot
represent it is not.

**A-06 — Assignment accept/complete are read-then-write, with no lock and no version column.**
`Pandit/…/PanditAssignmentManager.php`: `confirm()` (`:95-133`) and `complete()` (`:138-174`) call
`ownedInStatus()` (`:281-300`), which is a `SELECT` followed by a PHP `$assignment->status !==
$status` comparison. **No `lockForUpdate()` and no conditional `WHERE status = ? AND version = ?`.**
There is no `version` column at all on assignments (grep over `Modules/Pandit` finds only unrelated
`track_version`), despite `bookings` recently gaining revision machinery.
Conflict detection *does* exist and is correctly run before the write (`checkConflict` `:333-334` →
`conflictExists()` `:345-353` over `PENDING`+`CONFIRMED`, also `hasWindowConflict()` `:262`) — which
makes this a textbook time-of-check/time-of-use pair: two concurrent confirms on one pandit can each
pass the conflict check, because nothing serialises them. The only hard backstop is
`UNIQUE(pandit_id, booking_id)` (`:28`), which prevents the *same* booking being assigned twice to
the *same* pandit — **it does nothing about two different bookings in the same window**, which is
exactly Scenario A of the brief's §6.5, and §18.1's "at most one valid assignment for the
conflicting slot" requirement is therefore unmet under concurrency.
The brief's own §6.4 SQL (conditional update on `status` **and** `version`, zero-rows ⇒ conflict) is
the correct fix and is already the house pattern two modules away, in `MakerCheckerPayoutService.php:98-99`.

**A-07 — There is no `start` verb, so no service-start timestamp exists.**
Partner UI actions are `act(id, 'confirm' | 'decline' | 'complete')`
(`partner/presentation/pandit/PanditAssignmentsView.tsx:30`, `data-testid="complete-button"` `:118`)
against BFF routes `app/api/pandit/assignments/[id]/{confirm,complete,decline}/route.ts`. An orphan
`app/api/assignments/[assignmentId]/start/` directory exists, **but the PHP manager has no `start`
verb.** So the brief's `:accept / :start / :complete` trio is two of three — and the missing one is
`service_started_at`, which the trust spec §13 needs for completion evidence, which the
communications spec §15.2 needs for arrival/lateness, and which the calendar spec would want for
duration-accuracy feedback. **Three prior specs each independently identified a missing operational
timestamp; this is the single column that closes all three**, and it is the cheapest high-value
item in this document.

**A-08 — Partner earnings are computed from booking rows, not from the ledger that exists.**
`Pandit/Http/Controllers/PanditEarningsController.php:44-45` sums
`pandit_assignments.compensation_minor` on demand. Two modules away sits
`DoubleEntryLedgerService`. The brief's §9.5 requirement — *"read from an accounting ledger, not
calculate partner balances from current bookings on demand"* — is violated by exactly the shape it
warned about, and the correct substrate is already present. Compounding: the BFF returns a
hardcoded `{ total_minor: 0, breakdown: [] }` stub when Laravel is disabled
(`partner/app/api/pandit/earnings/route.ts`), so a disabled integration renders as **zero earnings**
rather than as unavailable — the same "fabricated success/absence" class as
`docs/communications/SPEC.md` C-01.
The brief's §6.6 financial distinction (platform fee ≠ GST ≠ §52 TCS ≠ TDS ≠ provider charges ≠
refunds, all separate) is unrepresentable in a sum of `compensation_minor`.

**A-09 — The ledger cannot express the payout lifecycle its own maker/checker flow needs.**
`Models/LedgerJournal.php:28-29` allows only `POSTED` and `VOID`. There is no pending/staged posting,
and reversal entries and period locks **could not be verified to exist** (no `reversal` / `period_lock`
symbols). So `SETTLEMENT_ELIGIBLE → MAKER_DRAFT → MAKER_SUBMITTED → CHECKER_REVIEW → APPROVED →
EXECUTION` (brief §9.2) is being run *around* a ledger that can only represent final postings, and a
correction must be modelled as `VOID` rather than as a compensating entry. That is a real accounting
weakness, and it is a small one to fix given the rest of the design.

**A-10 — Tenant isolation is well-designed and bypassed in 45 places.**
`withoutGlobalScopes()` appears **45 times across 18 files** — including
`Vendor/Application/StockReservationService.php` (12), `Catalog/Http/Controllers/CatalogItemController.php` (4),
`Search/Contracts/PanditSearchProvider.php` (3), `Customers/Http/Controllers/CustomerProfileController.php:261,372`,
`Customers/WebhookController.php:67`, and `Booking/Http/Controllers/ReservationV2Controller.php:936`.
Several re-add `where tenant_id` by hand (`Catalog/Domain/Pricing/TieredPriceResolver.php:46`), and a
comment at `SodRuleController.php:53-56` explains that some platform routes carry `tenant_id` in
query/body and gate in-controller rather than by middleware. **None of this is proven wrong** — most
of these are probably legitimate cross-tenant platform operations. The defect is that correctness is
asserted per-call by a human reader, with no per-call audit and no inventory, against a documented
`AssertTenantIsolation` matrix and OWASP-style isolation expectations the brief cites. 45 unlabelled
exits from a security boundary is not an implementation bug; it is a missing *proof obligation*.

**A-11 — Design tokens do not exist; the locked brand palette is copy-pasted hex.**
`packages/design-system` is **2 files / 17 LOC**. No `design-tokens` package, and **zero** brand
hex values anywhere in `packages/`. Across the apps (excluding `.next`/`node_modules`):
`#FFF7ED` × 63, `#2C2033` × 122, `#F16A24` × 67 — **~252 hardcoded brand colours**. `AGENTS.md` calls
this palette *locked*, and the distribution rules ("Orange is the signature, not the background for
everything", "keep the primary symbol recognisable in Aubergine alone") are unenforceable when the
values are string literals scattered through 87,628 lines of `web` alone.
This is the frontend face of a defect already recorded twice: `docs/communications/SPEC.md` C-12
(email renders neither brand nor tokens) and `docs/trust/SPEC.md` (status/severity enums with no
writer). Same class: a rule stated, no mechanism enforcing it.
Contrast is the reason it matters operationally. Computed values (reused from
`docs/communications/SPEC.md` §12.2 rather than re-asserted): Aubergine on Ivory **14.53:1**,
Aubergine on Flame **5.01:1**, **Flame on Ivory 2.90:1 — fails AA for normal text.** The brief's
warning about Flame-on-Ivory is correct, and a token layer with an enforced
permitted-combinations table is the only way to keep 252 call sites honouring it.

**A-12 — No accessibility gate, and no bundle-size gate.**
Zero `jest-axe` / `@axe-core` / `axe-playwright` references in any `package.json` or config, despite
the brief's §16.5 WCAG 2.2 AA target and its correct list of Navdhya-specific needs (accessible OTP
input, focus management, no colour-only status, large touch targets for a pandit standing in a
courtyard). Build artefact size is **recorded, not gated**
(`ci.yml:319-323` `du -sk .next >> $GITHUB_STEP_SUMMARY`), and `tooling/scripts/ci/gate.mjs` contains
no budget/size/a11y/perf/contract logic. An unenforced accessibility target in an app used for
religious obligation by families of every age is a real gap, not a checkbox.

**A-13 — E2E covers one app of four, and one known-failure ratchet hides web test debt.**
`e2e/playwright.config.ts:17` matches `/(ui|console|booking-full-lifecycle)\.spec\.ts/` on a single
`chromium` project with `baseURL = E2E_BFF ?? http://127.0.0.1:3000` and **no `webServer`** — so
coverage is effectively the web BFF/console. `catalog-discovery-lifecycle` and
`devotee-spiritual-lifecycle` specs exist but are **outside the default `testMatch`**, i.e. written
and not run. **No partner, dashboard or customer e2e coverage at all.** Separately, web unit tests
run through a known-failures ratchet (`tooling/scripts/ci/jest-known-failures.mjs:186` against
`ci/known-failures/web-jest.txt`, 35 lines).
Consequence: every cross-app scenario in the brief's §18 — the checkout/dispatch race, "maker tries
to approve own payout", "pandit accepts an outdated offer", "customer clears family-sharing consent"
— is **untestable in CI today**. The brief's own §22.4 verdict ("a fast public website does not prove
checkout or payout workflows are reliable") is currently unfalsifiable by this pipeline.

**A-14 — There is no TS↔OpenAPI contract test, so A-01/A-02 drift is undetectable.**
24 `openapi.yaml` files exist on the backend and `packages/api-contracts` is 5 files / 115 LOC,
hand-written. Nothing generates types from the spec and no job asserts compatibility between the TS
contracts and Laravel's published schema (the `architecture + drift + contract` job at
`ci.yml:214-237` checks *domain* drift, not API shape). **This is the mechanism-level root cause of
the duplicate-divergent pattern** in A-01, A-02 and A-03: nothing checks the copies, so they drift,
and the drift gets written down in a code comment ("mismatch M9") instead of failing a build.

**A-15 — `apps/dashboard` is 32 files. Every console surface the brief describes is absent, while
every backend control it needs is present.**
Pages are only `(app)/{page, bookings, customers, pujas, settings}` and `(auth)/login` plus `api/auth/*`
federation. Grep for `maker|checker|segregation|approval.queue` in `apps/dashboard` returns
**nothing**; `settings/page.tsx` contains no tax/billing/payout/dispatch strings; there is **no
step-up or MFA screen in any app frontend** despite `RequiresAcr:2` protecting ~9 platform routes —
meaning a checker hitting step-up gets an error, not a prompt.
Read positively, and this is the most useful reframing in the document: **the risky part is built and
tested (`SodEvaluator`, `MakerCheckerPayoutService`, `SodEnforcementTest`); the exposure part is
missing.** The brief's §9 spends ~1,500 words designing a maker/checker engine. §10 here designs the
queue against `routes/platform/api.php:200` + `platform:ops:view`, because designing that engine
again would be the single largest waste in this plan — and building a *second* approval path in the
frontend is how a payout control ends up in two places, which is A-02's lesson applied to money.

**A-16 — No PWA in `partner`, and the only service worker caches nothing.**
No `manifest.*` in any app; `apps/partner/public/` is **empty**; no `next-pwa`, `registerSW` or
workbox in partner. The one service worker in the repo is `apps/web/public/sw.js` (207 lines) and it
is **network-only** — it caches nothing. So the brief's §14.3 offline matrix (view cached schedule,
draft notes encrypted, accept/start/complete online-only, queued ops carrying revision +
idempotency key) is written against infrastructure that does not exist — and the *existing* SW would
mislead a reader into thinking a PWA layer is present. This matters more here than in a typical app:
§6's audience is a pandit with one bar of signal inside a temple, and the brief is right that the
answer is deliberately *limited* offline, not clever offline.

**A-17 — The public content corpus has two sources and they may already differ.**
`apps/web/lib/content/loader.ts:30` and `registry.ts:29` both key on
`SEO_CONTENT_SOURCE === "api"` with a **disk fallback**, so static/disk is the default and the API
path is opt-in per deployment. The disk corpus is now **1,279 files**; an earlier count in this same
session — recorded in `docs/search/SPEC.md` §1.1 and `docs/seo-content/SPEC.md` §1.1 — was **1,265**,
and the source brief said 1,265. **The corpus grew while this specification set was being written,
and nothing verifies that the API-served copy matches the disk copy.** Two corpora with an env-var
switch and no equivalence test is how a site serves stale festival dates from the fallback while
believing it serves computed ones — which is the calendar spec's generation/version discipline
(`docs/seo-content/SPEC.md` §9) failing to reach the frontend.
Related: `next/image` is used in **22** web files against **79** raw `<img>` tags, and **no
`sitemap.ts`** exists — instead `app/robots.ts`, `app/sitemap.xml/route.ts` and an `app/sitemaps`
directory, so the brief's §4.3 sitemap expectation needs reconciling with what is actually built.

**A-18 — The brief's proposed directory layout contradicts the repository in four places.**
(a) Apps use `apps/<app>/app/…`, **not** `apps/<app>/src/app/…` as §4.1/§5.1/§6.2/§7.2 sketch —
following the brief literally would create a second, unused `src/app` tree.
(b) Laravel lives at `platform-navdhya/`, not `backend/laravel/` — and the brief itself says to
retain the existing location, so no move is needed; the spec confirms and names the paths.
(c) `pnpm-workspace.yaml` includes a third glob, **`services/**`** (functions + workers), which the
brief's structure omits entirely — proposals about the package graph that ignore it will mis-resolve.
(d) The brief proposes `@navdhya/ui` / `localization` / `validation` / `date-time` / `telemetry`;
the repo has `design-system` / `i18n` / (nothing) / (nothing) / `observability` — 10 LOC of it.
Plus real overlap to reconcile, not add to: **`contracts` (21 files/344 LOC) *and* `api-contracts`
(5/115)**, and conceptual clusters around `availability`/`scheduling`/`resource-management`,
`pandit`/`partner`, `booking`/`fulfillment`/`commerce`, `search`/`discovery`/`recommendations`/
`personalization`, `finance`/`payment`/`pricing`. The brief's §2.1 wish to avoid duplicating
`packages/` is already the hardest requirement in it.

**A-19 — Frontend re-implements backend domain logic in five places.**
Beyond A-02: `packages/search/src/bm25.ts` (123 LOC) vs `Search/Services/SearchScorer.php` (recorded
as Search D-07/D-13); `packages/search/src/transliteration.ts` (175 LOC) hand-copying a PHP map that
`KeyFolder.php` / `Console/ExportSearchSchemasCommand.php` now generate (recorded as D-13 — **partly
remediated already**, worth verifying before deleting); `packages/pricing/src/application/
{calculate-quote,validate-price}.ts` + `domain/{Money,Price,Discount}.ts` vs
`Catalog/Domain/Pricing/TieredPriceResolver.php` and `Tax/Domain/CommercialQuoteService.php`;
`packages/availability/src/{domain/availability-rules.ts (isSlotAvailable), application/
{reserve-slot,release-slot}}.ts` vs `StockReservationManager.php`;
`packages/finance/src/domain/refund.ts:20,25` reducing `processedAmount`/`approvedAmount`
client-side. Recommendation scoring and panchang math in TS: **none found** — so the brief's §2.3
prohibition is violated in five specific, enumerable places, all fixable by the
generated-contracts mechanism in A-14 plus deletion of pure-client arithmetic.

### 1.3 The pattern, across six modules

`docs/onboarding/SPEC.md` §1.3 named *declaration without enforcement*. The portfolio adds the
complementary failure: **enforcement without a single declaration** — a correct control existing in
four copies, one of which (A-02) disagrees with the others and none of which CI compares.

| Failure | Where |
| --- | --- |
| Declared, never enforced | Search D-01 · Calendar P-03/P-08 · Onboarding T-06/T-12 · Comms C-01/C-05 · Trust TS-02/TS-03 |
| Enforced, duplicated, drifted | **A-01** transport · **A-02** booking lifecycle · **A-03** auth/session · A-19 pricing/availability/refund |
| Enforced in the wrong direction | **A-04** idempotency present in V1, auto-generated away in V2 |

The combined rule for this module: **one declaration per rule, generated from the authority that
enforces it, and every migration to a new path re-proves the control it inherits.** A-04 is the
template for that second half — the migration commit should have carried the idempotency guarantee
forward with a test, and did not.

### 1.4 Assumptions to prove

| # | Assumption | Proof | Gate |
| --- | --- | --- | --- |
| A-1 | Per-app federated OIDC sessions meet the isolation requirement without a shared cookie | threat-model review of `federation.ts` ×4 + `backchannel-logout` | G-AUTH-1 |
| A-2 | The backend's `RequiresAcr:2` can be surfaced as a usable step-up UX over the existing BFF | prototype + one real payout approval | G-AUTH-2 |
| A-3 | Generated TS types can be produced from the 24 OpenAPI fragments without breaking 87k LOC of `web` | spike on one package + one app | G-CONTRACT-1 |
| A-4 | Partner concurrency claims are testable against the real DB in CI (MySQL, not SQLite) | a passing race test | G-RACE-1 |
| A-5 | Counsel/finance confirm whether Navdhya operates a regulated Payment Aggregator activity or settles through an authorised PA (**unverified here** — repo holds no copy of the RBI direction) | written determination | **G-PA-0, blocks §11** |
| A-6 | Finance/tax confirm §52 TCS and TDS applicability per transaction shape (**unverified here**) | written determination, then `tax_rule_versions` rows | G-TAX-0 |
| A-7 | A reliable routing source exists, or conservative buffers are acceptable (brief §6.5) | decision + config | G-TRAVEL-0 |
| A-8 | Tenant-scoped behaviour is intended in all 45 bypass sites | per-site inventory with a justification or a fix | G-ISO-1 |

---

## 2. Scope — and what not to build

### 2.1 In scope
1. One transport, one auth client, one contract source; the four copies deleted with their tests
   moved (A-01/A-03/A-14).
2. **Generated** TS domain contracts from Laravel's OpenAPI + a drift gate, starting with the booking
   lifecycle (A-02/A-14).
3. Checkout integrity: idempotency restored on V2 (A-04), an uncertain-payment state + reconciliation
   (A-05).
4. Assignment integrity: `version` column + atomic conditional transitions (A-06), the `start` verb
   and `service_started_at` (A-07).
5. Ledger-backed partner earnings (A-08) + pending/reversal/period-lock gaps (A-09).
6. `apps/dashboard` built as the **exposure layer for existing controls**, not as a new engine (A-15).
7. Design tokens + enforced brand pairings + an accessibility gate (A-11/A-12).
8. Per-app e2e suites incl. the cross-app scenario matrix (A-13).
9. Tenant-bypass inventory with per-site justification (A-10).
10. Partner PWA/manifest with deliberately limited offline (A-16).
11. Content-source equivalence check between disk and API (A-17).
12. Version-matched Turborepo task/boundary configuration (§3).

### 2.2 Out of scope (with owner)

| Excluded | Why | Owner |
| --- | --- | --- |
| Re-designing maker/checker, SoD, checkout holds, double-entry, effective-dated tax, signed webhooks | **already built and correct** (§1.1) | nothing — this is the point |
| Moving Laravel to `backend/laravel/` | brief permits retention; A-18 | nothing |
| Splitting Laravel into four services | four frontends ≠ four backends | never without an ADR |
| A second booking/availability/pricing state machine in TS | A-02/A-19 | backend authority + generated types |
| Unrestricted offline mutation in `partner` | §14.3 brief rule, adopted | §9 |
| Real-time websockets for a modern feel | polling + `revalidate` is sufficient until measured | §13.3 |
| Payment-aggregator licensing or escrow setup | A-5 legal | counsel, G-PA-0 |
| Deciding TCS/TDS applicability | A-6 | finance/tax, G-TAX-0 |
| New brand colours or a palette change | `AGENTS.md` locks them | nothing |
| A shared navigation shell across apps | different users, contexts, permissions | §4.3 |

---

## 3. Workspace and task configuration — version-matched

All of §3 is based on the bundled `turbo@2.11.5` docs, not on recollection. Files read:
`docs/README.md` (index), `reference/boundaries.mdx`, `reference/configuration.mdx`,
`crafting-your-repository/configuring-tasks.mdx`.

### 3.1 Pinning
All four apps sit on Next `16.4.2` / React `^19` today — **the brief's "pin the tested release rather
than drifting independently" recommendation is already satisfied**, and its "16.4.6" reference is not
what this repo runs. Keep a single catalog version in `pnpm-workspace.yaml` (`catalog:`), and let
`tests/architecture` assert all four apps resolve the same `next` version so drift is a failure
rather than a surprise.

### 3.2 Boundaries: the right tool, with an experimental caveat
The brief's §2.2 dependency matrix is exactly what `turbo boundaries` checks — in 2.11.5 it reports
(a) imports of files outside a package's directory and (b) imports of packages not declared in that
package's `package.json`, and supports **tag-based rules** via per-package `turbo.json` `tags` and a
root `boundaries.tags` `allow`/`deny` map.

**Caveat that must not be dropped:** `reference/boundaries.mdx:6,13-16` marks Boundaries
**Experimental** with an open RFC (`vercel/turborepo/discussions/9435`). Therefore:
- Adopt the matrix as **tags** (`app:web`, `app:customer`, `app:partner`, `app:dashboard`,
  `pkg:shared`, `pkg:restricted`, `pkg:private`) now.
- Run `turbo boundaries` as a **CI advisory step** (non-blocking) in this version.
- Enforce the *hard* rules today with the existing architecture-test pattern
  (`tests/architecture/*`, already used for search/contract boundaries) plus an
  `eslint`/`dependency-cruiser` rule that a PR cannot import `apps/*` from another app, and
  `packages/*` cannot import `apps/*`.
- Promote Boundaries to blocking only after it leaves experimental — and record that promotion as a
  deliberate task (T-AP01-06), not a silent change of status.
This is the honest reading of the docs: the feature solves the brief's actual requirement, and its
stability status forbids making it the sole gate.

### 3.3 Task graph
Current `turbo.json` has `build.dependsOn: ["^build"]`, `outputs: [".next/**", "!.next/cache/**"]`,
and cache-less `dev`. Missing, all supported in 2.11.5 per `reference/configuration.mdx`:

```jsonc
{
  "tasks": {
    "build": {
      "dependsOn": ["^build"],
      "inputs": ["$TURBO_DEFAULT$", ".env!"],           // configuration.mdx:370 idiom
      "outputs": [".next/**", "!.next/cache/**", "!.next/dev/**"]  // :650
    },
    "typecheck": { "dependsOn": ["^build"], "inputs": ["tsconfig.json"] },   // :556
    "test":     { "dependsOn": ["^build"], "outputs": ["coverage/**"] },      // :653-654
    "lint":     { "dependsOn": ["^lint"] },
    "contract": { "dependsOn": ["^build"], "outputs": ["src/generated/**"] }, // NEW — A-14
    "build:affected": { "dependsOn": ["^build"] },
    "dev": { "cache": false, "persistent": true }
  },
  "globalEnv": ["NODE_ENV", "CI", "SEO_CONTENT_SOURCE", "CUSTOMER_BACKEND"],
  "ui": "stream"                                        // :102 — CI needs stream, not tui
}
```
Requirements derived from the docs rather than assumed:
- `test.dependsOn: ["^build"]` so a **contract change fans out to every app's tests** — the brief's
  §15.4 requirement, and today `test` has no `dependsOn` at all.
- `globalEnv` must include `SEO_CONTENT_SOURCE` and `CUSTOMER_BACKEND`: both change what an app
  *renders* (`loader.ts:30`, `laravelEnabled()`), so leaving them unhashed risks a cache hit serving
  the wrong source — directly relevant to A-17.
- `build.inputs` should exclude generated-only churn and include lockfile + OpenAPI inputs so the
  new `contract` task invalidates builds correctly.
- **Do not set daemon options:** `configuration.mdx:267` records the daemon is no longer used for
  `turbo run` and the flags are **deprecated, removal in 3.0**. Also avoid `--parallel`
  (`:142`, deprecated).
- `micro-frontends` is **not** a documented concept in this version's bundled docs
  (`core-concepts/` has `package-types`, not micro-frontends) — do not build the four apps as
  runtime-composed micro-frontends on the strength of a recollection.
- Env-var handling per `crafting-your-repository/using-environment-variables.mdx`: declare task-level
  `env` explicitly rather than relying on passthrough, so a missing `NEXT_PUBLIC_*` fails visibly.
- Retain `services/**` from `pnpm-workspace.yaml` in every graph statement (A-18c).

### 3.4 Structure reconciliation
`packages/` already has 42 dirs with real overlaps (A-18d). **Rule adopted: consolidate before
adding.** Concretely: pick one of `contracts` / `api-contracts` as the generated-contract home;
`api-client` becomes the single transport (A-01); a new `auth-client` absorbs the four
`federation.ts`/`session.ts` copies (A-03); `design-tokens` is created as the *only* place brand hex
may appear (A-11); `accessibility` is created to own the axe setup (A-12); `date-time` is created or
`i18n` extended to own `en-IN`/`INR` (163 scattered formatting hits today).
No package is created whose concern the backend already owns authoritatively (§2.2).

---

## 4. Ownership boundaries

### 4.1 The ownership table, with a verification column
The brief's §1.1 table is correct and adopted verbatim, with this addition — a *where is it enforced*
column, because an ownership table with no enforcement mechanism is how A-02 happened:

| Capability | Owner | Enforced by |
| --- | --- | --- |
| Booking + service completion | `Booking` | generated lifecycle types (A-02 fix) |
| Payment + ledger | `Payment`/`Finance` | ledger service + `UnbalancedJournalException` (exists) |
| Payout approval | `Finance` | `MakerCheckerPayoutService` + `SodEvaluator` (exists) |
| Partner verification status | `Onboarding` | claims projection (Onboarding T-01 fix) |
| Assignment + conflict | `Booking`/`Pandit` | atomic transitions (A-06 fix) |
| Panchang/muhurat computation | Calendar kernel | `docs/seo-content/SPEC.md` §3 |
| Ratings + aggregation | `Feedback` | trust G-STAT-1 |
| Cases / incidents | `Support` / `Incident` | trust G-STATE-1 |
| Notification delivery | `Communication`/`Notifications` | comms G-TRUTH-0 |

**Frontends never become the authoritative system** — including for *eligibility*, *status labels*,
*available quantities* and *error semantics*. A-01's per-app error-code mapping is the quiet
violation of this rule; the fix is one transport with a generated code table.

### 4.2 Cross-app imports
Today: none (verified). Keep it that way as a hard rule with a test, not a convention — apps may
depend on `packages/*` only. Any `@navdhya/<other-app>/*` import fails CI; that is the
`pkg:shared`/`app:*` tag pair from §3.2 plus the interim ESLint rule.

### 4.3 What is shared and what is deliberately not
Shared: tokens, UI primitives, transport, auth client, generated contracts, validation, a11y,
formatting, telemetry envelope.
**Not shared, by design:** navigation, layout shells, permission assumptions, caching posture,
interaction patterns. A pandit's three-button queue and a checker's review pane are different
products; the brief's §3.2 has this exactly right, and the failure mode to avoid is a
`design-system` package that grows a `RoleAwareLayout` and quietly becomes the fifth app.

### 4.4 App-scoped capability sets
Each app declares the capability codes it can render, generated from module manifests, so a UI never
offers an action the backend would deny — and so `step_up_required` is handled identically in four
apps instead of one (A-01). The `PermissionGate` component in the brief's §3.1 list is the right
primitive; it must consume the generated set, not a hand-maintained list.

---

## 5. Identity, sessions and authorization across four hosts

### 5.1 What exists, restated because it is better than assumed
Four separate first-party session cookies per host, federated by OIDC with backchannel logout
(`apps/*/server/{federation,oidc,backchannel-logout}.ts`), `SameSite=Lax`, `SESSION_DOMAIN` env-driven
(`config/session.php:167`), CORS allowlist with `supports_credentials` (`config/cors.php`). The
brief's §12.2 worry about a parent-domain cookie failing to isolate subdomains is already answered —
by not sharing a cookie at all. **Record this so nobody "simplifies" it into a shared cookie later.**

### 5.2 Required work
1. **Consolidate** the four auth copies into `@navdhya/auth-client` (A-03), with the union of
   behaviours — customer's `dpopProof`, web's `step_up_required`, refresh/backoff, CSRF bootstrap,
   backchannel logout — proven by tests moved from all four apps, not by a rewrite.
2. **Surface step-up** (A-15/A-2). `RequiresAcr:2` currently errors at the edge with no front door.
   A reusable step-up challenge component in each privileged app, and a `401 step_up_required` →
   re-auth → replay flow that preserves the idempotency key so a retried payout approval is not
   double-executed. Interaction between A-04, A-05 and this is the most under-specified path in the
   repo, so §17 makes it a named gate.
3. **Principal separation as data, not inference.** No `customer|partner|staff` constants exist;
   separation comes from `Role`/`RolePermission`/`MembershipRoleAssignment` +
   `MachinePrincipal`/`ApiKey`/`Invite`/`ActorSession`. That is fine, but the *audience* must be an
   explicit token attribute so `partner.navdhya.com` cannot render a staff-capable action because a
   row says so. Add an audience assertion per app and a test per cross-audience attempt.
4. **Never trust a browser claim as financial authority** (brief §16.1, adopted): the dashboard's
   approval calls go through `RequiresAcr:2` + capability + SoD runtime check, all server-side, each
   asserted by a negative AC.
5. Session posture per the brief: shorter idle and absolute lifetime for staff, revocation,
   re-auth after sensitive transitions, audited privileged actions, controlled recovery, and
   logged/reviewed break-glass. `Platform/Authz` already has DecisionCache + AuthzCacheInvalidator —
   revocation must be proven to invalidate both, which is a falsifiable test, not a property claim.

### 5.3 RBAC+ABAC
Adopt the brief's §12.3 conjunction
`Allow = Identity ∧ Role ∧ TenantScope ∧ ResourceOwnership ∧ ActionPolicy` as the *documented*
evaluation order and map it to `PolicyEvaluationEngine`'s actual sequence
(deny → constraints → grants → default-deny), correcting the brief's model where the implementation
differs. Explicit note: **Next.js route protection is routing, not security** — Server Components may
not be requested, and the brief's own §12.3 conclusion matches Next's documented guidance that Proxy
checks are optimistic. Enforce at the DAL and in Laravel; keep an app-level guard only for UX.

---

## 6. Multi-tenant isolation — the 45-exit problem

The design is sound (header + membership cross-check, fail-closed codes, `TenantScope`,
`BelongsToTenant`, `ConnectionResolver`, `AssertTenantIsolation` matrix, TEN-005…TEN-017 documented).
The gap is A-10: **45 `withoutGlobalScopes()` calls, no inventory.**

Requirements:
1. **Inventory every bypass** (`T-AP03-01`): file, line, purpose, whether a tenant predicate is
   re-applied, and who signs it off. Categories expected to be legitimate: platform-global SEO
   content (`tenant_id IS NULL`, per Search spec), cross-tenant ops under a capability, and the
   webhook matcher. Anything else is a bug.
2. **Replace ad-hoc bypass with declared scope elevation.** A `PlatformScope::run($reason, fn())`
   wrapper that (a) requires a reason string, (b) emits an audit record, (c) is greppable and
   countable in CI so the number can only go **down**. This converts a per-call human judgement into
   an accountable, measurable one, which is the actual requirement behind OWASP multi-tenant
   isolation that the brief cites.
3. **Tenancy ACs on every app-facing task** (methodology §3), including exports, search, and file
   access. `Search` spec §10 already requires tenant predicates *in retrieval*, not after ranking —
   same rule, now for dashboard lists and partner queues.
4. **Ownership models kept distinct** (brief §13.1 adopted): individual user data, family group
   authorisation, institution membership, payout beneficiary, tenant subscription, platform audit.
   Explicitly: a pandit's organisation membership ≠ their financial beneficiary (§11.2 brief point),
   and a temple admin does **not** inherit visibility of every associated pandit's private bookings
   (§6.2 brief point) — that's an entitlement test, not a nav-hiding exercise.
5. **Shared-cache rule** (§12): authenticated responses are identity-aware and must never enter a
   public cache layer; no cross-user reuse. Verified against the app's `Cache-Control` posture per
   route, since `docs/communications/SPEC.md` §12.4 shows caching is where silent leaks happen.

---

## 7. `apps/web` — public acquisition portal

**Realities to work with:** 87,628 LOC; disk-default content with an opt-in API source; 1,279-file
corpus; `robots.ts` + `sitemap.xml/route.ts` + `sitemaps/` instead of `sitemap.ts`; 22 `next/image`
vs 79 raw `<img>`; no CWV measurement; network-only service worker; contains an ad network, a
console, and astrology/resource surfaces beyond the brief's scope.

### 7.1 Rendering and caching posture
Server Components for public content by default; Client Components only where interactive (search
box — already correct per `docs/search/SPEC.md` §1.1 — calendars, maps, booking entry).
Per-page-family caching declared explicitly rather than inherited from framework defaults:
evergreen guides ISR with long windows, calendar-derived pages version-keyed on the calendar
**generation** (`docs/seo-content/SPEC.md` §9), directory pages short TTL, and *no* private data in
any public cache. `next-revalidate` on activation already exists (`api/revalidate/route.ts`) — wire
it to calendar generation activation rather than to editorial publish alone, and record
`calculation_profile` + `generation` in the page's metadata block so a stale Panchang is detectable
by reading the page (brief §4.3's explicit-version requirement, made checkable).

### 7.2 Content-source equivalence (A-17)
Either **one** source is authoritative, or an equivalence test runs in CI. Recommendation: keep disk
as build-time fallback, make `SEO_CONTENT_SOURCE=api` the production setting *only* once the API path
carries the calendar generation, and add `tests/ci/content-source.test.ts` asserting the disk corpus
and the API registry agree on slug set, cluster and `lastUpdated` distribution. Today nothing checks
this, and the two counts in this session already differed.

### 7.3 Location pages, honestly
Adopt the brief's §4.4 rule as an enforceable gate rather than advice, because
`docs/seo-content/SPEC.md` P-13 shows the thin-page pattern is already shipped at 21 near-duplicate
locality variants: a `/panchang/{city}` or `/temples/{city}` page may be indexable only if it
contains real city-specific content — different sunrise times, different local festivals, different
venues — computed from the shared region registry (`docs/search/SPEC.md` §5), not by interpolating a
city name into a template. Reuse the calendar spec's `seo_publish_eligibility` gate and similarity
check; do not build a second eligibility engine.

### 7.4 Performance and quality gates
CWV targets are worth keeping as *objectives*, and the current repo has **no way to measure them**
(A-12/A-13). Minimum viable: a Lighthouse CI job on 3 representative templates (guide, panchang day,
directory) with budgets on LCP/CLS and a bundle-size gate replacing the present
`du -sk >> $GITHUB_STEP_SUMMARY` note-only step. `next/image` adoption on the 79 raw `<img>` tags is
a bounded, mechanical improvement worth its own task. Broken-internal-link = 0 on release is
testable with a crawler against the built output and should be a real gate, since 1,279 pages ×
manual link checking does not happen.

### 7.5 Funnel and attribution without leaking
Adopt the brief's §4.5 funnel and its privacy rule (no birth/family/private identifiers in analytics
URLs — enforced by a URL-param allowlist test, since the corpus already targets diaspora and
family-context queries). Attribution rides the analytics package (`docs/search/SPEC.md` §10.1), not a
bespoke tracker.

---

## 8. `apps/customer` — devotee commerce

**Good news first:** the hard part is built. Holds with expiry and quote-drift guard exist and are
correct (§1.1). The gaps are one dropped protection and one missing state.

### 8.1 Checkout integrity — two specific fixes
1. **Restore idempotency on V2** (A-04). `ReservationV2Controller.php:134,343,448` must accept a
   client-supplied key and **require it fail-closed**, matching `ReservationController.php:141-149`.
   Because the customer BFF is actively migrating to V2, the sequencing rule applies: the migration
   carries the control forward with a test, or the migration does not land. Key rotation on retry,
   replay returning the original result, and conflict responses on key reuse-by-different-actor, all
   asserted.
2. **Add the uncertain state and a reconciler** (A-05). `PAYMENT_UNKNOWN` on `PaymentIntent` as a
   first-class status, a `payment:reconcile` job querying the provider for pending/unknown intents
   within a bounded window, and a rule the brief states and this codebase can now enforce:
   **a booking transitions to `FAILED` only from an authoritative failure, never from a client
   timeout or an unknown.** Then §18.1's "defined remedy for a successfully paid but unfulfillable
   booking" has a state to live in — which, note, also requires the trust spec's remedy path
   (`support_refund_requests`) to exist.

### 8.2 Address vs venue
Adopt the brief's rule (a devotee in Delhi may conduct a puja in Varanasi): `service_venue` is a
**first-class, separately-stored, separately-validated** entity, never defaulting to the residential
address. Locality resolution comes from the shared region registry; serviceability comes from
`packages/geography` finally having a consumer (Search spec E-SR-02 / Onboarding D-03 dependency).
Delivery instructions are optional and must not leak to the pandit beyond need-to-know
(`docs/trust/SPEC.md` §14.4 privacy scoping).

### 8.3 Gotra and spiritual-attribute forms
The brief's §5.4 optional/purpose-specific design is right, and it collides with
`docs/onboarding/SPEC.md` T-17, which found these exact attributes stored as unvalidated free text
(`['nullable','string','max:50']`) while the canonical lists already exist in-repo
(`NakshatraCalculator::NAKSHATRAS`, `GotraList::CANONICAL`). So:
- Fields are **optional unless a documented service-specific requirement makes them required**, and
  the form states *why* and *who needs it* (the pandit for this ceremony), per-purpose.
- Validate against the canonical enums, with `unknown` and `not_applicable` as **real, selectable,
  non-penalised values** — a devotee who does not know their gotra is not an error case, and a form
  that forces a wrong answer to proceed is worse than no answer.
- Explicit per-field sharing controls; these attributes are religion/caste-adjacent special-category
  data (the repo already treats them as such for analytics egress —
  `docs/communications/SPEC.md` C-15 area), so the frontend must not treat them as ordinary text.
- **`apps/customer/app/(protected)/profile/family` and `spiritual` exist and are the surface to
  change, not new pages.** The brief's §5.4 "should not be unconditional mandatory checkout fields"
  needs checking against those forms specifically.

### 8.4 Instant checkout, honestly defined
Adopt the brief's §5.5 list — minimal fields, user-authorised stored data, fast availability lookup,
transparent pricing, one obvious payment action, short holds, confirmation only on authoritative
success. Two additions the code makes necessary: revalidate at submission rather than trusting page
state (the quote-drift guard already does pricing; availability needs the same treatment at
confirm-time), and never require kundli/family completion to buy a simple service. Draft persistence
with safe restore after refresh is worth building; storing the *quote snapshot* client-side is not —
the backend already holds it.

### 8.5 Performance
Progressive form steps, no full-profile fetch before checkout, route-level code splitting on the
heavy `(protected)` surfaces, and measurement: 15,671 LOC with no per-app e2e (A-13) means the
funnel is currently unverified end-to-end, which is the risk to retire first.

---

## 9. `apps/partner` — pandit and temple operations

**The most operationally distinctive app**, and the one where the brief's assumptions and the code
diverge most sharply: no PWA (A-16), no `start` (A-07), non-atomic transitions (A-06), earnings not
ledger-backed (A-08).

### 9.1 Roles and operating context
Adopt the brief's seven partner roles with the two rules that matter:
**membership ≠ visibility** (a temple manager sees the temple scope they are authorised for, not
every pandit's private bookings) and **one explicit active context** (an account in several
organisations must show which one it is acting as, in every request, in the header the backend
validates against membership — the same cross-check `ResolveTenantContext` already does).

### 9.2 Assignment lifecycle, made atomic
1. Add `version` to `pandit_assignments`; rewrite `confirm()`/`complete()`/`assign()` as conditional
   updates with the expected revision — the pattern already proven at
   `MakerCheckerPayoutService.php:98-99`, and the brief's §6.4 SQL is exactly right.
2. Zero-affected-rows ⇒ `409 assignment_revision_conflict` with the current state, so a stale phone
   tap tells the pandit what actually happened instead of silently succeeding or failing cryptically.
3. Move conflict detection **inside** the same lock as the write (A-06's TOCTOU), and cover it with
   the concurrency test in §14.4 — which requires MySQL in CI (A-4/G-RACE-1).
4. Add the `start` verb with `service_started_at` (A-07). This one column serves partner operations,
   trust's completion evidence, communications' arrival journey and the calendar's duration reality;
   it is the highest-leverage small change in the portfolio.
5. Every action carries request id, assignment id, expected revision, actor, verb — with double-tap
   `complete` producing one completion event and one compensation accrual (brief §6.4 idempotency),
   asserted by a test that would fail if the guard were removed.
6. Completion uses a **structured checklist**, not one boolean — and adopts the brief's important
   qualification: not every ceremony needs a photograph or a signature, and evidence requirements
   must not be imposed on religious occasions without operational justification. Checklist shape is
   per service type, configurable, and the defaults are minimal.

### 9.3 Calendar conflicts
`conflictExists()` over `PENDING`+`CONFIRMED` is real and is the right base. Extend with: ceremony
duration + setup time, travel buffer as **configurable conservative defaults unless a routing source
is approved** (A-7 — the brief's honesty here is correct: no routing provider, no routing-grade
claim), pandit-declared unavailability, temple opening/access constraints, and reschedule
recalculation. Conflict responses follow the brief's table (hard block on exact overlap, policy-driven
warning/block on insufficient travel gap, block duplicate acceptance, escalate on temple closure).
**UI requirement:** show *why* a slot conflicts ("10:00–11:30 at Venue A, 45 min to Venue B, next
start 11:45") — an unexplained block to a pandit trying to earn is experienced as arbitrary.

### 9.4 Offline, deliberately limited
Build the manifest + a caching service worker (the existing `apps/web/public/sw.js` is network-only
and caches nothing, so there is nothing to reuse but the file precedent). Adopt the brief's §14.3
matrix as-is — it is well judged: cached shell and an expiring non-sensitive schedule summary yes;
accept/start/complete/payout-change/financial-update **online only**; queued drafts carry revision +
idempotency key and are revalidated on reconnect; **never** put customer addresses, family profiles
or case evidence in a service-worker cache. Add: honest stale-state UI (an offline banner with the
timestamp of the data shown), and a low-signal interaction model — large targets, no precision
swipe/drag for accept or complete, one action per screen, as the brief's §16.5 requires.

### 9.5 Earnings, ledger-backed
Point `PanditEarningsController` at the ledger rather than summing `compensation_minor` (A-08), with
the brief's §6.6 component separation — gross, platform fee, GST **charged by the relevant supplier**,
§52 TCS where applicable, TDS/withholding where applicable, provider charges, refunds/chargebacks —
as distinct fields, never one "tax" bucket and never a total that mixes them. Show estimated net
before acceptance and final net after reconciliation, each labelled as such. And replace the
`{ total_minor: 0 }` fallback with an explicit unavailable state (A-08, comms C-01's rule).

---

## 10. `apps/dashboard` — exposure layer for controls that already exist

**Framing that should change the plan:** 3,705 LOC against a backend with `SodEvaluator` runtime
checks, `MakerCheckerPayoutService`, `DoubleEntryLedgerService`, `tax_rule_versions`, MFA/step-up and
an SoD enforcement test suite. The brief's §8–§11 design work is, for the most part, **already done
somewhere else in this repo**. The gap is the console.

### 10.1 What to build, and what not to
Build: maker-queue, checker-queue, payout proposal review, dispatch board, exception and
reconciliation views, tax dataset views, tenant billing views, audit/evidence review.
Do **not** build: a new approval engine, a new ledger, a new SoD store, a new tax calculator, a
client-side "is this user a checker" rule. Each has a server authority already (`routes/platform/api.php:200`
`platform:ops:view`, `SodEvaluator::blockingForCapability()`, `tax_rule_versions`). A second approval
path in the dashboard would be A-02 repeated with money in it.

### 10.2 Metric definitions are a requirement, not a caption
Adopt the brief's §7.3 rule — every number carries a definition, source query, and last-computed time.
This repo has earned that rule the hard way: `docs/search/SPEC.md` D-01 found a ranking weight
multiplied by a column nothing wrote, and "Total Verified Pandits" is exactly that class of number
(verified identity? qualification? service-scope approved?). Each metric gets a `MetricDefinition`
row: what it counts, which table/query, which generation, and a linked drill-down. A metric without
a definition cannot appear on the overview.

### 10.3 Dispatch oversight
Board over existing booking/assignment state (today/unassigned/pending responses/accepted/arrival/
late/replacement/access-incident/conflicts), with **arrival and lateness columns unavailable until
A-07 lands** — stated on the board rather than left blank. Dispatcher recommendation flow (identify →
eligible → hard gates → conflicts → travel → rank → recommend → record → notify) is the trust spec's
§13 replacement engine reused, not re-derived; an override requires a reason and is auditable; and
the brief's rule is enforced, not advised: **never auto-dispatch an unverified pandit because no one
else is available** (trust R-02's failure). "Live" must mean broadcast or short-polled with a visible
updated-time and source; batch projections are labelled batch (§13.3).

### 10.4 Maker/checker UX with the independence shown, not assumed
`MakerCheckerPayoutService.php:70-71` already throws. The UI must make that outcome impossible to
trip by accident and visible when it bites: maker and checker are different people, the checker pane
shows the maker's identity, self-approval is *not offered* as an action (capability + SoD +
`RequiresAcr:2` server-side, with the absence asserted), and the checker checklist
(beneficiary verified, ledger reconciled, deductions reviewed, duplicate check, separation confirmed)
is a required form whose completed state is persisted as approval evidence. Amount thresholds route
additional independent approval (`SodEvaluator` constraints + role grants).
Bank-beneficiary change is the highest-risk action here: reverification + cooling-off (the
`docs/onboarding/SPEC.md` §8.3 payout-substitution controls) and a **revalidation invalidating an
in-flight approval** — the brief's §18 scenario, which needs a state and a test.
Duplicate payout prevention by deterministic settlement reference and idempotency keys, not by amount
matching (brief §9.4D — correct, and amount matching would also be the first thing a naive
implementation does).

### 10.5 Reconciliation, and what "success" means
Provider API success ≠ cleared funds (brief §9.4E). Four-way comparison — internal approved payout,
provider transaction, bank settlement result, partner statement — as a persisted state with named
exception classes and an owner, not a report a human eyeballs. Ledger gaps (A-09: no pending stage,
no reversal entries, no period locks verified) block part of this; §16 schedules the ledger work
before the reconciliation UI, in the right order.

### 10.6 Tax datasets and tenant billing
Tax: read `tax_rule_versions`/`tax_registrations`/`tax_computations`, present datasets and exceptions
(missing/inconsistent evidence), never submit to government portals without an authorised and
validated route or a controlled accountant workflow (brief §10.2 — adopted). TCS/TDS applicability is
A-6 (unverified) and arrives as approved, effective-dated rule rows, not as UI logic.
Tenant billing stays separate from marketplace settlement (verified: `Platform/Billing/*` is its own
model — brief §11's "do not combine into one balance table" already satisfied). Usage metering must
come from **auditable server-side counters**, never client events (brief §11), and the brief's
critical rule is a gate: **a subscription overdue state must not block a previously confirmed
ceremony or a statutory grievance workflow** without an explicitly approved policy. That is a
suspension-policy test, and it belongs with G-PA-0/G-BILL-1, not with the billing UI epic.

### 10.7 Privileged security
MFA at login, step-up for payout/bank change/tax export/role admin (server exists, **UI missing** —
A-15), short idle + absolute lifetime, revocation that provably invalidates
`DecisionCache`/`AuthzCacheInvalidator`, break-glass logged and reviewed, per-action audit
(`AuditWriter` hash chain — and its verifier is Onboarding-adjacent work: `docs/communications/SPEC.md`
C-04 notes the general chain has no verifier, so privileged-action audit is currently unverifiable).
Exports are permission- **and purpose-** controlled, with the export itself audited.

---

## 11. Financial and tax boundaries (legal-posture gated)

The brief's RBI framing is correct in structure and unverifiable in detail from here. The
consequences this repo can state now:
1. **Do not assume Navdhya may hold customer funds in its own operational account and settle
   pandits itself** (A-5). Whether the platform is a Payment Aggregator, a marketplace using an
   authorised PA, an agent, or another arrangement is a legal determination — **(unverified here)**,
   because no copy of the direction is in the repository.
2. Recommended interim posture, which the code already supports: integrate an authorised PA and keep
   the internal ledger + maker/checker as the **operational control layer**, not as a claim of
   regulated payment-system operation (brief §9.5's final warning, adopted verbatim as a rule:
   *the dashboard's payout approval is not legal authority to operate a payment system*).
3. `LedgerJournal`'s `POSTED`/`VOID`-only model (A-09) is the technical constraint to fix **before**
   anyone builds a settlement-on-hold flow on top of it, so that a hold is a ledger state and not a
   status column beside the ledger.
4. Transaction-shape classification (independent pandit supply / temple supply / platform's own
   service / third-party prasad goods / platform merchandise / commission) is `tax_rule_versions`
   data approved by finance/tax (A-6), with effective dating, and rates never hardcoded — the last
   part is currently clean (no hardcoded tax rates found), so the task is to keep it clean via a lint
   rule, not to fix a violation.
5. The three money flows (devotee transactions / marketplace settlement / tenant subscription
   billing) are already separate; the spec's job is to keep them separate in the **UI**, since a
   single "Balances" screen is how two ledgers get conflated by the next reader.

---

## 12. Design system, tokens and accessibility

### 12.1 Tokens, with a mechanism
Create `packages/design-tokens` as the single source of brand and semantic values; make
`packages/design-system` (17 LOC today) the component layer that consumes it. Enforce: an ESLint
`no-restricted-syntax` rule banning raw brand hex in `apps/**` and non-token components, a
`scripts/tokens` check that every token in use is declared, and migration of the ~252 hardcoded
occurrences app-by-app with web first (87k LOC — schedule it as four tasks, not one).
Semantic tokens (success/warning/error/neutral/info/disabled/border/surface/**focus ring**) are
separate from brand, per the brief.

### 12.2 Contrast pairings, enforced
Reuse the computed table (`docs/communications/SPEC.md` §12.2):
Aubergine/Ivory **14.53** ✓ AAA · Aubergine/Flame **5.01** ✓ AA · Flame/Aubergine **5.01** ✓ AA ·
**Flame/Ivory 2.90 ✗ prohibited for text.** A CI test asserts declared foreground/background pairs
against computed ratios, so Flame-for-text cannot be introduced by a well-meaning refresh — the same
trap recorded for email. Status indication is never colour-only (badge + text + glyph), which the
brief's §16.5 requires and which also serves the pandit-on-a-bright-courtyard case.

### 12.3 Components and accessibility
Adopt the brief's component list, including its app-specific sets (`ApprovalQueue`,
`AuditTimeline`, `FinancialAmount`, `StatusTransition`, `EvidencePanel`, `EntitySwitcher`,
`PermissionGate` for dashboard; `AssignmentCard`, `ScheduleConflictAlert`, `AvailabilityCalendar`,
`ArrivalStatus` — *blocked by A-07* — `CompensationSummary`, `OfflineStatus` for partner). Build the
`packages/accessibility` gap (absent) as: axe-core in the component tests, an axe pass in
Playwright per app, keyboard-only flows for checkout/accept/approve, focus management for dialogs and
route changes, and accessible OTP input — which is already close to right in `packages/otp` and
should be verified rather than rewritten (§3.4 consolidate-not-rebuild rule).
WCAG 2.2 AA as target (**standard text unverified here**), with the practical Navdhya list from the
brief adopted as the acceptance surface: keyboard, screen-reader semantics, focus, accessible OTP,
clear error summaries, large touch targets, Hindi + English, Indian date/currency formatting,
Devanagari and regional script handling, no colour-only status.
`date-time`/formatting: centralise the 163 scattered `en-IN`/`INR`/`Intl.NumberFormat` call sites into
one provider so `hi`/`en` and Indian formatting stop being per-component decisions.

---

## 13. Observability across four apps

### 13.1 Envelope
`request_id`, `correlation_id`, `actor_id`, `application_id`, `tenant_id`, `session_reference`,
plus `booking_id` where relevant — reusing the correlation discipline already in the platform
(`docs/search/SPEC.md` §1.1: `X-Request-Id` + `Navdhya-Request-Id` dual headers in
`laravel-client.ts`). **Record only identifiers necessary for the operation**: no birth data, family
records, payment values or identity document content in ordinary logs (brief §17) — enforce with a
log-scrubbing lint plus a CI test that greps sampled log output for PII shapes (the same technique
that caught `docs/search/SPEC.md` §10.7's PII vector).

### 13.2 Per-app metrics and the cross-app funnel
web: discovery→detail→entry-point click→handoff to customer. customer: funnel completion, checkout
abandonment by step, hold expiry rate, **payment/booking mismatch count** (the A-05 metric),
reconciliation lag. partner: offer→accept latency, accept conflicts (A-06's metric), start/complete
latency, conflict warnings shown vs overridden, earnings view unavailable rate.
dashboard: approval backlog age, self-approval attempts (expect zero, alert on non-zero), SoD
escalscope denials, reconciliation exceptions, export access, privileged-action anomalies.
Funnel tracked per the brief's §17.1 rule that **a booking is not successful because payment
succeeded** — payment, confirmation, assignment, fulfilment, completion, dispute status are separate
stages, which is exactly what A-02's divergent lifecycle and A-07's missing `start` currently prevent
measuring honestly.

### 13.3 Real-time, minimally
Server pagination + short polling on the dispatch/assignment boards + manual refresh +
`revalidate`-driven cache invalidation. No websocket service until a measured need exists; if added
later, it inherits authorization, tenant isolation and event ownership — **a realtime channel is
another data access path, not a trusted shortcut** (brief §14.4, adopted). Label every board
`live` vs `as-of HH:MM` (brief §8.1's rule).

---

## 14. Testing strategy

### 14.1 Layers already present, kept and extended
Vitest (packages + apps), React Testing Library, PHPUnit, Playwright nightly, Semgrep/gitleaks/Trivy,
Stryker opt-in, `tests/architecture/*` incl. the search-boundaries and drift checks. **Extension
order matters more than adding new layers** — three named gaps first:
1. **Contract generation + drift gate** (A-14): generate TS types from the 24 OpenAPI fragments,
   commit them, fail CI on uncommitted drift. This is the mechanism that prevents A-01/A-02/A-03
   recurrence and is the highest-value CI change in this document.
2. **Per-app e2e** (A-13): add webServer configs and projects for customer, partner, dashboard; bring
   the two existing-but-unrun specs into `testMatch`; convert the web known-failures ratchet from a
   35-line allowlist into a shrinking budget with a CI check that new entries are rejected.
3. **Concurrency suite on MySQL** (A-06, A-4/G-RACE-1): the two-devotees-one-pandit race, double-tap
   accept, double-tap complete, concurrent confirm, concurrent payout approval + bank change, replayed
   webhook with the same idempotency key. Today SQLite-flavoured tests cannot prove these, and the
   brief's §18.1 explicitly calls the checkout/dispatch race a distributed-process problem.

### 14.2 Cross-app scenario matrix
The brief's §18 table is adopted nearly verbatim as a test list (16 scenarios). Each maps to a
Playwright/PHPUnit pair and each is written to fail today for a *named* reason:
"two devotees select the same pandit" fails on A-06; "payment succeeds, page times out" fails on
A-05; "maker approves own payout" **passes already** (A-15's point — the control exists, so this test
should be added to *keep* it passing); "pandit accepts an outdated offer" fails on the missing
version column; "tax rate configuration changes" should pass via `tax_rule_versions` effective dating
and is worth asserting as a regression guard; "customer clears family-sharing consent" needs the
trust/communications consent convergence first.
Distinguishing which scenarios already pass is the whole value of this exercise: it turns a
aspirational table into a coverage report.

### 14.3 Accessibility, performance and security
axe per app per release (§12.3), Lighthouse budgets on three web templates + a real bundle-size gate
(A-12), k6-style mixed load per the brief's §19.3 scenarios (launch campaign, festival burst,
checkout rush, partner morning peak, finance close, multi-tenant concurrent ops) benchmarked
separately **and** as one platform, and the security suite extended to per-audience authorization,
tenant isolation across all 45 scope-exit sites (A-10), step-up enforcement, and export/purpose
controls.

### 14.4 Acceptance matrix
Adopt the brief's §19.2 tables nearly as written, with three honest substitutions where reality
differs: partner "Accept = atomic transition" is **currently false** (A-06) so it becomes a
to-build; web "Core Web Vitals target" is **currently unmeasurable** (A-12) so it becomes
measurement-first; dashboard "Maker/Checker: no self-approval" is **already enforced server-side**
(A-15) so it becomes a keep-green regression test rather than a feature.

---

## 15. Deployment and CI

Dependency-aware and per-app already (§1.1). Changes:
- Add the `contract` task (§3.3) so contract changes fan tests out to all affected apps — the
  brief's §15.4 requirement, and unimplemented today.
- Promote size and CWV from note-only to gated; add the a11y gate.
- Per-app `.env.example` with explicit `env` declarations in the task graph (§3.3), so a missing
  variable fails rather than defaulting silently — the same class as `docs/communications/SPEC.md`
  C-01 (`driver=fake` by default).
- **Independent deployment is currently aspirational: no `Dockerfile` and no `vercel.json` exists
  anywhere** in the tree (verified to depth 3), and `docker-compose.yml` provides only postgres +
  redis. `infrastructure/` is application code (cache, composition, BigQuery, Postgres), not deploy
  config. So the brief's §15.1 "separately deployable" claim is a *packaging* truth, not an
  operational one. §16 schedules a deployment-target decision (E-AP-07) before any
  independent-release claim is made, because a buildable app is not a deployable app, and because
  canary/rollback for payout and checkout changes (brief §15.5) needs an environment to canary into.
- Retain the existing safeguards that are genuinely good: gitleaks, blocking `pnpm audit --prod
  --audit-level critical`, SBOM + Trivy, `pr-hygiene`, and add MSG91/WhatsApp secret patterns (recorded
  as `docs/communications/SPEC.md` C-15, still open).
- Migration/API compatibility: the brief's "never release a frontend contract requiring an
  incompatible backend" needs an enforced version floor per app — generated contracts plus a CI check
  that a released app build targets an API range the deployed backend satisfies.

---

## 16. Work breakdown

Estimate classes per methodology §1; scaffold task first per epic.

### E-AP-01 — One transport, one contract *(P0: this is the drift root cause)*

| Task | Title | Est | Key AC |
| --- | --- | --- | --- |
| T-AP01-01 | Generate TS types from the 24 OpenAPI fragments; commit; CI drift gate (A-14) | L | AC-1 an uncommitted contract change fails CI. AC-2 no app may hand-write a response type that the generator owns (lint rule + test). |
| T-AP01-02 | Single `@navdhya/api-client`: merge the four `laravel-client.ts` copies, **union of behaviours** (dpop, step-up, `quote_stale`/`resource_unavailable`/`invalid_state_transition`→409) | L | AC-1 all four apps import the package and delete their copies. AC-2 tests from all four apps pass against it. AC-3 the "mismatch M9" divergence is gone, with a test per error code. |
| T-AP01-03 | `@navdhya/auth-client`: consolidate 4× `federation.ts`/`session.ts`/`auth.ts` + CSRF + refresh + backchannel logout | L | AC: one cookie/session implementation per app host, shared code; a security test per app asserting no cross-audience token acceptance. |
| T-AP01-04 | Generated booking lifecycle types from PHP `BookingLifecycle`; delete `booking-rules.ts`'s table (A-02) | M | AC-1 `NO_SHOW` exists in TS and equals the backend's status set. AC-2 **a test asserts the generated transition map equals the PHP map** — byte-for-byte, so drift cannot recur. AC-3 no TS-authored status set remains. |
| T-AP01-05 | Delete or generate the duplicated domain logic in `packages/{pricing,availability,finance,search}` (A-19) | L | AC: no client-side arithmetic for quote, slot availability, refund reduction, or scoring; a boundary test forbids re-adding. |
| T-AP01-06 | Turborepo Boundaries tags as CI advisory; interim hard ESLint/dependency-cruiser rules; revisit promotion after experimental status (A-18, §3.2) | M | AC: no `apps/*`→`apps/*` import; no `packages/*`→`apps/*`; the experimental status of Boundaries is recorded in the CI step name. |

### E-AP-02 — Checkout and assignment integrity
T-AP02-01 **idempotency required on `ReservationV2Controller`**, fail-closed, matching V1 (A-04) — AC: a repeated key returns the original result; a missing key is rejected; the V1→V2 migration carries a passing test.
T-AP02-02 `PAYMENT_UNKNOWN` + `payment:reconcile` job + "FAILED only from authoritative failure" (A-05) — AC: a client timeout cannot mark a paid booking failed; the remedy path for a paid-unfulfillable booking exists.
T-AP02-03 `version` column + conditional atomic `confirm/complete/assign` (A-06) — AC: zero-affected-rows ⇒ 409 with current state; no read-then-write status mutation remains in `PanditAssignmentManager`.
T-AP02-04 conflict detection moved inside the lock (A-06) — AC: two concurrent confirms yield exactly one accepted assignment, on MySQL.
T-AP02-05 **`start` verb + `service_started_at`** (A-07) — AC: start is recorded once, idempotently, and the field is exposed to trust/communications consumers.
T-AP02-06 concurrency e2e suite on MySQL in CI (A-4/G-RACE-1) covering §14.1.3.
T-AP02-07 double-tap protections per action + one compensation accrual per completion — AC: replay produces one event.

### E-AP-03 — Tenant isolation proof obligations
T-AP03-01 inventory all 45 `withoutGlobalScopes()` sites with purpose + re-filter status (A-10).
T-AP03-02 `PlatformScope::run($reason, fn())` declared elevation + audit + CI count that only decreases.
T-AP03-03 per-audience isolation e2e (partner queue, dashboard lists, exports, search, file access).
T-AP03-04 entitlement model for family/institution scopes (temple admin ≠ all pandits' bookings).
T-AP03-05 cache-posture review: no authenticated response in a shared public cache.

### E-AP-04 — Financial exposure layer (dashboard)
T-AP04-01 **ledger gaps first** (A-09): pending/committed posting stage, compensating reversal
entries, period locks — before any hold/reconciliation UI.
T-AP04-02 maker/checker queue UI over the existing service + persisted checklist as approval evidence.
T-AP04-03 step-up UI + replay-with-same-idempotency-key after re-auth (A-15, §5.2.2).
T-AP04-04 beneficiary-change revalidation invalidating in-flight approvals.
T-AP04-05 four-way reconciliation view with named exception classes.
T-AP04-06 dispatch board (`start`-dependent columns gated until T-AP02-05).
T-AP04-07 metric-definition registry; no undefined number on the overview (§10.2).
T-AP04-08 tax dataset views + export purpose/permission controls, submission deferred.
T-AP04-09 tenant billing console kept in a separate ledger surface; suspension-policy gate test.

### E-AP-05 — Partner operations
T-AP05-01 manifest + caching SW with the brief's limited-offline matrix (A-16) — AC: no financial or
assignment mutation is ever queued without revision + idempotency key; no PII in SW cache.
T-AP05-02 ledger-backed earnings + component separation + explicit unavailable state (A-08).
T-AP05-03 conflict explanations in UI + conservative travel config (A-7).
T-AP05-04 role/context handling with explicit active organisation.
T-AP05-05 low-signal interaction pass (touch targets, no precision gestures).

### E-AP-06 — Design tokens and accessibility
T-AP06-01 `packages/design-tokens` + semantic tokens + lint ban on raw brand hex (A-11).
T-AP06-02 migrate ~252 occurrences app-by-app, `web` first as four subtasks.
T-AP06-03 computed contrast-pair enforcement in CI (§12.2).
T-AP06-04 `packages/accessibility` with axe in component + Playwright passes (A-12).
T-AP06-05 `packages/date-time` (or `i18n` extension) centralising `en-IN`/`INR` (163 sites).
T-AP06-06 `packages/validation` shared schema layer so forms stop diverging.

### E-AP-07 — Delivery and deployment reality
T-AP07-01 deployment target decision per app (**nothing exists today**, §15) + build/deploy configs.
T-AP07-02 bundle-size and CWV gates replacing note-only (§15).
T-AP07-03 per-app `.env.example` + explicit task `env` (§3.3).
T-AP07-04 version floor / API-compatibility check.
T-AP07-05 canary + rollback for checkout and payout changes.

### E-AP-08 — Public portal correctness
T-AP08-01 content-source equivalence test, disk vs API, + single authoritative decision (A-17).
T-AP08-02 calendar-generation-keyed revalidation for calendar-derived pages (link to
`docs/seo-content/SPEC.md` §9).
T-AP08-03 page-family eligibility + similarity gate reuse for location pages (no new engine).
T-AP08-04 `next/image` migration of the 79 raw `<img>` tags.
T-AP08-05 sitemap/robots reconciliation with what actually exists.
T-AP08-06 broken-internal-link crawler gate.
T-AP08-07 analytics URL allowlist test (no birth/family identifiers).

### E-AP-09 — App e2e coverage completion
T-AP09-01 per-app Playwright projects with `webServer` (A-13).
T-AP09-02 enable the two orphaned specs in `testMatch`.
T-AP09-03 known-failures ratchet → shrinking budget, new entries rejected.
T-AP09-04 the §14.2 cross-app scenario matrix, each with a named current-status verdict.
T-AP09-05 regression tests for controls that **already pass** (self-approval, effective-dated tax) so
A-15's asset cannot silently regress.

---

## 17. Acceptance gates

| Gate | Blocks | Evidence |
| --- | --- | --- |
| **G-DRIFT-0** | every app-facing feature task | generated contracts + CI drift gate + **the PHP↔TS lifecycle-equivalence test** (A-02/TS-02/TS-03 pattern). Nothing ships a hand-written status set after this. |
| **G-IDEM-1** | checkout release | idempotency required fail-closed on the V2 path, replay returns the original result, tests pre- and post-migration. **The migration cannot land before its control does** (A-04). |
| **G-MONEY-1** | payment state claims | `PAYMENT_UNKNOWN` + reconciler + no FAILED-from-timeout; measured payment/booking mismatch rate published (A-05). |
| **G-RACE-1** | partner accept/complete | MySQL concurrency suite; exactly one accepted assignment per slot; double-tap produces one event; atomic transitions with revision conflict (A-06). |
| **G-START-1** | arrival/lateness/dispatch/completion features | `start` verb + `service_started_at` exist, with consumers in trust and communications specs updated (A-07). |
| **G-ISO-1** | any dashboard/partner data surface | all 45 scope exits inventoried and either justified behind declared elevation or fixed; per-audience isolation e2e green; exit count can only decrease (A-10). |
| **G-LEDGER-1** | payout console | pending stage, reversal entries, period locks exist or the features requiring them stay unexposed; earnings read the ledger (A-08/A-09). |
| **G-PA-0** | holding customer funds / settling pandits | **A-5 counsel determination** — unverified here, no repo copy. Payment-aggregator posture decided before any escrow-like flow. |
| **G-TAX-0** | automated TCS/TDS | **A-6 finance/tax determination** per transaction shape; effective-dated rule rows; lint forbids hardcoded rates. |
| **G-BILL-1** | tenant suspension logic | an overdue tenant cannot block a confirmed ceremony or a statutory grievance workflow without an approved policy, tested. |
| **G-AUTH-1 / G-AUTH-2** | privileged console | federated per-app isolation threat-model review; step-up surfaced in UI and re-asserted for payout/bank/tax-export/role-admin; revocation proven against both caches. |
| **G-A11Y-1** | any user-facing release | axe in CI per app; keyboard-only and screen-reader flows for checkout, assignment actions, payout approval; no colour-only status; contrast pairs asserted numerically (§12.2). |
| **G-TOKEN-1** | new UI in `apps/**` | raw brand hex banned; tokens only; 252-site migration tracked to zero. |
| **G-E2E-1** | release claims for customer/partner/dashboard | each app has a running e2e project; the two orphaned specs enabled; ratchet is shrinking. |
| **G-DEPLOY-1** | "independently deployable" | a real deployment target and rollback path per app (nothing exists today), with canary for checkout and payout changes. |
| **G-SRC-1** | public portal correctness | disk/API corpus equivalence green; calendar pages carry generation + version; broken internal links zero; bundle/CWV budgets gated. |
| **G-TENANT-0** | every epic | tenancy AC per task; negative authorization AC where privileged. |

**Release train.** *Step 1* = E-AP-01 + E-AP-02 (one source of truth per rule, money and assignment
integrity) — **no new feature surface before this**, because every later app would else duplicate
the divergence. *Step 2* = E-AP-03 isolation proof. *Step 3* = E-AP-04/05 in parallel (console,
partner). *Step 4* = E-AP-06/07 tokens, a11y, deploy reality. *Step 5* = E-AP-08/09 portal
correctness and full coverage.

---

## 18. Risks & kill criteria

| # | Risk | Control | Kill / revert |
| --- | --- | --- | --- |
| R-01 | A rule exists in two places with two answers (A-02 recurrence) | generated contracts + equivalence test + boundary lint | a hand-written status/capability/error set in an app fails CI |
| R-02 | The V2 migration silently drops an inherited control (A-04) | "migration carries its controls forward" review rule + pre/post tests | V2 rollout blocked until T-AP02-01 is green |
| R-03 | A paid devotee is recorded as failed (A-05) | `PAYMENT_UNKNOWN` + reconcile + authoritative-failure-only transitions | any auto-FAILED on timeout ⇒ release blocked |
| R-04 | Double assignment under concurrency (A-06) | version + atomic transition + in-lock conflict check + G-RACE-1 | detected conflict ⇒ accept path manual-only |
| R-05 | Dashboard grows a second approval/ledger/tax engine (A-15) | §10.1 explicit do-not-build list + Deptrac/API test | a duplicate approval endpoint is a blocker |
| R-06 | 45 scope exits become 60 (A-10) | declared elevation, audit, monotonic-count CI | count increases ⇒ merge blocked |
| R-07 | Maker/checker bypassed by UI convenience | capability + SoD runtime + step-up + non-offered self-approval + regression test | any self-approval success is a P1 |
| R-08 | Payout approved then beneficiary details change | revalidation invalidates approval; cooling-off; on-hold state | approval surviving a change ⇒ payouts disabled |
| R-09 | Operating a payment system without authorisation (**unverified** whether this applies) | G-PA-0 counsel; authorised PA; ledger as control layer | unresolved ⇒ hold funds never; PA integration before settlement features |
| R-10 | Tax misclassification automated before finance sign-off | G-TAX-0; datasets only; no portal submission without authorised route | a rule row without approval cannot compute |
| R-11 | Offline partner UX mutates stale state | revision + idempotency key + online-only financial/assignment actions | a queued mutation without both ⇒ offline writes disabled |
| R-12 | Private data cached (SW, public cache, browser) | §12/§6 rules + cache-posture test per route | a leak ⇒ feature disabled pending review |
| R-13 | Brand/a11y drift while tokens are missing (A-11/A-12) | token layer + lint + computed contrast + axe CI | new raw hex fails; an a11y violation blocks the app release |
| R-14 | Test debt laundered through a ratchet | shrinking budget, new failures rejected | ratchet grows ⇒ CI merge blocked |
| R-15 | "Independently deployable" claimed without deployment (A-17/§15) | G-DEPLOY-1 before the claim | wording corrected in docs, no rollback story ⇒ no canary claim |
| R-16 | Two content corpora drift (A-17) | equivalence test + single authoritative decision | a diff ⇒ fallback disabled until reconciled |
| R-17 | Unmet accessibility in a religious-obligation context | keyboard/screen-reader flows for checkout, ceremony actions, approvals | a broken keyboard path in checkout is a blocker, not a bug |
| R-18 | Consolidation epics (E-AP-01) become a year-long rewrite | land per-app behind flags, keep four builds, no big-bang | an epic exceeding its estimate triggers scope split, not a pause on the control fixes |

**Verdict.** The brief's target architecture is right and largely *already chosen* by this codebase;
the brief's §22 brutal assessment lands on the correct risk — *"the risk is not having four
applications; it is creating four inconsistent interpretations of the same booking, user, financial
transaction or permission."* That risk is not prospective here: A-01, A-02 and A-03 are it, in
production, with one divergence documented in a code comment. So the recommendation is a reversal of
the brief's build order: consolidate the contracts and fix money/assignment integrity first, then
build the console that exposes the controls already built, then polish surfaces.

**Sequencing claim.** Two changes carry the most risk reduction in this document: **require an
idempotency key on the V2 booking path** (A-04 — a protection that exists in V1 and is being
migrated away from) and **generate the booking lifecycle types from the backend, with an equivalence
test** (A-02 — the divergence that would otherwise be replicated into every new screen). Both are
small, both are mechanically verifiable, and both are the kind of fix that stops needing to be made
again.

---

## 19. ADRs required before approval

| ADR | Subject | Must record |
| --- | --- | --- |
| ADR-0068 | Single transport + generated contracts as the only cross-app integration surface | A-01/A-02/A-14; drift gate; no hand-written status sets in `apps/**` |
| ADR-0069 | Booking lifecycle authority and TS codegen | the equivalence test; who may edit `BookingLifecycle.php`; deprecation of `booking-rules.ts` |
| ADR-0070 | Checkout idempotency and payment-uncertainty | A-04/A-05; "FAILED only from authoritative failure"; migration carries its controls forward |
| ADR-0071 | Optimistic-locking assignment transitions + the `start` verb | A-06/A-07; conflict-in-lock; double-tap semantics; downstream consumers in trust/communications |
| ADR-0072 | Declared tenant-scope elevation replacing ad-hoc global-scope bypass | A-10; inventory; monotonic count; per-audience e2e |
| ADR-0073 | Dashboard as exposure layer only | A-15; the do-not-build list; ledger prerequisites first |
| ADR-0074 | Payment-aggregator posture and settlement boundaries | A-5 **(unverified here)**; authorised PA; ledger as control layer, not legal authority |
| ADR-0075 | Turborepo task graph and boundary enforcement on 2.11.5 | the version-matched `inputs/env/globalEnv` decisions; **Boundaries' experimental status**; daemon deprecation; retained workspace globs incl. `services/**`; Laravel stays at `platform-navdhya/` |
| ADR-0076 | Design tokens, contrast policy and accessibility floor | A-11/A-12; computed pairings; raw-hex ban; axe in CI |
| ADR-0077 | Partner offline posture | limited-offline matrix; revision + idempotency for anything queued; no PII in SW cache |
| ADR-0078 | Content source of truth for `apps/web` | A-17 decision; equivalence; calendar generation keying |

Also required on merge: `tests/architecture` boundary specs per app pair; the shrinking
known-failures budget as an explicit CI config; OpenAPI fragment completeness for any endpoint an app
consumes; and a documentation update to `docs/communications/SPEC.md` §15.2 and `docs/trust/SPEC.md`
§13 wherever A-07 (the `start` timestamp) closes a stated gap — three prior specs asserted that data
did not exist, and this portfolio is where it gets created.

---

## 20. Verification (this document)

1. Every `A-` row cites a `file:line` or a measured count that still resolves; counts (45 scope exits,
   ~252 hex, 87,628 LOC, 1,279 content files, 22 vs 79 images) are reproducible from §1.1/§1.2.
2. Every Turborepo configuration statement in §3 is traceable to the bundled
   `turbo@2.11.5` docs (files named inline), including the **experimental** status of Boundaries and
   the **deprecated** daemon options. No config claim rests on recollection of another version.
3. Legal/standards positions (RBI PA direction, CBIC §52, ASVS 5.0, WCAG 2.2, DPDP dates, the brief's
   Next `16.4.6`) are marked **(unverified here)** and converted into gates, never constants.
4. §1.1 lists what already works and §2.2 forbids rebuilding it; a task that re-implements an
   existing backend control is rejected at review.
5. Every epic maps to at least one gate; every gate has a test that fails when the enforcement is
   absent; §14.2 distinguishes scenarios that will newly pass from those that **already pass and must
   be guarded**.
6. Every AC written into `docs/specs/tasks/` follows methodology §3 — observable, decidable,
   CI-testable, falsifiable; "appropriate", "reasonable", "user-friendly" do not appear.
7. Every task touching tenant data carries a tenancy AC; every task touching a privileged or financial
   action carries a negative AC; every user-facing task carries an accessibility AC.
8. Doc drift is a review blocker: where this spec closes a gap asserted in `search`,
   `seo-content`, `onboarding`, `communications` or `trust`, those documents are updated in the same
   task, and the four-app count/version facts in §3.1 are re-checked on any dependency bump.
