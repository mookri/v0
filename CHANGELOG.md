# Changelog

All notable changes to Navdhya are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **PR B9** Load testing, failure drills, staged rollout (final phase):
  full-matrix load harness over both platform legs (client collector leg
  17k eps with injected faults/duplicates/backlog/recovery; P0 truth-path
  leg at 10k committed domain events with crash-replay duplicates,
  backlog drain and clean Level-1 reconciliation); six failure drills
  (collector outage, ingestion 500s, Pub/Sub backlog, consent-service
  outage, BigQuery quota exhaustion, misdeployed schema) with a gated
  drill-results table (measured detection/degradation-containment/
  recovery per drill); release rollback drill (dry-run trail → enabled
  hook executes once → verified return within the window); budget alerts
  at 50/80/100% with the test-pinned reduction ladder (diagnostics
  first, revenue last, never automatic); seven exercised runbooks;
  per-surface rollout playbook (state machine, two-week shadow for
  low-risk, revenue gated on explicit reconciliation); release-readiness
  checklist (14 items, evidence-named) with a rot-proof meta-test.
- **PR B8** Experiment ledger, exposure logging, error fingerprinting,
  release health: pre-registered experiment definitions (validation rejects
  conversion-only guardrail sets); deterministic assignment replicating the
  app primitive's algorithm bit-for-bit over Web Crypto (salt +
  allocation_version keyed); Postgres assignment/exposure ledger as the
  assignment authority (first-write-wins, exposure recorded only on actual
  display — assignment alone never exposes); automated SRM checks
  (chi-square, rigged fixtures flagged invalid); reproducible readouts
  (window-honest, guardrail-breach flags, SRM-invalid distrust verdict).
  Crash subsystem: cross-build fingerprinting (build hashes + framework
  frames stripped), strict PII redaction (Luhn-gated cards), bounded
  breadcrumb ring, adaptive sampling, release regression alerts with
  runbooks, crash-free rates pinned to web instrumentation only
  ("native crashes out of scope"), auto-rollback dry-run before enable.
  Restricted `signals_crash` BigQuery datasets + error_groups mart;
  retention matrix v1.1.0 (crash_events domain, 180d).
- **PR B7** Privacy lifecycle: versioned retention matrix v1.0.0 (all plan
  domains + registry class coverage, legal review owns final values);
  end-to-end `DeletionWorkflow` that fails closed on incomplete target sets,
  records suppression (consent ledger + erasure token) BEFORE executing
  systems, produces per-system timestamped evidence proofs, enforces the
  30-day SLA (`sla_breached`), and keeps partial failures visible; side-
  effect-free dry-run mode that can never masquerade as erasure; authoritative
  never-pruned erasure tokens with `fanout_only → full` upgrade and
  erasure-aware query filtering; purpose-scoped consent withdrawal (SLO
  15 min) that blocks fan-out before destinations run without erasing raw
  data; `PepperRing` HMAC pepper rotation with versioned identifiers
  (continuity within overlap, hard break after expiry, fail-closed elsewhere);
  Postgres privacy tables (erasure_tokens, consent_withdrawals, deletion_audit);
  `privacy-erasure` CLI (dry-run default, `--live` credential pre-flight,
  BigQuery DELETEs under byte cap, GA4 Data Deletion API, shell-free subprocesses).
- **PR B6** Rule engine, delayed actions, segments v1, anomaly detection:
  typed/versioned rule contract with deterministic action-idempotency keys
  (`rule@v:subject:sortedEventIds`) and per-action persisted records (rule
  version, triggering event IDs, decision, evidence, delivery status) so
  every fired action is explainable a year later; idempotent engine
  (idempotency → consent → cooldown → daily cap → window/sweep →
  authoritative-checked fire; suppressed decisions persisted with their
  why; inconclusive checks still fire and record why); `dispatchAction`
  boundary skips already-delivered requests (retry → effect once);
  six starter rules (checkout abandonment with `booking_exists` due-time
  check, booking follow-up, payment-failure support, zero-results
  catalog-gap report, KYC reminder, assignment-delay ops page); Postgres
  persistence (`rule_actions` migration + `PostgresRuleActionStore`,
  statement-level fake-tested); segments v1 — eight explainable
  memberships with evidence + BigQuery mart, NO segment→Remote-Config
  sync; robust median/MAD anomaly detection — annotate diagnostic
  families, page revenue-adjacent ones, affected window on the record;
  Remote Config guardrails — behavior/rollout flag allowlist, secret-like
  names rejected before the allowlist, typed values enforced.
  Acceptance: `tests/signals/rule-engine.test.ts` (replay, cancel-path ×2,
  guardrails, dispatch-once, explainability),
  `tests/signals/anomaly-detection.test.ts` (search annotate vs
  payment_succeeded page), `tests/signals/postgres-rule-actions.test.ts`.
- **PR B5** Warehouse staging, marts, reconciliation: version-controlled
  BigQuery assets at `infrastructure/database/bigquery/` — dataset DDL
  (partitioned/clustered `signals_raw.events` + `quarantined` + reconciliation
  tables, GA4 export kept in its own Google-managed namespace with a typed
  `signals_ga4` view), typed staging views, all seven mart families
  (funnels/retention/ltv/attribution/experiment_readouts/data_quality/
  operational_metrics), 3 data-quality tests, and the recorded
  `table-sizes.json` manifest. `toRawRow` now emits the `event_date`
  partition key (streaming inserts land in real partitions). **Cost gate**:
  `packages/analytics/src/warehouse/sql-lint.ts` + `tooling/scripts/
  warehouse-lint.ts` fail CI on missing partition filters, `SELECT *`
  without one, and statically over-budget scans (`maximum_bytes_billed`
  stays the production bill gate — recorded). **Nine nightly checks**
  (`packages/analytics/src/server/checks/`): missing domain events,
  duplicate IDs, unpublished-beyond-SLA, published-not-persisted (Level-1),
  quarantined records, booking/payment/refund divergence, volume swings,
  freshness, query cost — each with green/red paths, concrete diagnostics,
  an owner + runbook alert, and a data-trust badge whose green statement is
  exactly "Specified checks passed at T" (never an export-completeness
  claim). **Level-2 analytics parity** (`reconciliation/level2.ts`):
  consent-adjusted expectation, eventUniqueId dedup, export-lag exclusion,
  warn/alert drift thresholds, breach owner alerts — measurement only, never
  a replacement for Level-1. CLIs: `warehouse-lint.ts` (CI gate) and
  `warehouse-nightly.ts` (report + alerts + trust + optional `--level2`).
  Acceptance: `tests/signals/warehouse-nightly.test.ts` (injected failure
  per check + real-SQL cost gate) and `tests/signals/level2-parity.test.ts`
  (staging-week drift + breach alert).
- **PR B4** GTM wiring and key-event ownership: governed GA4 adapters in
  `packages/analytics/src/adapters/` — `ga4-data-layer.ts` (the only
  sanctioned browser → GTM path: registry `gtm_ga4` gate, `producer=client`
  + web-surface gate, schema-validated/forbidden-key-stripped params, the
  `purchase` double-fire guard, Consent Mode v2 defaults/updates with
  `UNKNOWN` → denied) and `ga4-measurement-protocol.ts` (server → GA4 MP:
  ≤25 events/request, ≤40-char wire names, ≤25 params, sessionized,
  consent-required, `client_id` resolved by the sending boundary and never
  invented, governed `payment_succeeded → purchase` mapping, `2xx` →
  *receipt only* — `processed` is unrepresentable). GTM bootstrap
  `apps/web/app/gtm-provider.ts` mounts in the root layout: consent defaults
  pushed before the external `gtm.js` loader, StrictMode-safe, inert without
  `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB`; CSP extended for googletagmanager /
  google-analytics only. Tag inventory + publishing separation + Preview
  runbook in `docs/analytics/gtm-tag-inventory.md`; driver-gated live MP
  validation via `tooling/scripts/validate-mp-endpoint.ts` (local spec
  validator runs in CI). Acceptance: `tests/signals/key-event-parity.test.ts`
  (one `purchase` server, one `purchase_viewed` browser, one
  `booking_confirmed` server — per destination, no duplicates/gaps) and
  `tests/signals/consent-matrix.test.ts` (denied → no GA4 tags, collector
  403s, zero rows, MP refuses; granted → all three paths).
- **PR B3** Interim first-party collector — one framework-neutral contract in
  `packages/analytics/src/collector/` (`pipeline.ts`, `contract.ts`,
  `rate-limit.ts`, `auth.ts`) behind two thin adapters: the Vercel Edge route
  `apps/web/app/api/ae/v1/batch/route.ts` and the Cloudflare Worker
  `services/workers/analytics-collector/` (`wrangler.toml`) — swapping is
  configuration (plan decision #5). Status map: 64 KiB cap → 413 (never
  partial); ≤25 events/batch and schema/registry violations → 400 naming the
  offender, all-or-nothing (batchmates reported as `deferred`, never silently
  forwarded); missing/forged/expired/denied consent token → 403 (fail-closed,
  never forwarded); per-IP/per-session fixed-window rate limits → 429 +
  Retry-After; **200 only after the ingestion durable-acceptance ack**;
  network error / upstream 5xx / upstream 401 (secret mismatch) / unset
  `SIGNALS_*` secrets → 503, safe to retry (insertId dedup absorbs replay).
- **PR B3** Delivery-policy gate at the edge: only `producer=client` +
  `collector_bigquery`-eligible + surface-permitted events may enter the
  collector; server P0 events and `internal_only` ad beacons are rejected
  before the forward. Forbidden property keys (email, phone, identifiers,
  secrets — data minimization) are stripped before validation and forwarding.
- **PR B3** Client SDK delivery completion
  (`packages/analytics/src/client/analytics-client.ts`): fetch-with-ack
  primary (events leave the outbox only on collector 200), `flushBeacon()`
  unload-only best-effort (never acked, excluded from the delivery-SLO
  denominator, counted in `beaconBestEffort`), per-batch consent token
  provider (decision #7), provenance `source` (default `shadow` while
  comparing), `metrics()`/`deliveryRatio()` = acknowledged ÷
  eligible-attempted, priority shed counted in `metrics.shed`.
- **PR B3** `services/functions/ingestion`: per-request `x-signals-source`
  provenance (valid enum only — invalid → 400, absent → server-configured
  default); canonical `SignalSource` in
  `packages/analytics/src/contracts/signal-source.ts` (raw-sink re-exports).
- **PR B3** Shadow-vs-baseline comparison
  (`packages/analytics/src/server/shadow-report.ts` +
  `tooling/scripts/shadow-report.ts`): counts per event/family, schema errors
  with samples, indicative latency per family over any window, honest `null`
  delta% when the baseline never saw an event; driver-free CLI (bq-exported
  JSON in, markdown out — no warehouse credential in the repo).
- **PR B3** Load/acceptance harness (`tests/signals/`): in-process Node
  harness driving collector → ingestion handler → sink with injected
  downstream-500 windows, duplicate sends and backlog drain — 10k events
  sustained + 2.5k burst: zero loss, zero duplicate rows, recovery to
  zero-lag (recorded: 8,201 eps, p95 6.7 ms; fault-free burst 10,940 eps);
  plus the full HTTP acceptance matrix (oversize, offender naming, replay,
  consent 403, fault 503s) at pipeline, worker and edge-route level.
- **PR B2** Transactional outbox contract (`packages/contracts/src/messaging/`):
  `OutboxRecord` gains `claimed_at` / `lease_expires_at` / `attempts` /
  `dead_lettered` / `published_at`; `OutboxStore` gains lease-based
  `claim(limit, {leaseMs})` (attempts incremented at claim), `release`,
  `markDeadLettered`, and idempotent `append` keyed by the stable signal ID.
  `TransactionContext.outbox` is the tx-bound emission seam.
- **PR B2** `PostgresOutboxStore` (`infrastructure/messaging/outbox/`):
  parameterized SQL with `FOR UPDATE SKIP LOCKED` claim, lease-expiry
  redelivery, attempts ceiling → terminal dead-letter; migration DDL
  `infrastructure/database/postgres/migrations/2026_10_12_000001_create_signals_outbox.sql`
  (claim partial index + Level-1 view). `MemoryOutboxStore` rewritten as the
  lease reference implementation with an injectable clock.
  `PostgresTransactionManager` now issues real `BEGIN`/`COMMIT`/`ROLLBACK`
  and hands the operation a `PostgresOutboxStore` bound to the same client —
  signal appends commit and roll back with the business write.
- **PR B2** Server P0 emitters (`packages/analytics/src/server/emitters.ts`,
  deep-import only — never re-exported from the package index):
  `payment_succeeded` / `payment_failed` / `booking_confirmed` build
  fully self-validated `SignalEnvelope`s (generated validator gate,
  server-producer-only guard, deterministic `srv_<type>_<aggregate>` session,
  ULID = outbox record ID end to end).
- **PR B2** Capability signal-sink ports + adapter: `PaymentSignalSink`
  (`packages/payment/src/ports/PaymentPorts.ts`) and `BookingSignalSink`
  (`packages/booking/src/ports/BookingSignalSink.ts`) let the application
  boundaries (`confirmCapture`, `confirmBooking`) emit semantic facts through
  an injected sink **before control leaves the operation** — architecture
  rules forbid capability → analytics imports, so
  `infrastructure/messaging/outbox/signal-sinks.ts` performs the wiring at
  the composition layer, bound to `tx.outbox`.
- **PR B2** `services/functions/outbox-publisher`: claim → publish →
  ack-only-after-Pub/Sub-acceptance; transient failure → `release` for
  immediate retry; attempts ceiling → dead-letter + `onDeadLettered` alert
  hook; `PubSubTransport` (REST `topics.publish`, bearer token injected,
  stable event ID in message attributes) and a thin Cloud Run poll loop.
- **PR B2** `services/functions/ingestion`: fail-closed validation against
  the generated registry (unknown event names and schema violations are
  **rejected, dead-lettered and never inserted**; all-or-nothing batches with
  explicit `deferred` counts), `receivedAt` stamping, `RawSink` with
  envelope-ID `insertId` dedup (`MemoryRawSink`, `BigQueryRawSink`
  streaming `insertAll` with enforced `source` column
  `first_party|server|shadow|ga4_export`), Worker authentication (HMAC +
  timestamp window + nonce replay store, constant-time compare, decision #6)
  and the HTTP handler's 401/400/200 semantics — **200 is returned only
  after the sink put resolves** (the durable-acceptance ack).
- **PR B2** Level-1 reconciliation
  (`packages/analytics/src/server/reconciliation/level1.ts` + `level1.sql`):
  committed vs published vs persisted over one ID space, reporting
  `missingPublished` / `missingPersisted` / `duplicatedPersisted` — every
  committed domain event is either persisted exactly once or visible in the
  gap report.
- **PR B2** Workspace glob fix (decision #9): `pnpm-workspace.yaml`
  `services/*` → `services/**` — landed in B2 (B3 item 1) because B2 creates
  the nested function packages; root `tsconfig.json` + root `vitest.config.ts`
  gain the `@services/*` alias.
- **PR B2** 79 tests: `tests/signals/` (Postgres SQL-shape/lease/attempt
  math against a statement-level fake `DbClient`; transactional emission —
  commit → exactly one record, rollback → none; truth-path end-to-end —
  crash-replay duplicates collapse to one row per envelope ID, unknown event
  → dead-letter + gap report, full transport outage loses nothing; Level-1
  gap detection) plus publisher (9) and ingestion (23) package suites.

### Changed
- **PR B2** `confirmCapture` / `confirmBooking` accept an optional injected
  signal sink (6th parameter / `emit.sink`); existing call sites unchanged.
  Registry params are snake_case at the boundary (`transaction_ref`,
  `service_category`).
- **PR B2** Architecture-governance debt repaid: intra-package relative
  imports in `packages/analytics` converted to `@navdhya/analytics/...`
  aliases (+ package-local `vitest.config.ts` so package-level runs keep
  working), `packages/contracts` self-import uses its alias, apps/web ads
  track routes use `@/app/api/ads/tracking-handler` (jest resolves it via
  next/jest tsconfig paths), and `rootDir` was dropped from touched package
  tsconfigs (cross-package path-mapped imports make TS6059 inevitable; these
  packages only ever typecheck with `tsc --noEmit`).

### Added
- **PR B1** Navdhya Signals contracts (`packages/analytics/src/contracts/`):
  `SignalEnvelope` (transport contract with **required** `session.seq` — the
  client-delivery metric denominator; ULID `newEventId`; structural
  `validateEnvelope` with query-string/param/breadcrumb bounds), consent
  state machine + HMAC-SHA256 per-batch consent token (fail-closed:
  `UNKNOWN` blocks collection; Web Crypto, no hand-rolled crypto), and the
  `delivery-policy` sink enum (`gtm_ga4 | collector_bigquery | server_ga4 |
  internal_only`).
- **PR B1** Event registry as code (`packages/analytics/src/registry/`):
  16 initial events (15 funnel-critical + ad-beacon family as
  `internal_only`) with owner/producer/surfaces/allowedSinks/params/
  privacyClass/retentionClass/sampling/priority. Generator
  (`npx tsx src/registry/generate.ts`) emits committed
  `generated/event-types.ts`, `generated/validator.ts` (fail-closed
  allowlist), `generated/schemas.json` and `generated/bigquery-docs.md`;
  `--check` exits 1 on drift, also enforced by tests.
- **PR B1** Registry lint gates (`registry/lint.ts`, runs via `turbo test`):
  structure (incl. forbidden param types), delivery-policy (P0/server can
  never route through `gtm_ga4`), pii-names (direct-identifier param names
  forbidden), high-cardinality (requires explicit `hcReviewed`), volume
  (≤500 events, ≤25 params/event), compat (breaking param changes require a
  version bump vs `baseline.json`), fixtures (valid envelope, known event,
  required params, type match, PII value scan). Every gate has a negative
  test proving it fires; the shipped registry passes clean.
- **PR B1** Signals client SDK (`packages/analytics/src/client/`):
  `createAnalyticsClient` (no-op when disabled, fail-closed without
  consent, registry-validated, **rejects server/P0 events from the
  browser**), peek/ack outbox (`MemoryOutbox` + `IndexedDbOutbox`; removal
  only after positive collector acknowledgement; shed ladder P3→P2→P1),
  bounded batcher (≤25 events), exponential backoff with full jitter, and
  the bounded breadcrumb ring buffer (cap 50, attached only to `app_error`).
- **PR B1** 53 package tests (`npx vitest run` in `packages/analytics`):
  envelope round-trip, consent machine/token, one negative fixture per
  lint gate, generator idempotency/determinism, client delivery semantics
  (batch bounds, ack-vs-fail retention, eviction order), breadcrumb bounds.

- **PR B0** Navdhya Signals implementation plan:
  `docs/analytics/signals-platform-plan.md` — phase-wise plan (B0–B9) with
  implementation tasks, evaluation criteria and acceptance tests per phase,
  derived from the v2.1 production architecture decision (GA4 + Firebase +
  GTM + edge collector + Google Cloud + Postgres truth).
- **PR B0** `docs/analytics/instrumentation-inventory.md` — repository-wide
  audit of every analytics/telemetry path with owner, destination and
  Signals disposition. Evidence for the PostHog removal below.
- **PR B0** `apps/web/.env.example` — Signals env contract
  (`NEXT_PUBLIC_GA4_MEASUREMENT_ID_*`, `NEXT_PUBLIC_GTM_CONTAINER_ID_*`,
  `NEXT_PUBLIC_SIGNALS_*`, server-only `SIGNALS_HMAC_SECRET` /
  `SIGNALS_CONSENT_TOKEN_SECRET`). No secrets; values are provisioned at B3.

- **PR 0.8** New `lib/security/rate-limit.ts` with
  `InProcessRateLimiter` (sliding fixed-window, per-call cost,
  max-keys GC, `toHeaders()` helper) and `clientIp()` helper.
  Consumed by `/api/revalidate` and ready for use by SMS, OTP,
  and auth routes in PR 1.
- **PR 0.8** New `lib/auth/cookie-names.ts` as the single source
  of truth for auth cookie names (`DEV_ROLE_COOKIE`, `SESSION_COOKIE`,
  `CONSENT_COOKIE`, `ANALYTICS_OPT_IN_COOKIE`,
  `EXPERIMENT_COHORT_COOKIE`, `LAYOUT_SEED_COOKIE`) plus
  `VALID_DEV_ROLES` and `isValidDevRole` type guard. The dev-role
  cookie string was previously duplicated in 3 files; changing it
  in one place silently broke the others. All 3 callers updated.
- **PR 0.8** `OtpInput` no longer double-fires `onComplete` on
  every keystroke. The original `useEffect` had `onComplete` in its
  dep array AND ran on every value change, so a single OTP entry
  produced multiple submissions (and every parent re-render with a
  new callback identity re-triggered it). New version uses a ref to
  track the last value for which we already called `onComplete`;
  fires exactly once per distinct value. Closes F-FE-09 sibling
  P1 (auth UI double-submit risk).
- **PR 0.8** `AuthSplitLayout` body scroll lock is now
  reference-counted. The old in-place mutation leaked when two
  layouts mounted concurrently: the inner one's unmount restored
  the wrong value, releasing the outer one's lock and leaving the
  page scrollable while a layout was still open. New code lives in
  `lib/security/scroll-lock.ts` and is safe to call from any number
  of overlapping layouts/modals.
- **PR 0.8** `getMockSession` verification status is now
  role-realistic. Old code marked `Pandit` as `"verified"` and
  everything else as `"pending"` (inverse of reality). New code:
  `User` / `Manager` / `Admin` / `SuperAdmin` = `"verified"`,
  `Vendor` = `"pending"` (business-doc review), `Pandit` =
  `"pending"` for new mocks and `"verified"` for the three
  pre-approved pandits (`mock-pandit`, `mock-pandit-1`,
  `mock-pandit-2`).
- **PR 0.8** `app/find-pandit/client.tsx` and `app/pandits/client.tsx`
  no longer generate their JSON-LD schemas in client code. The
  duplicate `seoManager.generateSchema` + `dangerouslySetInnerHTML`
  injection is gone from both client files; the schema is now
  rendered once by the server-side `page.tsx` and ships in the
  initial HTML. Removes wasted client JS, eliminates a
  `dangerouslySetInnerHTML` surface, and lets Next.js static-
  optimise the schema. Closes P1 audit finding.
- **PR 0.8** `lib/dlo-x/cache.ts` is now a bounded LRU
  (max 10,000 entries) with proper TTL. Old code was an unbounded
  `Map<string, { value, expiry }>` — at 5K RPS with 1KB entries
  per value, memory grew at ~5MB/sec (~18GB/hour). Also reachable
  by an attacker sending random `?bust=…` keys. LRU evicts the
  least-recently-used entry on every insert. Closes P0 memory leak.
- **PR 0.8** `lib/dlo-x/layout-resolver.ts` `matchesRoutePattern`
  now requires a `/` boundary after a wildcard prefix. Old
  `startsWith(prefix)` matched `/pandits` against `/pandit/*` (the
  public directory was being resolved to the dashboard policy).
  Now requires either exact prefix match or `prefix/...`.
- **PR 0.8** `lib/dlo-x/layout-policies.ts` now registers an
  explicit `find-pandit-page` policy (was falling through to the
  `*` wildcard). Closes consistency gap with `/pandits` and
  `/pujas/*` which were already explicit.
- **PR 0.8** New `lib/security/csrf.ts` with double-submit-cookie
  CSRF protection. `issueCsrfToken()` returns a HMAC-signed,
  time-bounded token; `validateCsrfToken(cookie, header)` checks
  match + signature + expiry; `extractCsrfToken(request)` pulls
  the values from a Next.js request. Fails closed in production
  if `CSRF_SECRET` is unset (throws on first call). Closes
  C-RISK-01 (no CSRF on state-changing endpoints).
- **PR 0.8** `app/api/revalidate/route.ts` and
  `app/api/ads/decision/route.ts` now require a valid CSRF token
  on every state-changing request. `ads/decision` is read-only
  but writes audit logs, so we treat it as state-changing. The
  check is gated on `process.env.NODE_ENV === 'production' || CSRF_SECRET`
  so dev tooling (Vercel preview, `curl`, etc.) still works
  without a secret. `revalidate` uses `extractCsrfToken` against
  `NextRequest.cookies`; `ads/decision` parses the `Cookie` header
  directly to avoid loading `next/server` (which transitively
  imports a fetch global that breaks in some Jest environments).
- **PR 0.8** `lib/dlo-x/cache.ts` is now a bounded LRU
  (max 10,000 entries) with proper TTL. Old code was an unbounded
  `Map<string, { value, expiry }>` — at 5K RPS with 1KB entries
  per value, memory grew at ~5MB/sec (~18GB/hour). Also reachable
  by an attacker sending random `?bust=…` keys. LRU evicts the
  least-recently-used entry on every insert. Closes P0 memory leak.
- **PR 0.8** `lib/dlo-x/layout-resolver.ts` `matchesRoutePattern`
  now requires a `/` boundary after a wildcard prefix. Old
  `startsWith(prefix)` matched `/pandits` against `/pandit/*` (the
  public directory was being resolved to the dashboard policy).
  Now requires either exact prefix match or `prefix/...`.
- **PR 0.8** `lib/dlo-x/layout-policies.ts` now registers an
  explicit `find-pandit-page` policy (was falling through to the
  `*` wildcard). Closes consistency gap with `/pandits` and
  `/pujas/*` which were already explicit.
- **PR 0.8** New `lib/security/sanitize.ts` allow-list sanitizer
  (`sanitizePlainText`, `sanitizeMarkdown`, `sanitizeUrl`) with
  character-allow-list, control-char / zero-width stripping, scheme
  allow-list for URLs, and rejection of `javascript:` / `vbscript:` /
  `data:` (except `data:image/*` when explicitly allowed). 24/24
  sanitizer tests pass. Closes P-ARCH-05 (P0).
- **PR 0.8** `removePII` is now wired into `lib/dlo-x/telemetry.ts`
  `TelemetryService.track()`, redacting both field-names and value
  patterns (email, phone, Aadhaar, PAN, card, secrets) recursively.
  Returns a `RedactionLog` so callers can audit what was stripped.
  Closes P-ARCH-12 (P1).
- **PR 0.8** 177 new tests across `lib/security/__tests__/security.test.ts`
  (100: 10 RBAC + 7 tenant + 8 PII + 8 plain + 6 markdown + 12 URL + 9 rate-limit
  + 4 clientIp + 11 ab-testing + 5 cookie-names + 7 scroll-lock + 13 CSRF) +
  `lib/dlo-x/__tests__/cache.test.ts` (15) +
  `lib/dlo-x/__tests__/layout-policies.test.ts` (9) +
  `lib/pandits/__tests__/data.test.ts` (12) +
  `components/dashboard/__tests__/StatusBadge.test.tsx` (19) +
  `components/auth/__tests__/OtpInput.test.tsx` (7) +
  `app/api/ads/__tests__/csrf.test.ts` (4 new CSRF integration tests
  on the wired route). The previously broken
  `app/api/ads/__tests__/routes.test.ts` was fixed by switching
  it to `@jest-environment node` (jsdom does not expose the Web
  Fetch API globals that `next/server` references at module load).
  Full suite: lib/{dlo-x,security,pandits} + components/{dashboard,auth}
  + app/api/{ads,revalidate} green.
- **PR 0.55** i18n scaffold: `i18n.ts` config, `lib/i18n/locale.ts` (path
  resolution, prefix helpers), `lib/i18n/messages.ts` (server-side JSON
  loader with cache), `lib/i18n/formatters.ts` (Intl-based, INR default),
  `lib/i18n/translate.ts` (DeepL + Claude clients, no-op passthrough when
  keys are absent), `messages/en.json` + `messages/hi.json` (62 keys in
  sync), `scripts/translate.ts` (CLI), `scripts/check-bundle-parity.ts`
  (CI gate), `docs/i18n.md`. 42/42 i18n tests pass.

### Removed
- **PR B1** Root `package.json` `posthog-js` dependency — repo-wide verified
  (B0 audit): nothing imports it; the only consumer was the deleted stub.
- **PR B0** Dead PostHog instrumentation (superseded by the Signals platform,
  decision record in the plan doc §0.1): deleted
  `apps/web/components/analytics/PostHogProvider.tsx` (stub from PR 0.25 —
  audited repo-wide first: zero imports of `initPostHog` / `trackEvent` /
  `identifyUser` / `PostHogPageview` anywhere; never mounted in a layout;
  `NEXT_PUBLIC_POSTHOG_KEY` never set), removed `posthog-js` from
  `apps/web/package.json` (only consumer was the stub's dynamic import), and
  removed `https://*.posthog.com` from the CSP `connect-src` in
  `apps/web/next.config.ts` (no remaining code can reach it). CSP
  `graph.facebook.com` retained — active ad-adapter egress, inventoried in
  the plan doc's instrumentation inventory. `@vercel/analytics`: audit-04june
  reference to `layout.tsx:4` confirmed stale (no import exists).

### Security
- **PR 0.8** Replaced DJB2-like `deterministicHash` in
  `lib/dlo-x/ab-testing.ts` with SHA-256 + rejection-sampled
  modulo. Old code had visible modulo bias at our traffic levels
  and let neighbouring user IDs collide into the same A/B bucket.
  New code distributes 5,000 inputs uniformly (max bucket
  < 3× expected, no empty buckets). Added `pickVariant` helper
  with optional integer weights. Closes P-ARCH follow-up.
- **PR 0.8** `/api/revalidate` now charges rate-limit tokens by
  slug count (was 1 token per request). A single request with
  100K slugs previously bypassed the 30 req/min limit and could
  DoS the registry. Hard caps: 100 slugs per request, 200 chars
  per slug, regex `/^[a-z0-9](?:[a-z0-9-]{0,198}[a-z0-9])?$/i`,
  no leading slashes / backslashes / `..` / `//` / `\\` / control
  chars. Invalid slugs get a 200 with status: 'invalid'.
- **PR 0.8** `/api/ads/decision` now validates the body with
  zod (`DecisionRequestEnvelope`): caps `opportunities` to 20,
  enforces `requestId` + `opportunities[].slotId` shape, rejects
  bodies > 64 KB with 413, returns the first 5 zod issues on 400.
  No more `payload as DecisionRequest` unsafe cast.
- **PR 0.8** `hasPermission` no longer lets an unknown role claim
  SuperAdmin access. Old code defaulted both unknowns to `0`, so
  `0 >= 0` was `true` — any caller could ask for `'SuperAdmin'`
  permissions and get them. New code requires the typed `Role` and
  returns `false` for any unknown value. SuperAdmin is now in the
  hierarchy. Audit P-ARCH-26 follow-up.
- **PR 0.8** `validateTenant` no longer treats the `'default'` tenant
  as a global read-all key. Old code returned `true` whenever
  `userTenant === 'default'` regardless of `resourceTenant`, so a
  default-tenant user could read every other tenant's data. New code
  does a strict equality check, plus an explicit `GLOBAL_TENANTS`
  allow-list (`global` / `public` / `system`) for resources that are
  intentionally shared. Audit P-ARCH-04 (P0).
- **PR 0.25** Removed dead `sanitizeInput` and `sanitizeHeaders` from
  `lib/dlo-x/security.ts` — they were unused and over-escaped `/` which
  would have corrupted JSON-LD. Audit P-ARCH-17.
- **PR 0.25** Replaced non-deterministic per-request `crypto.randomUUID()`
  seed in `proxy.ts` with sticky `deriveStableSeed()` (cookie > daily IP+UA
  hash). Fixes A/B test bucketing and layout-cache hit-rate.
  Audit P-ARCH-03 / P-ARCH-11 (P1).
- **PR 0.25** Added `.gitleaks.toml` and `.githooks/pre-commit` for secret
  scanning (Razorpay keys, AWS keys, Indian PAN/Aadhaar, UIDP JWTs).
  Mocks + audit docs whitelisted.
- **PR 0.25** Removed `@vercel/analytics` (replaced with PostHog swap stub)
  and `next-themes` / `sonner` (unused). Audit F-FE-19, C-COST-01.

### Changed
- **PR 0.25** Removed 20 unused `@radix-ui/*` packages (kept 7 in active
  use). Audit P-ARCH-21 / C-COST-01.
- **PR 0.25** Removed auto-running `postinstall` hook (was re-running
  `scripts/postbuild.mjs` on every `npm install`). Build pipeline still
  calls it after `next build`. Audit C-RISK-05.
- **PR 0.25** `next.config.ts` CSP updated: removed Vercel telemetry URLs,
  added `https://*.posthog.com` (analytics) and `https://graph.facebook.com`
  (WhatsApp Business API, PR 11.5). Audit Q3 / Q6.
- **PR 0.25** `next.config.ts` `images.remotePatterns`: activated AWS S3
  mirror (`navdhya-assets.s3.ap-south-1.amazonaws.com`) and CloudFront
  alias. Audit Q7.

### Added
- **PR 0.5** AEO Speakable + KeyFacts + TLDR + ClaimReview + AuthorEntity
  JSON-LD on all 1,265 SEO pages.
- **PR 0.5** `/llms.txt` and `/llms-full.txt` markdown exports for AI
  search engines (GPTBot, ClaudeBot, Perplexity, etc.).
- **PR 0.5** 17 AI bot user-agents in `lib/seo/robots.ts` with explicit
  allow/deny for sensitive paths.
- **PR 0.25** `lib/dlo-x/stable-seed.ts` — deterministic seed derivation
  with 8 unit tests.
- **PR 0.25** `lib/infra/dns.ts` — Route 53 record source-of-truth
  document for `navdhya.com`.
- **PR 0.25** `components/analytics/PostHogProvider.tsx` — PostHog
  swap stub. Real wiring in PR 11.5.
- **PR 0.25** `.github/CODEOWNERS` — review-routing rules.
- **PR 0.25** npm scripts: `secrets:scan`, `secrets:scan:all`,
  `secrets:baseline`, `secrets:check`.

## [0.1.0] - 2026-05-15

### Added
- Initial scaffold (Next.js 16, React 19, TypeScript 5, Tailwind 4).
- DLO-X layout orchestration (10 shells, route policies).
- SEO platform: 1,265 pages across 14 content clusters, JSON-LD
  generator, sitemap, robots.
- 6 role-based dashboards (User / Pandit / Vendor / Manager / Admin /
  SuperAdmin).
- Mock auth + DevRoleSwitcher for local development.
