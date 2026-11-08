
 Here is Qoder's plan:

────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 Phase 7 Slice — Booking V2 API + Surface Cutover

 Context

 The premise given ("P0–P7 fully built, 1,256 PHP + 729 Vitest tests, PHPStan 100%") does not match the repo:

 - planner-08nov.md:707 itself states "Local read-only analysis has not established a green test baseline."
 - The backend P1–P6 slice is uncommitted: ~44 untracked paths (Booking/Application/ use cases, Booking/Console/, Tax/,
  Finance/, Platform/Billing/, Payment/Domain/, booking migrations 000001–000003, CommandDeduplicator.php, even
 planner-08nov.md) + 54 modified files (+1,028/−307).
 - phpunit.xml runs 184 test files (SQLite), not 1,256 tests; PHPStan level 6 has a 16-entry legacy baseline. No doc
 contains the 1256/729 gate figures.
 - /api/v2 is spec-only: v2/booking appears nowhere in code; platform-navdhya/routes/api.php:20-34 mounts a single
 global version from config/platform.php ('api_version' => 'v1').
 - Frontends: apps/web/app/book/client.tsx:50-55 and app/pandits/book/client.tsx:46-51 still console.log+alert fake
 bookings; apps/partner/.env.local lacks PARTNER_BACKEND so all partner actions run mock; apps/customer is real but
 flag-gated to /api/v1.

 User-approved scope: baseline stabilization → Booking V2 REST plane (planner §9, lines 660–702) → customer/web/partner
  surface wiring. Operational daemons (reconciliation, dunning, tax returns, MySQL stress harness) are explicitly
 deferred — they depend on work that isn't landed or verified.

 Architecture rule (§9 line 664): v2 and v1 adapters must call the same application services — no permanent dual
 engine; v1 stays additive-only and is retired later per consumer inventory.

 WP0 — Stabilize baseline, commit the uncommitted P1–P6 slice (gates everything)

 1. Review the full diff + untracked list (git status --short, 98 entries).
 2. Stage and commit in logical commits: fix(baseline) for backend (Booking Application/Console, Domain, Models,
 migrations, Tax/Finance/Billing/Payment additions, modified controllers, tests, phpunit.xml, routes/console.php), docs
  commit for planner-08nov.md. Exclude # Navdhya — Homepage Structure.ini and any tmp/junk. Do not renormalize LF→CRLF
 noise.
 3. Run the gate ledger (planner §11 lines 908–926): php artisan test, pint --test, vendor/bin/phpstan analyse
 --no-progress --memory-limit=1G, deptrac analyse --no-cache, php artisan api:compose --check, php artisan
 platform:doctor --json; then pnpm exec vitest run tests/customer tests/partner tests/architecture tests/contracts,
 pnpm --filter @navdhya/web test (Jest), pnpm verify, pnpm build.
 4. Record pass/fail per gate in the commit body; known pre-existing failures (phpstan legacy baseline entries)
 documented, not fixed here. Exit: clean tree, ledger committed, no new red. If a gate reveals the uncommitted slice is
  actually broken, fix before proceeding.

 WP1 — v2 routing plane (per-module versions)

 - platform-navdhya/config/platform.php — add 'api_versions' => ['booking' => ['v1', 'v2']]; keep api_version as
 default.
 - platform-navdhya/routes/api.php:22-34 — loop versions per module: Route::prefix("$v/{$module->code}"); a module opts
  in by shipping Routes/api-v2.php (manifest routesPath() is generic; ModuleShapeTest forbids inline routes here — keep
  it that way).
 - platform-navdhya/app/Modules/Booking/module.json — declare versions; app/Modules/Booking/Routes/api-v2.php (new).
 - deptrac.yaml — assign the currently-unlayered Booking/Application + Booking/Console namespaces.
 - Exit: route:list shows both planes; ModuleShapeTest, deptrac, api:compose green.

 WP2 — ReservationV2Controller + wiring existing use cases (depends WP1)

 New app/Modules/Booking/Http/Controllers/ReservationV2Controller.php (plain final class, JsonResponse) + shared HTTP
 support so v1/v2 don't fork:
 - Http/Support/ReservationPresenter.php — server-derived version, expires_at, tax breakdown, payment status, document
 links, available_actions (§9 line 684).
 - Http/Support/BookingProblemResponder.php — domain exceptions → App\Platform\Api\ErrorEnvelope
 (bootstrap/app.php:91): 401/403, ownership-safe 404, 409 version_conflict/state_forbidden/idempotency_key_conflict,
 422, pollable 202.
 - Refactor v1 ReservationController::store()/confirm() inline bodies to delegate to CreateReservationDraftUseCase /
 hold+confirm use cases (currently test-only); v1 response shapes pinned by
 BookingReservationTest/BookingIdempotencyTest.

 Routes (§9 lines 669–671): POST reservations · GET reservations, {id} (weak ETag: W/"v{version}" + If-None-Match→304,
 pattern: app/Modules/Search/Http/Controllers/SearchController.php:283-311), {id}/history, {id}/changes · :quote ·
 :checkout · :cancel:preview + :cancel · :reschedule:preview + :reschedule · :rebook · GET operations/{commandId}.
 Every mutation: observed version + Idempotency-Key (scoped actor:operation:resource:key, payload-hash mismatch → 409).

 - Previews read-only: new Application/PreviewCancellationUseCase.php reusing the 48h/72h tier math from
 CancelBookingUseCase.php:86-103 without persisting BookingChange/transition/audit/outbox; :reschedule:preview via
 ResourceAllocationManager::conflictExists.
 - Dedupe/202: wire the existing-but-unused Domain/CommandDeduplicator.php + booking_commands table around every v2
 mutation → replay, 409 on hash mismatch, PROCESSING → 202 with pollable operations/{id}.
 - Rebook: new Application/RebookBookingUseCase.php — fresh DRAFT + Revision 1 copying current BookingLines, set
 rebooked_from_id (Models/Booking.php:36,56), version=1, new idempotency verb.

 WP3 — Quote/checkout + tax coverage gate (depends WP2)

 - New port app/Modules/Tax/Contracts/QuotesCommercials.php bound in app/Modules/Tax/ModuleServiceProvider.php to
 existing Domain/CommercialQuoteService (Tax module currently domain-only, no HTTP — this gives it its first reachable
 surface via Booking).
 - :quote → quote_hash, per-line tax_components, jurisdiction summary; unknown/invalid coverage → 422
 coverage_unavailable (blocks checkout, P3 exit rule).
 - :checkout → persist accepted_quote_hash (2026_11_08_000003 migration), re-quote and 409 quote_stale on drift, then
 InitiateCheckoutHoldUseCase (15-min pandit holds) + payment-intent creation under the existing
 two-committed-transaction discipline (ReservationController.php:429-469). Stock holds via stock_reservations on
 Catalog items; samagri-kit tables out of scope (follow-on migration).

 WP4 — Customer BFF onto v2 (depends WP2+WP3 contract freeze)

 - apps/customer/adapters/backend/laravel-client.ts:82 hardcodes /api/v1${path} → add version arg (default 'v1');
 booking call sites pass 'v2'. Forward If-None-Match/ETag; never re-fetch to refresh a stale form (§9 line 696); reuse
 application/idempotency.ts keys across renders/retries.
 - Update app/api/bookings/[bookingId]/{reschedule,cancel,checkout,rebook} routes,
 application/bookings/laravel-booking-mappers.ts, get-customer-booking-details.ts,
 presentation/bookings/BookingDetailPage.tsx; make /book/start + checkout pending/retry/expired states real. Mocks stay
  behind laravelEnabled(). DPoP (server/dpop.ts) and tenant forwarding unchanged.

 WP5 — Web public funnel → real handoff (parallel with WP2)

 - packages/booking/src/contracts/BookingIntent.ts — v1 has only pujaId/panditId/slotRef; additively extend with
 durationMinutes, timezone, tenantId (bump v handling, keep old links decodable).
 - Replace console.log+alert in apps/web/app/book/client.tsx:50-55 and app/pandits/book/client.tsx:46-51 with
 buildBookStartUrl(...) handoff (proven pattern: apps/web/components/QuickBook.tsx:156-176), using real pandit
 ULID/server duration from the pandit page data (kill MOCK_PANDITS path in these forms).
 - Receiver: apps/customer/application/bookings/resolve-book-start.ts + start-booking-from-intent.ts map new fields
 into the v2 draft.

 WP6 — Partner fulfillment (last, lowest risk)

 - apps/partner/.env.local: add PARTNER_BACKEND=laravel, NAVDHYA_API_BASE.
 - app/api/assignments/[id]/accept/route.ts (pure mock today) → laravelPost; confirm/decline/complete BFFs already
 exist (laravel-client.ts:102 — add version arg if assignment routes stay v1).
 - Backend :start missing (§9 line 673): PanditAssignmentManager::start() beside confirm()
 (app/Modules/Pandit/PanditAssignmentManager.php:95) with owned-in-ASSIGNED guard, route assignments/{id}:start,
 pandit.assignment.started outbox fact.

 WP7 — E2E smoke toward A29

 - Extend e2e/playwright.config.ts:20 testMatch (planner line 928) and add e2e/booking.spec.ts: discover → handoff →
 quote → checkout → cancel preview/submit → reschedule → rebook. Validate with playwright test --list first.

 Test strategy (extend existing suites first, §11)

 - Backend: extend platform-navdhya/tests/Modules/Booking/ (BookingReservationTest, BookingIdempotencyTest) + new
 BookingRebookTest, BookingQuoteCheckoutTest — exact status/error codes, persisted effects (bookings, booking_changes,
 booking_commands, resource_allocations, stock_reservations, outbox, audit), previews assert zero rows written,
 duplicate-key replay asserts single effect.
 - Frontend: tests/customer/booking-actions.test.ts + laravel-backend.test.ts (v2 paths, version/ETag forwarding, key
 stability); Jest apps/web/**/__tests__ (handoff carries real ULID/duration/TZ); tests/contracts (BookingIntent
 round-trip); tests/partner (flagged laravel calls).

 Sequencing

 WP0 → WP1 → {WP2 ∥ WP5} → WP3 → WP4 → WP6 → WP7. WP4 blocked on WP2+WP3 contract freeze.

 Verification

 Per-WP exits above; final: full WP0 gate ledger re-run green (except documented pre-existing), php artisan route:list
 v1+v2 inventory committed, Playwright smoke passing locally against platform-navdhya serve with seeded tenant.

 Explicitly deferred

 Operational daemons (reconciliation, dunning/renewals, tax-return aggregation, ops health alerts), Billing P6
 verification beyond baseline, phpunit.mysql.xml multi-process stress harness (config exists but has no
 processIsolation/parallel), samagri-kit tables, v1 removal (gated on consumer inventory), production-size rehearsal,
 CI activation, A30–A32 full gates.

 Key files

 platform-navdhya/routes/api.php, config/platform.php, app/Modules/Booking/{Routes/api-v2.php,
 Http/Controllers/{ReservationController,ReservationV2Controller}.php, Application/*, Domain/CommandDeduplicator.php,
 module.json}, app/Modules/Tax/{Contracts,ModuleServiceProvider,Domain/CommercialQuoteService}.php,
 app/Modules/Pandit/PanditAssignmentManager.php, apps/customer/{adapters/backend/laravel-client.ts,
 app/api/bookings/**, application/bookings/**, presentation/bookings/BookingDetailPage.tsx},
 apps/web/app/{book,pandits/book}/client.tsx, packages/booking/src/contracts/BookingIntent.ts,
 apps/partner/{.env.local, app/api/assignments/**}, e2e/playwright.config.ts.

 -----

  Here is Qoder's plan:

────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 Combined Program — Booking V2 Cutover + Pandit Domain Build-Out

 Context

 Premise check (verified in-repo): "P0–P7 fully built/verified" is false. planner-08nov.md:707 admits no green
 baseline; the P1–P6 backend slice is uncommitted (44 untracked paths — Booking/Application/ use cases, Tax/, Finance/,
  Platform/Billing/, Payment/Domain/, migrations, planner-08nov.md itself — plus 54 modified files); PHPStan runs at
 level 6 with a 16-entry legacy baseline.

 The quoted Pandit spec (traditions/Shakhas/Gotra, buffers/blackouts, ratings/fulfillment/acceptance rules) is not in
 planner-08nov.md — nearest authority: line 385 ("provider eligibility, working windows, exceptions, travel/setup
 buffers participate in reservation guards") and line 158 ("Pandit… expose contracts to Booking, never mutate booking
 rows"). Today: no tradition/shakha/gotra columns anywhere; two unlinked availability systems (pandit_availability vs
 Booking-owned resource_calendars/allocations); holds ignore buffers/blackouts; selected_pandit_id gets zero
 eligibility validation (CreateReservationDraftUseCase.php:96); rating_avg/rating_count/total_bookings have no writers
 at all; no acceptance rules or SLA anywhere.

 User-approved decisions: combined program (Booking V2 slice A + Pandit slice B), contract bridge (Pandit stays
 authoritative; no materialization into resource_calendars), full depth acceptance (rules + SLA daemons + rating
 recompute), and WP0 baseline commit first. Planner §9 line 664 rule holds: v1/v2 adapters call the same application
 services — no dual engine.

 Ordered program

 WP0 → WP1 → WP2 → B1 → B2 → WP3 → B3 → WP4 → B4 → WP5+B5 → WP6+B4b → WP7+B6

 Ordering risk accepted: WP2's v2 create/quote ship one package before the B3 pandit guard exists — safe because the
 controller only delegates to CreateReservationDraftUseCase, so B3 drops the guard in with zero controller churn. B1/B2
  precede WP3 so checkout never ships buffer-blind. WP6+B4b co-landed (same file PanditAssignmentManager.php).

 WP0 — Baseline stabilization (gates everything)

 Review diff + untracked list; commit in logical commits (fix(baseline) backend, docs commit for planner-08nov.md);
 exclude # Navdhya — Homepage Structure.ini/tmp; no CRLF renormalization. Run gate ledger (§11): php artisan test, pint
  --test, vendor/bin/phpstan analyse --no-progress --memory-limit=1G, deptrac analyse --no-cache, php artisan
 api:compose --check, platform:doctor --json, pnpm exec vitest run tests/customer tests/partner tests/architecture
 tests/contracts, pnpm --filter @navdhya/web test (Jest), pnpm verify, pnpm build. Known pre-existing failures
 documented in commit body, not blocked. Exit: clean tree, committed ledger.

 WP1 — v2 routing plane

 platform-navdhya/config/platform.php add 'api_versions' => ['booking' => ['v1','v2']]; routes/api.php:22-34 loop
 versions per module (business routes must not be inline here — ModuleShapeTest); new
 app/Modules/Booking/Routes/api-v2.php + module.json version declaration. Exit: route:list shows both planes;
 shape/deptrac/api:compose green.

 WP2 — ReservationV2Controller (slice A core)

 New Http/Controllers/ReservationV2Controller.php + Http/Support/ReservationPresenter.php (server-derived version,
 expires_at, tax breakdown, payment status, available_actions) + Http/Support/BookingProblemResponder.php (→
 App\Platform\Api\ErrorEnvelope; 401/403, ownership-safe 404, 409
 version_conflict/state_forbidden/idempotency_key_conflict, 422, pollable 202). Routes: create/list/detail (weak ETag:
 W/"v{version}" pattern from SearchController.php:283-311)/history/changes, :quote, :checkout, :cancel:preview+:cancel,
  :reschedule:preview+:reschedule, :rebook (new Application/RebookBookingUseCase.php — DRAFT + Revision 1 copying
 lines, sets rebooked_from_id), GET operations/{id}. New read-only PreviewCancellationUseCase (reuses
 CancelBookingUseCase.php:86-103 tier math, asserts zero writes). Wire the unused Domain/CommandDeduplicator.php +
 booking_commands table around every v2 mutation. Refactor v1 store()/confirm() to delegate to
 CreateReservationDraftUseCase (currently test-only) with v1 shapes pinned by existing tests.

 B1 — Vedic taxonomy (Pandit)

 Migrations (expand-only, per-module convention): pandit_profiles + tradition, shakha_code, gotra; new pandit_shakhas
 lookup (code/label/veda, tenant-nullable). Model decisions: tradition = backed enum
 VedicTradition{Smartha,Vaishnava,Shaiva}; gotra = controlled constant list (8 primary + Unknown); both live in
 Pandit/Contracts/ (deptrac: Contracts may only depend on Support — a Domain/ enum would violate the port layer).
 Shakha = Rule::exists table (grows without deploys). Extend PanditProfileController.php:127 allowlist,
 PanditProfileQueries::publicProfiles:34-46 filters, Search/Contracts/PanditSearchProvider card facets + searchText;
 Pandit/Http/openapi.yaml + api:compose. Exit: taxonomy filters work; unknown value → 422.

 B2 — Availability depth + PanditSchedulePolicy contract

 Migrations: pandit_blackouts (pandit_id, starts_at, ends_at, reason, type∈{PERSONAL,TRAVEL,FESTIVAL,ADMIN_BLOCK});
 pandit_profiles + travel_buffer_minutes, travel_radius_km, timezone (fallback tenants.timezone — availability tables
 are clock-only today). New Pandit/Contracts/PanditSchedulePolicy.php + ScheduleVerdict/AvailabilityWindow DTOs;
 first-ever Pandit/Application/PanditSchedulePolicyService.php: isAvailableAt, buffer-padded effectiveWindowCoverage,
 blackoutOverlap, nextAvailable — reads pandit_availability+blackouts+assignments; no writes to Booking tables (line
 158). Extend PUT availability (PanditAvailabilityController.php:133) to accept blackouts/buffers. Exit: boundary tests
  (buffer across midnight, blackout == slot start, DST rejection).

 WP3 — Quote/checkout gate

 app/Modules/Tax/Contracts/QuotesCommercials.php port → Domain/CommercialQuoteService (Tax module's first reachable
 surface). :quote → quote_hash + per-line tax_components; unknown coverage → 422 coverage_unavailable. :checkout
 persists accepted_quote_hash, 409 quote_stale on drift, then InitiateCheckoutHoldUseCase under
 two-committed-transaction discipline (ReservationController.php:429-469); stock via stock_reservations on Catalog
 items (samagri-kit tables deferred).

 B3 — Booking guards consult the policy (single gate)

 CreateReservationDraftUseCase.php:96: validate pandit exists + VERIFIED + ACTIVE +
 pandit_service_eligibility(pandit_id, sku) row matches (new table — exact booking_lines.sku predicate, FK-honest vs
 fragile whereJsonContains; specializations json stays display/facet text) + isAvailableAt → typed exceptions → 422
 pandit_not_eligible / 409 slot_unavailable. InitiateCheckoutHoldUseCase.php:49: widen hold interval by buffer via
 policy before holdPanditSlot. InstantSearchController.php:97-109 refactored off raw windowsOnDate/hasWindowConflict
 onto the policy; reschedule-preview uses it too. Exit: contention test proves search/checkout/preview agree.

 WP4 — Customer BFF onto v2

 apps/customer/adapters/backend/laravel-client.ts:82 version arg (default v1; booking sites pass v2); forward
 If-None-Match/ETag, never re-fetch to refresh a stale form (§9:696); stable keys via application/idempotency.ts.
 Update app/api/bookings/[bookingId]/{reschedule,cancel,checkout,rebook}, laravel-booking-mappers.ts,
 get-customer-booking-details.ts, BookingDetailPage.tsx; real /book/start + checkout pending/retry/expired. Mocks stay
 behind laravelEnabled().

 B4 — Acceptance rules, SLA, derived projections

 pandit_assignment_rules (auto-accept by tradition/sku/max_distance; response_sla_hours) + new Pandit/Console/ commands
  registered via bootModule() (copy Booking/ModuleServiceProvider pattern), scheduled in routes/console.php:25:
 pandit:process-assignment-sla (timeout → auto-decline + reassign next eligible), pandit:recompute-ratings (incremental
  from Booking/Models/BookingReview.php, cursor-paged backfill, idempotent, outbox fact). total_bookings becomes
 derived projection on confirm/complete transitions.

 WP5 + B5 — Web funnel + partner surfaces

 BookingIntent.ts additive fields (durationMinutes, timezone, tenantId; v1 links keep decoding); replace
 console.log/alert stubs (apps/web/app/book/client.tsx:50-55, app/pandits/book/client.tsx:46-51) with buildBookStartUrl
  handoff (QuickBook.tsx:156-176); receiver resolve-book-start.ts maps fields into v2 draft. Web pandits listing: real
 cards + B1 facets via Search API, retiring MOCK_PANDITS (lib/pandits/data.ts:11). Partner: PanditProfileView.tsx
 taxonomy fields + new PanditSchedulePolicyView.tsx (blackouts/buffer/rules) over self endpoints.

 WP6 + B4b — Partner fulfillment (one package, same files)

 apps/partner/.env.local PARTNER_BACKEND/NAVDHYA_API_BASE; app/api/assignments/[id]/accept/route.ts mock → laravelPost;
  PanditAssignmentManager::start() beside confirm() (:95) with owned-in-ASSIGNED guard + assignments/{id}:start route +
  outbox fact; auto-accept applied on assign when rules match.

 WP7 + B6 — E2E + test closure

 Widen e2e/playwright.config.ts:20 testMatch (planner:928); e2e/booking.spec.ts A29 journey: discover → handoff → quote
  → checkout → cancel preview/submit → reschedule → rebook, plus buffered-slot conflict + auto-accept. Backend: extend
 tests/Modules/Pandit/{PanditModuleTest,PanditPublicDirectoryTest,PanditSelfServiceTest,PanditAdminTest}.php and
 tests/Modules/Booking/ (BookingReservationTest, BookingIdempotencyTest, new BookingRebookTest,
 BookingQuoteCheckoutTest, policy-bridge tests); SLA tests use $this->travel()->hours() (repo convention, no
 setTestNow); recompute run-twice-equal; previews assert zero rows. Frontend: extend
 tests/customer/booking-actions.test.ts + laravel-backend.test.ts, tests/partner, tests/contracts, Jest
 apps/web/**/__tests__. §11 rule: extend existing suites before naming new ones.

 Verification

 Per-package exits above; final: full WP0 ledger re-run green (except documented legacy phpstan baseline), route:list
 v1+v2 inventory committed, Playwright A29+B journeys against platform-navdhya serve with seeded tenant + shakha
 reference data. deptrac blind spot (Application/, Console/, Models/ unlayered; Search/Contracts → Pandit/Models leak)
 explicitly deferred — do not fix mid-flight.

 Deferred (sequel)

 Samagri-kit tables; operational daemons (reconciliation, dunning, tax returns); Billing P6 verification beyond
 baseline; v1 removal (consumer inventory first); MySQL stress harness (phpunit.mysql.xml has no processIsolation);
 A30–A32; deptrac layer additions; converting packages/pandit/packages/availability scaffold into consumed code.

 Key files

 platform-navdhya/: config/platform.php, routes/api.php, routes/console.php, deptrac.yaml,
 app/Modules/Booking/{Routes/api-v2.php,
 Http/Controllers/{ReservationController,ReservationV2Controller,InstantSearchController}.php, Http/Support/*,
 Application/*, Domain/{CommandDeduplicator,ResourceAllocationManager}.php, module.json},
 app/Modules/Pandit/{Contracts/*, Application/PanditSchedulePolicyService.php, Console/*, Database/Migrations/*,
 Http/openapi.yaml, module.json, Pandit{Assignment,Availability}Manager.php, Http/Controllers/*},
 app/Modules/Tax/{Contracts,ModuleServiceProvider}.php, tests/Modules/{Booking,Pandit}/. apps/customer/:
 adapters/backend/laravel-client.ts, app/api/bookings/**, application/bookings/**,
 presentation/bookings/BookingDetailPage.tsx. apps/web/: app/{book,pandits,find-pandit}/**, lib/pandits/data.ts,
 components/QuickBook.tsx. apps/partner/: .env.local, app/api/assignments/**, presentation/pandit/**.
 packages/booking/src/contracts/BookingIntent.ts. e2e/.

 -------

 
 Here is Qoder's plan:

────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 Combined Program — Booking V2 + Pandit Build-Out + Temple Domain Deep Slice (C)

 Context

 Reality check (verified): no green baseline exists (planner-08nov.md:707); the P1–P6 backend slice is uncommitted (44
 untracked paths + 54 modified files). The quoted Temple spec (deities, sanctum rules, aarti schedules, priest
 councils, homa/darshan capacity, venue limits) is not in the planner — nearest authority: line 385 ("temple capacity…
 participate in reservation guards") and A5 (line 782: "competing customers book the same provider/temple → capacity
 respected"). Today capacity is decorative: temple_pujas.capacity/schedule_json.capacity are stored/displayed but never
  consulted — InitiateCheckoutHoldUseCase.php:64-73 passes a hardcoded literal 10 to holdTempleSlot();
 resource_allocations.units is written as 1 everywhere and never summed; InstantSearchController.php:118-169 lists
 temple slots whose own docblock admits "capacity is declared, never enforced"; no test asserts configured capacity.
 selected_temple_id gets zero validation at draft creation (CreateReservationDraftUseCase.php:97) and no UI ever sets
 it — the temple booking path is unreachable end-to-end.

 Security finding (drives C0): the 11 partner dashboard/puja endpoints (Temple/Routes/api.php, scope:temple:partner
 only) pass URL {templeId} straight into TempleDashboardService/TemplePujaManagementService with no
 whereHas('managers') membership gate — intra-tenant horizontal IDOR (TempleManagementService::partnerProfile():234-240
  shows the correct pattern). Capability middleware alone can't fix it: every partner token has the scope.

 User-approved decisions: (1) full bookable path — customers book homa/darshan slots end-to-end; (2) priest council =
 approval workflow — per-booking council decisions with SLA/escalation; (3) aarti + sanctum = operational guards.
 Architecture rules hold: contract bridge (no materialization into resource_calendars, mirrors approved Pandit B2),
 planner line 158 ("expose contracts to Booking, never mutate booking rows"), line 378 ("recheck eligibility, policy,
 capacity inside the transaction"), line 664 (single engine behind v1+v2).

 Design corrections from verification: ConfirmBookingFulfillmentUseCase is orphaned (constructed only in tests) — the
 natural, zero-churn approval hook point that WP2 wires anyway. Cross-module id columns carry no FKs
 (selected_pandit_id/selected_temple_id are string(26)) — follow that convention. deptrac has no Application/ collector
  — C-boundaries are pinned by a new tests/Architecture test, not config.

 Slices A (Booking V2, WP1–WP7) and B (Pandit taxonomy/policy/rules, B1–B6) are unchanged from the approved plan; this
 revision deepens Temple. A/B one-line summaries: A = WP1 v2 routing plane (config/platform.php api_versions,
 Routes/api-v2.php) → WP2 ReservationV2Controller (quote/checkout/rebook, read-only previews, ETag,
 CommandDeduplicator, 202-pollable, ErrorEnvelope; v1 delegates to same use cases) → WP3
 Tax/Contracts/QuotesCommercials gate (accepted_quote_hash, 422 coverage_unavailable) → WP4 customer BFF v2
 (laravel-client.ts:82 version arg, If-None-Match, stable idempotency) → WP5 web handoff (buildBookStartUrl replaces
 alert stubs; BookingIntent additive fields) → WP6 partner pandit fulfillment (:start, env flags) → WP7 e2e A29. B = B1
  taxonomy (tradition enum/shakha table/gotra constants in Pandit/Contracts) → B2 PanditSchedulePolicy
 (buffers/blackouts) → B3 Booking guards consult policy + pandit_service_eligibility → B4 acceptance rules +
 pandit:process-assignment-sla + pandit:recompute-ratings → B5/B6 partner editors + tests.

 WP0 — Baseline stabilization (gates everything)

 Review diff + untracked list; logical commits (fix(baseline) backend, docs for planner-08nov.md); exclude the .ini
 junk; no CRLF renormalization. Gate ledger (§11): php artisan test, pint --test, phpstan --no-progress
 --memory-limit=1G, deptrac analyse --no-cache, api:compose --check, platform:doctor --json, targeted vitest run, pnpm
 --filter @navdhya/web test (Jest), pnpm verify, pnpm build. Pre-existing failures documented in commit body, not
 fixed. Exit: clean tree + committed ledger.

 C0 — Temple ownership gate (immediately after WP0 — security, independent)

 whereHas('managers') (accepted invitations only) inside TempleDashboardService + TemplePujaManagementService queries
 keyed by {templeId}; ownership-safe 404 collapse per module openapi posture. Add capability:temple:dashboard:view /
 :puja:manage on the 11 routes as defense-in-depth. New ownership matrix in tests/Modules/Temple/ proving cross-manager
  404 on every affected route. Exit: matrix green; no new red in ledger.

 WP1–WP2 — v2 plane + ReservationV2Controller (unchanged; presenter must be C-ready)

 ReservationPresenter's server-derived status_detail/actions machinery (§9:684) is where C's PENDING_COUNCIL_APPROVAL
 surfaces later — build it data-driven now, no Temple knowledge needed.

 C1 — Temple schema (expand-only, 2026_11_09_0000NN, tenant_shared)

 - temples + timezone varchar(64) NOT NULL DEFAULT 'UTC' (default preserves behavior; partner edits set IANA), +
 daily_booking_cap uint NULL.
 - temple_deities: ulid id, tenant/temple ids, name, lineage, iconography_url, seniority, status, soft delete; uniq
 (tenant,temple,name).
 - temple_aarti_schedules: deity FK, day_of_week, start/end time (wall-clock in temples.timezone),
 blocks_darshan/blocks_homa bool default true, occupancy_units; uniq (tenant,temple,deity,day,start).
 - temple_sanctum_rules: nullable deity_id (null = temple-wide), max_occupancy_units, entry_constraints json
 (dress/age/footwear/photography), allows_multiple_events bool; uniq (tenant,temple,deity_id).
 - temple_venue_limits: scope ∈ {CONCURRENT_EVENTS, DAILY_HEADCOUNT, DAILY_BOOKINGS, SLOT_TYPE}, nullable slot_type,
 limit_units, effective range; uniq (tenant,temple,scope,slot_type,from). Physical venue limits live here; ritual
 capacity stays on temple_pujas; deity occupancy in sanctum rules — min() of all four is the answer, never a stored
 materialization.
 - temple_priests: pandit_id ulid (indexed, no FK — matches selected_*_id convention), denormalized user_id, role ∈
 {HEAD_PRIEST,PRIEST,ASSISTANT}, officiable_skus json, seniority, council_member bool; uniq (tenant,temple,pandit_id).
 - temple_pujas + slot_type ∈ {PUJA,DARSHAN,HOMA} default PUJA, min/max_persons NULL, requires_approval bool default
 false, approval_sla_minutes default 240 — defaults ⇒ zero behavior change for existing rows.
 - temple_booking_approvals: booking_id, temple_id, revision_id, state ∈ {PENDING,APPROVED,DECLINED,ESCALATED,EXPIRED},
  quorum_required, requested_by/decided_by, decision_reason, required_role ∈ {ANY_COUNCIL,HEAD_PRIEST},
 sla_expires_at/hard_expires_at, facts json; uniq (tenant,booking_id,revision_id), idx (tenant,state,sla_expires_at).
 - resource_allocations: no change — adopt the existing units column.

 C2 — units becomes the capacity primitive

 Enforcement in ResourceAllocationManager::holdTempleSlot:80-96 changes COUNT(*) overlap → SUM(units) overlap;
 signature gains trailing int $units = 1 so all existing call sites and ResourceAllocationManagerTest.php:98 stay
 green. Migration-safe by construction: every pre-existing row is units=1, so SUM ≡ COUNT on all old data — no
 backfill. Homa = units 1 (per-event); darshan = units = persons. holdPanditSlot untouched (strict 1/slot).
 Serialization remains lockForUpdate on the calendar row.

 C3 — Approval hooks FULFILLMENT (post-PAYMENT_CONFIRMED) — decision + justification

 - BookingLifecycle::TRANSITIONS['PAYMENT_CONFIRMED'] = [CONFIRMED, REFUNDED, FAILED] already permits
 approve→CONFIRMED, decline→REFUNDED: zero lifecycle edits, docs/STATE-MACHINE.md gains no temple state.
 - Gate is a read-model check inside ConfirmBookingFulfillmentUseCase (the currently-orphaned use case WP2 wires): open
  PENDING approval ⇒ confirm returns booking unchanged; presenter derives status_detail=PENDING_COUNCIL_APPROVAL,
 actions=[] (PAYMENT_CONFIRMED already yields no actions — the pollable-202 pattern falls out for free).
 - Pre-hold (pre-payment) approval rejected: holds can't outlive 15-min expiry without re-acquiring capacity →
 reintroduces the exact race WP3/B3 closes. Post-hold stranded-capacity is impossible because allocations COMMIT at
 payment, before approval.
 - Quorum: HOMA → HEAD_PRIEST; DARSHAN → any council_member; two-head quorum only when request exceeds venue
 CONCURRENT_EVENTS. Maker/checker: decided_by must be a council member ≠ assigned priest's user_id for that temple
 (use-case-enforced, tested).
 - council:process-approval-sla (new Temple/Console/, bootModule() registration per
 Booking/ModuleServiceProvider.php:52-54, scheduled in routes/console.php): sla_expires_at → escalate to HEAD_PRIEST +
 fact; hard_expires_at → auto-DECLINE reason approval_timeout + 100% refund (distinct refund_reason for Finance;
 cancellation tiers explicitly do not apply — customer is not the defaulter).
 - Outbox facts: temple.approval.requested/escalated/decided/expired.

 C4 — Policy contracts (bridge, mirrors B2)

 Temple/Contracts/TempleSchedulePolicy.php → slotIsBookable(templeId,pujaId,startAt,endAt,units):
 {ok,reason,effective_capacity,remaining_units,blocked_by[]}; TempleCapacityPolicy.php → effectiveCapacity,
 reservedUnits, approvalRequired. All-scalar signatures (Contracts may only touch Support — no Carbon in ports). Impls
 in new Temple/Application/, bound in Temple/ModuleServiceProvider::registerModule().
 effectiveCapacity = min(temple_pujas.capacity, schedule_json window, sanctum.max_occupancy, venue.CONCURRENT_EVENTS −
 active, venue.DAILY_HEADCOUNT − todayUnits) − Σ(committed + unexpired-held units) − aarti-overlap units.
 Consumers: CreateReservationDraftUseCase (new ctor dep after SKU check :44-47 → 422
 temple_not_eligible/temple_slot_full); InitiateCheckoutHoldUseCase:64-73 (replace literal 10, pass units=persons,
 policy re-read inside the transaction after lockOrCreateCalendar's lock — advisory number + under-lock re-sum =
 defence in depth per line 378); reschedule preview + InstantSearchController::templeSlots (fill remaining_units, drop
 full windows) — search/checkout/preview cannot diverge.
 Timezone: materialize aarti/schedule wall-clock via new DateTimeZone($temple->timezone)->utc(); lockOrCreateCalendar
 gains ?string $timezone='UTC'. DST tests required: aarti crossing midnight, slot straddling UK/IN DST boundary.

 C5 — Full bookable path (lands with WP5+B5)

 - BookingIntent additive templeId?, slotType?, persons? (v stays 1); reuse pujaId + slotRef — slotRef becomes an
 opaque server-issued temple:{templePujaId}:{date}:{startTime}:{units} token re-validated by the policy at draft time
 (today slotRef is validated nowhere). resolve-book-start.ts adds temple to needs[] when slotType !== 'PUJA'.
 - Web: apps/web/app/(seo)/temples/[slug]/page.tsx:192 bare /book link → buildBookStartUrl with real slot;
 app/pujas/temple/client.tsx (pure listing today) gains the slot picker + handoff.
 - Customer: app/api/temples/[templeId]/availability returns remaining_units; checkout view renders
 PENDING_COUNCIL_APPROVAL and polls via WP4's If-None-Match.
 - Pricing: today ReservationController.php:419-421 quotes catalog-only — WP3's QuotesCommercials gains a per-temple
 line modifier keyed on selected_temple_id (temple_pujas.price_override_minor), darshan quotes quantity = persons.

 C6 — Partner/admin surfaces (lands with WP6+B4b)

 Deities/aarti/sanctum/venue/priest editors under scope:temple:partner + new capability:temple:schedule:manage (no
 stepup:2 — reserved for verification transitions, consistent with existing routes). Approval queue: GET .../approvals,
  POST .../approvals/{id}:decide with Idempotency-Key; actor eligibility resolves via temple_priests.user_id ∪
 temple_managers.user_id (single partner identity — apps/partner/server/temples.ts:10). Replace empty mockPayload
 fixtures (route already laravelEnabled()-branched) + add approvals BFF.

 C7 — Artifacts

 Temple/Http/openapi.yaml +14 paths → api:compose; config/platform.php api_versions entry for temple; module.json +
 permissions temple:schedule:manage|venue:manage|approval:decide (sensitive) + 9 events; new tests/Architecture test
 pinning Temple/Application → Support|own Contracts and Booking→Temple only via Contracts (deptrac can't see
 Application — this test is the boundary).

 C8 — Tests (extend existing suites first, §11)

 Backend new: TempleCapacityEnforcementTest (configured 2 → 3rd 409 via the use case, not the manager),
 TempleAartiBlockTest, TempleSanctumCapTest, TempleVenueLimitTest, TempleDarshanUnitsTest (3-of-5 partial fill: next 3
 accepted / 1 refused), TempleApprovalBridgeTest ($this->travel() SLA escalation + hard-expiry→refund, quorum,
 double-decide idempotent replay, maker/checker refusal, lifecycle invariant sweep), guard-consistency test
 (search==checkout==preview remaining_units); extend tests/Modules/Temple/* ×5. Frontend: vitest resolve-book-start
 temple branch + tests/customer/temple-booking + tests/partner/approval-queue.test.ts; Jest web slot-ref handoff;
 Playwright A29 extended: darshan→pay→PENDING→approve→COMPLETED and decline→REFUNDED.

 Merged ordering

 WP0 → C0 → WP1 → WP2 → B1 → [C1+C2+C3+C4 = WP2.5, before WP3] → B2 → WP3(temple pricing line) → B3 → WP4 → B4 →
 WP5+B5+C5 → WP6+B4b+C6 → WP7+B6+C8

 Accepted risks: (1) units/SUM enforcement is only as strong as the lockForUpdate row lock — SQLite cannot prove it;
 raises priority of the deferred MySQL barrier lane (A5 now means "last 2 darshan heads"); (2) post-payment approval
 takes money before possible decline — mitigated by SLA + auto-refund, but a temple with no HEAD_PRIEST row times out
 into refunds → ship a pre-flight council-completeness admin report; (3) timezone DEFAULT 'UTC' silently mis-times
 aarti for unedited temples → pair with verification-blocking check; (4) Application-layer boundary is test-enforced
 only.

 Verification

 Per-package exits above; final: WP0 ledger re-run green (legacy phpstan baseline excepted), route:list v1+v2 inventory
  committed, C0 ownership matrix green, Playwright A29+temple journeys green against seeded tenant (deities, aarti,
 sanctum rules, venue limits, council roster incl. head priest, one requires_approval homa + one darshan with capacity
 5).

 Deferred (sequel)

 Samagri-kit tables/stock; reconciliation/dunning/tax-return daemons; Billing P6 verification; v1 removal (consumer
 inventory first); MySQL barrier stress lane (priority raised by C2); A30–A32; deptrac Application-layer config fix;
 packages/pandit/packages/availability scaffold conversion.

 Key files (Temple-critical first)

 platform-navdhya/: app/Modules/Temple/{Routes/api.php, module.json, Http/openapi.yaml,
 Temple{Dashboard,PujaManagement,Management}Service.php, Contracts/*, Application/*, Console/*, Database/Migrations/*}
 · app/Modules/Booking/Domain/ResourceAllocationManager.php,
 app/Modules/Booking/Application/{InitiateCheckoutHold,CreateReservationDraft,ConfirmBookingFulfillment}UseCase.php,
 app/Modules/Booking/Domain/BookingLifecycle.php, app/Modules/Booking/Http/Controllers/InstantSearchController.php,
 app/Modules/Tax/Contracts/QuotesCommercials.php, config/platform.php, routes/console.php, tests/Modules/Temple/,
 tests/Architecture/. apps/: web/app/(seo)/temples/[slug]/page.tsx, web/app/pujas/temple/client.tsx,
 customer/app/api/temples/** + application/bookings/resolve-book-start.ts, partner/server/temples.ts +
 app/api/temple*/**. packages/booking/src/contracts/BookingIntent.ts. e2e/.

 -----
 
 Here is Qoder's plan:

────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 Master Plan — Phase 7 Programs: (A) Booking V2, (B) Pandit, (C) Temple, (D) Vendor/Samagri — separate detailed program

 Context

 Reality check (verified): no green baseline (planner-08nov.md:707); P1–P6 backend slice uncommitted (44 untracked
 paths + 54 modified files). The quoted module specs for Pandit/Temple/Vendor are target-product prose, mostly not in
 the planner — nearest authorities: line 385 (kit stock in reservation guards), L254–256 (vendor stock: add
 reserved/version counters; enforce reserved <= on_hand; never reserve nonexistent stock), L158 (modules expose
 contracts to Booking, never mutate each other's rows), A5/L782 ("last kit" contention), L578–583 ("a compensation
 total is not a payout… final pre-dispatch recheck"), L309 (extend the vendor payout read model, don't fork totals).

 Verified Vendor reality: stock ledger exists but is a phantom — stock_reservations + StockReservationManager live in
 Booking, yet holdStock()/consumeStock() have zero production callers, InitiateCheckoutHoldUseCase never touches stock,
  and vendor_inventories.on_hand's only writer is a manual inventory:adjust. vendor_inventories is structurally
 single-warehouse (UQ(tenant,vendor,sku)); no BOM anywhere (a Satyanarayan kit cannot bundle wood+ghee+samagri);
 DISPATCHED/DELIVERED are bare labels (zero shipment/carrier/tracking concepts repo-wide); vendor_payouts is an orphan
 stub vs Finance's real MakerCheckerPayoutService; module.json's 9 events are never dispatched; Booking's
 StockReservationManager imports Vendor\Models\VendorInventory — a live layering violation; no vendor route has
 throttling.

 User-approved for D: full location dimension, booking-driven auto-fulfilment (no standalone kit storefront), internal
 shipment ledger (no courier API). Programs A/B/C stand as previously approved (baseline+V2+surfaces; Pandit
 taxonomy/policy/rules; Temple capacity enforcement/council approval/IDOR fix).

 Main Program — A/B/C (condensed; details in git history of this plan)

 - WP0 baseline: audit diff + untracked list → logical commits (fix(baseline); exclude .ini junk, no CRLF renorm) →
 gate ledger (php artisan test, pint --test, phpstan --memory-limit=1G w/ documented legacy baseline, deptrac,
 api:compose --check, platform:doctor --json, targeted vitest run, Jest for apps/web, pnpm verify, pnpm build). Exit:
 clean tree + committed ledger.
 - C0 Temple IDOR (first fix): whereHas('managers') inside TempleDashboardService/TemplePujaManagementService +
 capability on the 11 un-gated routes + ownership matrix test (intra-tenant horizontal IDOR confirmed).
 - WP1 v2 plane: config/platform.php api_versions per-module map; routes/api.php:22-34 loops versions;
 Booking/Routes/api-v2.php. WP2 ReservationV2Controller: create/list/detail(weak ETag)/history/changes, :quote,
 :checkout, cancel+reschedule read-only previews/submit, :rebook (new RebookBookingUseCase), GET operations/{id}; wire
 CommandDeduplicator+booking_commands; Http/Support/ReservationPresenter (data-driven status_detail/actions — C's
 PENDING_COUNCIL_APPROVAL and D's tracking surface here); BookingProblemResponder→ErrorEnvelope
 401/403/ownership-404/409/422/202; v1 store()/confirm() refactored to delegate to Application/ use cases.
 - B1 Pandit taxonomy (tradition enum/gotra constants/shakha table in Pandit/Contracts; allowlist+filters+search
 facets+openapi). B2 PanditSchedulePolicy bridge (blackouts table, travel_buffer_minutes/radius/timezone cols,
 Pandit/Application/ service). B3 Booking guards consult pandit policy (CreateReservationDraftUseCase.php:96
 eligibility + pandit_service_eligibility(pandit,sku); InitiateCheckoutHoldUseCase.php:49 buffer-widened holds;
 InstantSearchController.php:97-109 same gate). B4 pandit_assignment_rules + pandit:process-assignment-sla +
 pandit:recompute-ratings (from orphaned rating cols + BookingReview; derived total_bookings). B5 partner pandit
 editors.
 - WP3 tax gate: Tax/Contracts/QuotesCommercials→CommercialQuoteService; 422 coverage_unavailable; accepted_quote_hash
 + 409 quote_stale; two-committed-transaction discipline (ReservationController.php:429-469).
 - C1–C4 Temple: schema (temples.timezone/daily_booking_cap; temple_deities; temple_aarti_schedules wall-clock in
 temple tz with blocks_darshan/homa; temple_sanctum_rules; temple_venue_limits scopes
 CONCURRENT_EVENTS/DAILY_HEADCOUNT/DAILY_BOOKINGS/SLOT_TYPE; temple_priests (pandit_id no-FK, roles, officiable_skus,
 council_member); temple_pujas + slot_type PUJA|DARSHAN|HOMA, persons bounds, requires_approval;
 temple_booking_approvals); adopt resource_allocations.units for darshan (COUNT→SUM under existing lockForUpdate;
 migration-safe since all rows are units=1); approval hooks post-PAYMENT_CONFIRMED (zero lifecycle edits:
 approve→CONFIRMED, decline→REFUNDED @100% — tiers don't apply; quorum HOMA=HEAD_PRIEST, DARSHAN=any council;
 maker/checker vs assigned priest; council:process-approval-sla escalate→auto-decline);
 TempleSchedulePolicy/TempleCapacityPolicy contracts — effectiveCapacity = min(puja cap, schedule window, sanctum max,
 venue limits) − Σ(committed+held units) − aarti overlap, re-checked inside the checkout transaction (replaces
 hardcoded 10).
 - WP4 customer BFF v2 (laravel-client.ts:82 version arg, If-None-Match, stable idempotency per §9:696). WP5+B5+C5 web
 funnel: buildBookStartUrl replaces alert stubs (apps/web/app/book/client.tsx:50-55,
 app/pandits/book/client.tsx:46-51); BookingIntent additive
 durationMinutes/timezone/tenantId/templeId/slotType/persons; server-issued opaque slotRef validated by policy;
 MOCK_PANDITS retired. WP6+B4b+C6 partner: PARTNER_BACKEND env on, accept→laravel, PanditAssignmentManager::start() +
 assignments/{id}:start (file co-owned with B4 auto-accept + C council queue approvals/{id}:decide), Temple editors.
 WP7+B6+C8 e2e: playwright.config.ts:20 testMatch widened; A29 journey + pandit buffered-conflict/auto-accept + temple
 darshan approve→complete and decline→refund.

 Merged ordering: WP0 → C0 → WP1 → WP2 → B1 → [B2 ∥ C1-C4] → WP3 → B3 → C-guards → WP4 → B4 → WP5+B5+C5 → WP6+B4b+C6 →
 WP7+B6+C8, plus Program D below woven at D-points.

 ---

 PROGRAM D — Vendor, Samagri Kits, Multi-Warehouse Stock & Dispatch (separate, advanced)

 Posture: Vendor owns stock truth and dispatch; Booking consumes only via Vendor/Contracts (all-scalar ports —
 Contracts→Support-only honored). D0–D3, D5-partner, D6 are fully standalone; only D4 is hard-blocked on WP2/WP3.

 D0 — Preflight hardening (standalone, lands first after WP0)

 1. Throttle every vendor route (Routes/api.php has zero): throttle.tier for machine principals and explicit caps on
 writes — inventory:adjust + orders:advance/:cancel → throttle:30,1; order store → throttle:10,1; reads →
 throttle:60,1; admin verify/suspend keep stepup:2. (Humans ride bootstrap/app.php:26 throttleApi('api'); precedent:
 Pandit throttle:60,1.)
 2. Wake the 9 dead events: new Vendor/Listeners/VendorLifecycleNotifier with EVENT_TYPES = module.json names,
 registered per the sole-working pattern Booking/ModuleServiceProvider::bootModule(); Outbox::append() inside mutating
 transactions (dual-write window forbidden) in VendorInventoryService::adjust +
 VendorOrderService::place/advance/cancel; vendor.payout.recorded lands with D6. OutboxDrainCommand's unhandled report
 is the assertion surface.
 3. Fix the deptrac blind spot (pre-existing bug): add Application to the ModuleCore collector regex in deptrac.yaml
 (Application|Domain|Events|Listeners|Jobs|Policies|Database) — otherwise D2's Vendor/Application is unenforced.

 D1 — Schema chain (expand→backfill→swap, forward-only, tenant_shared, 2026_11_XX)

 - vendor_warehouses: ulid/tenant/vendor FK cascade, code UQ(tenant,vendor,code), label/city/state/address, is_default,
  active; backfill one DEFAULT row per existing vendor in-migration.
 - vendor_inventories: add location_id NULL + reserved uint 0 + version uint 1 → backfill from vendor default
 (UPDATE…JOIN) → drop UQ(tenant,vendor,sku), add uq_vendor_stock_loc(tenant,vendor,sku,location_id) → flip NOT-NULL +
 FK same-step (MySQL constraint). down() reverses exactly.
 - inventory_movements (append-only): sku, location, type ∈
 {ADJUST,HOLD,RELEASE,COMMIT,CONSUME,TRANSFER_OUT,TRANSFER_IN,RETURN}, signed bigint delta_signed (only signed column;
 on_hand stays unsigned), balance_after, transfer_id, soft ref_type/ref_id (booking|vendor_order|manual|reservation),
 actor, note; immutability enforced by code + guard test, not DB perms.
 - vendor_kit_components(kit_sku, component_sku, vendor_id, quantity>0), UQ(tenant,kit,component): kits stay ordinary
 ACTIVE catalog_items rows (no kind column exists; a flag would mean cross-module schema edit) — BOM lives entirely in
 Vendor; kit_sku resolves via ListsCatalogItems.
 - vendor_orders: booking_id NULL + UQ(tenant,booking_id,vendor_id) (NULLs don't collide → manual orders unaffected),
 address_snapshot json, delivery_pincode, quote_hash; items gain line_kind/stock_sku/stock_location_id. Decision: no
 component fan-out rows in items — sold lines stay as sold (matches what the customer paid); physical decrement is
 recorded once, in CONSUME movements (ref_type='vendor_order').
 - shipments (order FK cascade, state PACKED|HANDOFF|IN_TRANSIT|DELIVERED|RETURNED, carrier_name, tracking_code,
 dispatched/delivered_at, pod_note; UQ(tenant,order_id), idx state+tracking) + append-only shipment_events.
 - stock_reservations.location_id backfill + index — table is NOT relocated (moving a table with live rows for a naming
  argument is a drop-and-recreate; ownership changes at the code layer, not schema layer).
 - vendor_payouts + orders_count/gross_minor/platform_fee_minor/source DEFAULT 'PROJECTION' (L309 conflicting-totals
 guard).

 D2 — Stock truth engine (standalone; the ownership inversion)

 New Vendor/Contracts/ManagesStockReservations.php + VendorStockPolicy.php (all-scalar
 hold/release/commit/consume/availableAt/resolveKit); impl Vendor/Application/StockReservationService.php:
 lockForUpdate() the stock row (matching ResourceAllocationManager:178 precedent; optimistic version rejected — the
 counter invariant rides the lock, version increments anyway for evidence), enforce reserved + units <= on_hand against
  the new reserved counter — not today's SUM(stock_reservations) re-derivation which degrades under contention.
 Movement + row write + reservation INSERT in one tenant transaction. Booking/Domain/StockReservationManager →
 deprecated shim delegating to the port; new Vendor/Models/StockReservation.php (same table) so Booking's Domain ends
 with **zero Vendor\ imports**, killing the live deptrac violation. StockReservationManagerTest is **retargeted, not
 rewritten** (same 3 scenarios through the port + location pinning); behavioural home becomes VendorStockLedgerTest.
 vendor:reconcile-stock {--tenant} {--fix} — report-only first: on_hand == SUM(movements) and reserved == SUM(active
 reservations) per row; violations emit the L966 ops alert; --fix writes an ADJUST correction movement, never an
 UPDATE. Kit availability = min(floor(component_available/qty)), depth 1 — component-with-BOM → 422
 kit_depth_unsupported, self-ref rejected, cross-vendor component → 422 kit_foreign_component.

 D3 — Routing + transfers

 Routing rule, stated honestly: explicit active location_id → else vendor's is_default warehouse → else oldest active.
 No nearest-by-city engine (no coverage/pincode service exists to route against; inventing one is sequel work — seam
 documented as VendorStockPolicy::routeFor(...)). All kit components must resolve to a single warehouse; split BOM →
 422 kit_coverage_unknown. Transfers: one transaction, both rows locked in deterministic location_id order
 (deadlock-free), paired TRANSFER_OUT/IN movements sharing transfer_id, 409 on version conflict. No TRANSIT
 pseudo-location — in-and-out in the same commit; nothing double-counted; cost (momentarily unlocatable in-transit
 stock) documented as acceptable for internal moves.

 D4 — Booking seam (the only hard dependency: WP2/WP3)

 - Draft: CreateReservationDraftUseCase gains lines[{sku,quantity,line_kind}] — booking_lines.line_kind already exists
 (string(32) default 'SERVICE'); KIT lines append to the SERVICE line 1.
 - Checkout: InitiateCheckoutHoldUseCase — inside the existing transaction, after the calendar-hold loop over lines,
 KIT branch calls hold() per component fan-out at the routed warehouse (calendar first = cheap/high-contention
 ordering; any ResourceUnavailableException rolls back calendar holds too). Codes: 409 kit_unavailable (+ component_sku
  reason), 422 kit_coverage_unknown. A5 "last kit" rides the deferred MySQL barrier lane — its priority is raised.
 - Expiry: ReapExpiredHoldsCommand (verified: touches ResourceAllocation only) gains expireFor(tenant,bookingId) stock
 leg (HELD&&expired → EXPIRED, reserved -= units) before the booking-expiry leg.
 - Cancel/failed: BookingPaymentTransitions:99 releaseStock becomes the real port call + stock.reservation.released
 fact.
 - Confirm: orphaned ConfirmBookingFulfillmentUseCase (wired by WP2, gated by C3 approval before reaching here) →
 commit() + consume() (decrements component skus at the held warehouse, never the kit sku) → auto-creates exactly one
 vendor_order per involved vendor via ManagesVendorOrders::placeForBooking(), idempotent through the
 UQ(tenant,booking_id,vendor_id) (replay returns existing order), copying address_snapshot/delivery_pincode from
 booking + items from booking lines; emits vendor.order.placed. Shipment rows are created by the vendor's first
 dispatch action (at PACKED), not here.

 D5 — Dispatch lifecycle

 Extend VendorOrderService::advance() match to NEW→PROCESSING→PACKED→HANDOFF→IN_TRANSIT→DELIVERED (+RETURNED from
 DELIVERED|IN_TRANSIT); migration …000009 renames existing DISPATCHED rows to HANDOFF so no row carries both spellings.
  Gates: PACKED→HANDOFF requires carrier_name+tracking_code (422 shipment_details_required) AND the money recheck —
 booking status ∈ {CONFIRMED, PAYMENT_CONFIRMED} or payment port captured, else 409 payment_not_confirmed (L578 "goods
 don't move unless money is verified"). DELIVERED requires pod_note (no unsigned delivery; admin force-write via
 capability+stepup:2 logged actor='system:forced'). Every hop appends shipment_events + vendor.order.advanced.
 Reads/CRUD: movements feed, shipments, shipment:advance, warehouses, transfers, kit-BOM editor (partner products
 extension). Customer tracking: apps/customer/app/api/bookings/[bookingId]/shipment/route.ts mirroring the existing
 bookings BFF, backed by Vendor/Contracts/ReadsShipments::forBooking() — deliberately never exposes vendor-order ids.
 packages/resource-management/inventory.ts stays untouched scaffold (note only).

 D6 — Payout projection (READ ONLY)

 vendor:accrue-payouts {--period=YYYY-MM} {--tenant}: per (vendor, period) sum DELIVERED orders' total_minor minus
 platform_fee_minor (honest gap: no fee data exists today — auto-create writes what the quote supplies, else 0 with
 source='PROJECTION'); upserts PENDING only. Vendor never writes PAID — Finance MakerCheckerPayoutService stays sole
 pay authority; VendorPayoutController::index keeps reading the extended stub (L309). Idempotent per period (recompute,
  never append).

 D7 — Tax hook (interface requirement on WP3, no build)

 CommercialQuoteService lines carry no goods/service discriminator (GST split rides tax_code→TaxEngine). Requirement:
 KIT lines must quote with a goods-class tax_code (L438–456) or kits silently tax as services — logged, test-asserted
 deferral to the Tax sequel if no goods rate is seeded.

 D8 — Tests (extend existing first, §11)

 Extend Vendor{Module,Profile,Operations}Test (locations, 429 gates, outbox-fact assertions), retarget
 Booking/StockReservationManagerTest, invert EndToEndBookingToSettlementTest's "no vendor order" assertion once D4
 lands (verify not load-bearing), update vendor-contract.test.ts + tests/partner/vendor-bff.test.ts. New:
 VendorStockLedgerTest (two competing holds on last unit → one 409; never reserve nonexistent/other-vendor sku;
 balance_after reconciles; SQLite's implicit serialization means the negative test must force interleaving via manual
 version bump), KitBomTest, CheckoutStockSeamTest (rollback → zero reservations + zero movements; reap; cancel
 release), FulfillmentAutoOrderTest (one order+shipment per vendor per booking; replay idempotent; snapshot equality;
 CONSUME hits components), DispatchGateTest, TransferTest, PayoutProjectionTest (PAID never written by Vendor),
 tests/Architecture/VendorBoundaryTest (Booking imports Vendor only via Contracts). Playwright: extend A29 — kit
 booking → payment → auto order → PACKED → HANDOFF(AWB required) → DELIVERED → customer sees tracking; decline/late →
 stock released.

 D ordering & collision management

 D0 ∥ (anytime post-WP0) → D1 → D2 → D3 → D4 (after WP2+WP3) → D5 → D6; D5-UI with the WP6+B4b+C6 partner batch; D8 e2e
  with WP7+B6+C8. InitiateCheckoutHoldUseCase is a four-way collision zone (B3 + C2/units + D3-branch + D4) — strict
 sequence B3 → C-policy → D4, one author per package; D4's diff there is one appended loop + one constructor arg,
 keeping rebase cost bounded.

 Verification (all programs)

 WP0 ledger re-run green at each package exit; route:list v1+v2 inventory committed; ownership matrices (Temple C0,
 Vendor ownership trait already correct); vendor:reconcile-stock reports zero violations on seeded data; Playwright
 A29+ journeys green against seeded tenant (warehouses + kit BOM Satyanarayan{havan_wood×2, ghee×1, samagri×1} capacity
  5, one darshan capacity 5, one homa requires HEAD_PRIEST approval).

 Deferred (sequel)

 Reconciliation/dunning/tax-return daemons; Billing P6 verification; v1 removal; MySQL barrier stress lane (priority
 raised by C2 units + D A5 "last kit"); A30–A32; city/pincode-based warehouse routing; goods tax-rate seeding for KIT
 lines; courier API; standalone kit storefront; packages/resource-management + packages/fulfillment scaffold
 adoption-or-retirement decision; deptrac full Application-layer rollout beyond the D0 regex fix.

 Key files (Program D focus; A/B/C files as previously listed)

 platform-navdhya/: app/Modules/Vendor/{Routes/api.php, module.json, Http/openapi.yaml,
 Vendor{Management,Inventory,Order}Service.php, Http/VendorOwnership.php, Contracts/*,
 Application/StockReservationService.php, Console/*, Database/Migrations/2026_11_XX_*} ·
 app/Modules/Booking/Domain/StockReservationManager.php (shimmed) ·
 app/Modules/Booking/Application/{InitiateCheckoutHold,ConfirmBookingFulfillment,CreateReservationDraft}UseCase.php ·
 app/Modules/Booking/Models/BookingLine.php · app/Modules/Booking/Console/ReapExpiredHoldsCommand.php ·
 app/Modules/Booking/BookingPaymentTransitions.php · deptrac.yaml · tests/Modules/{Vendor,Booking}/,
 tests/Architecture/. apps/: partner/app/{vendors/**,api/vendors/**}, partner/server/vendors.ts,
 customer/app/api/bookings/[bookingId]/shipment/route.ts. e2e/ui.spec.ts.