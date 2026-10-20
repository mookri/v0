# Navdhya Signals — Implementation Plan (v2.1)

Status: **Approved for implementation — starting at B0.**
Architecture: see the v2.1 production architecture decision (GA4 + Firebase + GTM + edge collector + Google Cloud + Postgres truth). This document is the execution plan derived from it: phase-wise scope, file-level implementation tasks, evaluation criteria, and acceptance tests per phase.

---

## 0. Governing decisions

### 0.1 Closed architecture decisions

| # | Decision | Call |
|---|----------|------|
| 1 | Stream processing | Cloud Run consumers first; Dataflow only when benchmarks prove stateful/windowed processing is required. |
| 2 | GTM server-side tagging | **Not** in the initial collector. GTM Web container only. A separate server container is a later, marketing-justified decision. The custom edge collector is **not** a GTM server container. |
| 3 | PostHog removal | Remove the inactive stub, its dependency and its CSP allowance after repository-wide verification (audit completed — evidence in `instrumentation-inventory.md`). Shadow-test the new pipeline against the **active** baseline (ad-network adapters, ad beacons, DLO-X telemetry, search analytics), not the removed stub. |
| 4 | Cost governance | Model costs first. Provisional **₹25,000/month** incremental analytics-infrastructure design-review threshold. Cost alerts reduce *optional* workload first; they never shut down the booking/payment event pipeline. |

### 0.2 Plan-level decisions (resolved at planning time)

| # | Decision | Call | Rationale |
|---|----------|------|-----------|
| 5 | Edge collector hosting | **Interim: Vercel Edge Function** at `/api/ae/v1/batch` behind a thin collector adapter interface; **Cloudflare Worker (B3b)** is a swappable implementation of the same contract. | App deploys on Vercel. Avoids new DNS/TLS topology (Cloudflare proxy in front of Vercel origin) during the contract-proving phase. The adapter makes the later swap a non-event. |
| 6 | Worker→GCP authentication | **HMAC with rotated secret + timestamp + nonce replay window**, verified at the ingestion service. GCP Workload Identity Federation is **not** available to Cloudflare Workers; the "prefer WIF" branch is unreachable from the edge. | §3.4 of the architecture; stated explicitly so no one attempts the impossible option. |
| 7 | Consent mechanism | **Server-side consent record in Postgres** (session/user keyed, auditable, survives cookie clearing) + **HMAC consent token attached per batch** by the SDK, verified at the edge. GTM and the collector read the **same** record. | A cleared cookie must not resurrect collection. Single source prevents GTM/collector drift. Reuses the actor-token pattern (`tooling/scripts/mint-actor-token.ts`). |
| 8 | Registry CI home | Registry lint runs as a `turbo test` task in `packages/analytics` (no GitHub Actions workflows exist in this repo; tests run via turbo/vitest/jest today). | Matches current automation surface; workflow files can be added later without moving the lint. |
| 9 | Workspace glob | Change `pnpm-workspace.yaml` `services/*` → `services/**` **before** creating nested service packages. | The v2.1 nested layout (`services/functions/ingestion`, …) is invisible to the current one-level glob — silent build-breaker. |
| 10 | Initial event set | ~15 funnel-critical events enter the registry at B1; the ad-beacon family enters as `internal_only` (governed later as GTM tags at B4, **not** removed). | Right-sized for contract proving; preserves the active baseline for shadow comparison. |

### 0.3 Non-negotiable rules (from the architecture)

1. **Postgres + transactional outbox is business truth.** GA4 and BigQuery are projections. GA4 never authorizes, never initiates critical workflows, never confirms payment/booking success. A GA4 `2xx` means receipt, not processing.
2. **One canonical owner per event; explicit sink policy per event** (`gtm_ga4 | collector_bigquery | server_ga4 | internal_only`). No event may reach the same reporting destination through two paths. No reporting-time dedup as a fix for instrumentation design.
3. **Server-confirmed and browser-observed are different events** (`booking_confirmed` vs `booking_confirmation_viewed`), never two copies of one event.
4. **P0 (domain truth) is a backend reliability category** — transaction + outbox + reconciliation. Not a browser retry tier.
5. **No PII to GA4 or the analytics stream**: no raw booking IDs, names, emails, phones, tokens, free-text user input, raw stack traces. Identifiers are HMAC-hashed; hashes are *pseudonymous*, not anonymous.
6. **Client-delivery metrics are measured over eligible attempted and acknowledged events** — never over "all events ever generated in the browser."
7. **Secrets/credentials/Cloudflare-specific code never enter portable client packages.**

---

## 1. Global targets (proposed engineering targets, verified at B9)

| Dimension | Target |
|---|---|
| Design volume | 10⁸ events/day (≈1,157/s avg; 5,000/s expected peak; 10,000/s load-test headroom) |
| Collector availability | 99.9% monthly |
| Domain event integrity | No unexplained outbox→warehouse loss; every gap alerted or accounted |
| Action latency (async rules) | p95 < 60s, p99 < 5m |
| First-party warehouse freshness | < 15 min (load-test verified) |
| GA4 export freshness | Monitored separately; not a hard real-time guarantee |
| Error triage | p95 < 15 min from first qualifying occurrence to alert |

Load tests must include retries, duplicate delivery, downstream failure, queue backlog and recovery — not just clean ingestion.

---

## 2. Phases

Each phase: **Scope → Implementation → Evaluation criteria → Acceptance tests**. A phase is done when its acceptance tests pass and its exit criteria are evidenced in the PR description.

### B0 — Baseline, environment, removal

**Scope.** Establish the ground truth and clean cut the dead instrumentation. No new pipeline code.

**Implementation**

1. GA4 property + 4 registered web apps (`web`, `customer`, `partner`, `dashboard`), BigQuery export linked; GTM **web container** created (web surface only — privileged partner/admin surfaces get no marketing tags). *(External console setup; recorded in the PR with IDs.)*
2. Env contract documented in `apps/web/.env.example`:
   - `NEXT_PUBLIC_GA4_MEASUREMENT_ID_<SURFACE>`, `NEXT_PUBLIC_GTM_CONTAINER_ID_<SURFACE>`
   - `NEXT_PUBLIC_SIGNALS_COLLECTOR_URL`, `NEXT_PUBLIC_SIGNALS_ENABLED` (default off)
   - `SIGNALS_HMAC_SECRET`, `SIGNALS_CONSENT_TOKEN_SECRET` (server-only)
3. Consent policy decision record in `CHANGELOG.md` (strict-denial vs consent-mode-ping — pick one explicitly; see architecture §5.2) and consent storage design per decision #7.
4. **Remove dead PostHog instrumentation** (audit evidence: zero imports of the 4 exported symbols anywhere in `apps/`; no `@vercel/analytics` imports; env vars read but never set):
   - Delete `apps/web/components/analytics/PostHogProvider.tsx`
   - Remove `posthog-js` from `apps/web/package.json`
   - Remove `https://*.posthog.com` from `connect-src` in `apps/web/next.config.ts`
5. Write `docs/analytics/instrumentation-inventory.md`: every active instrumentation path (ad-network adapters, `/api/ads/track` + `/api/ads/track-beacon`, DLO-X telemetry, search analytics → Laravel, `TempleAnalyticsView`) with owner, destination, and future GTM/registry disposition.
6. Service-worker check: `apps/web/public/sw.js` is network-only with 5s timeout → 503 on failure; `sendBeacon` bypasses SW entirely. Documented in the inventory as compatible with the collector SDK (503 is retryable).
7. **Known debt — lockfile refresh (follow-up, human-reviewed):** `posthog-js` was removed from root and `apps/web` `package.json`, but `pnpm-lock.yaml` was intentionally NOT regenerated here: a regeneration re-resolves every `specifier: "latest"` dependency (Radix 1.2.x → 1.3.0, etc.) and adds workspace importers the lock is already missing (`apps/dashboard` was absent — the lock was stale pre-B0). Those version bumps deserve their own review. Run `pnpm install` deliberately (separate PR) to sync; frozen installs are already affected by the pre-existing staleness. **Repaid after B9:** the refresh ran as a side effect of `pnpm add firebase` (S50 kit) and is committed separately as `chore: refresh pnpm-lock.yaml` — it drops `posthog-js`, adds the missing `apps/dashboard` and `services/**` + `wrangler` importers, adds `firebase@13`, and re-resolves the `latest` specs (Radix 1.2.x → 1.3.x, etc.); the re-resolved bumps ride with that commit for review rather than being absorbed silently.

8. **Known debt — pre-existing failures outside Signals (baseline, not chased):** `tests/architecture/customer-features.test.ts` fails on committed `apps/customer/presentation/bookings/BookingDetailPage.tsx` (presentation calls `canCancel`); bare root `tsc --noEmit` fails on `apps/customer` / `drafts/` / `packages/design-system` (root config has no `jsx`/`@/`) and on `tests/{partner,commerce,identity,resource-management}` type drift; `packages/payment` and `packages/booking` `npm test` exit 1 because they have no colocated tests; the 9 apps/web jest suites listed at B0 still fail. All of these files pre-date the Signals work. Signals gates: per-package `npx tsc --noEmit`, the `tests/` + package vitest suites, `registry:check`, and `domain-check` (all green at B2).

9. **Known debt — new workspace packages are absent from `pnpm-lock.yaml`:** `services/functions/outbox-publisher`, `services/functions/ingestion` and `services/workers/analytics-collector` are uninstalled workspaces — their tests resolve sources through vitest aliases (or relative imports) and typecheck through tsconfig paths, no install required. `wrangler` is declared (not installed) for the B3 worker; the lockfile refresh (item 7) must include all of them. **Repaid:** the same refresh commit now carries importers for all three workspaces and `wrangler@3.114.17`.

**Evaluation criteria**

- No unexplained existing instrumentation paths: every analytics-touching file in the repo is listed in the inventory with an owner and a plan.
- No PostHog/Vercel-analytics artifact remains except history.
- Consent posture is an explicit, recorded decision — not an implementation accident.

**Acceptance tests**

- `rg -i posthog` returns matches only in `docs/` and `CHANGELOG.md` (history), none in `apps/`, `packages/`, `services/`.
- `rg '@vercel/analytics'` returns nothing.
- CSP no longer contains `posthog`; CSP still contains `graph.facebook.com` (active baseline, inventoried).
- `apps/web` typecheck (`tsc --noEmit`) and jest suite pass.
- `instrumentation-inventory.md` exists and covers all files matched by `rg -l 'track|beacon|telemetry|analytics' apps/ packages/ infrastructure/` that emit or forward events.

---

### B1 — Contracts, registry, client SDK skeleton

**Scope.** The canonical event contract, the registry-as-code with generators and lint gates, and the client SDK skeleton. No network delivery yet.

**Implementation**

1. Restructure `packages/analytics/`:
   - `contracts/envelope.ts` — `SignalEnvelope` with **required** `session.seq` (per-session monotonic counter; denominator for the client-delivery metric), `id` (ULID, generated once, preserved end-to-end), `version`, `consent`, `context.routeTemplate` (no raw URLs with query strings), allowlisted `params`.
   - `contracts/consent.ts` — consent state machine (`UNKNOWN → DENIED | GRANTED`, separate `analytics`/`ads` purposes) and consent-token format.
   - `contracts/delivery-policy.ts` — sink enum + per-event `allowedSinks`.
   - `registry/events/*.ts` — ~15 initial events, each with: `name`, `version`, `owner`, `producer`, `surfaces`, `allowedSinks`, typed allowlisted `params`, `privacyClass`, `retentionClass`, `samplingPolicy`, `priority` (P0–P3).
   - Initial set: `page_view`, `view_item`, `search`, `zero_results_search`, `checkout_started`, `begin_checkout`, `purchase_viewed` (browser), `booking_confirmed` (server/P0), `payment_succeeded` (server/P0), `payment_failed` (server/P0), `sign_up`, `login`, `kyc_submitted`, `app_error`; ad-beacon family as `internal_only`.
   - `registry/generate` — emits TypeScript types, JSON schemas, the edge-validator module, test fixtures, BigQuery column docs. Generated output is committed; CI regenerates and fails on drift.
   - `registry/lint` — turbo test task (decision #8). Gates: unknown event names; schema-incompatible version bumps (no remove/rename, additive-optional only); forbidden param types; high-cardinality params without explicit review flag; PII-pattern-bearing fixtures; >500 registered names; >25 params/event; missing delivery policy.
   - `client/` — `analytics-client.ts` (§4.2 interface: `track(name, params)`, `flush()`), `indexeddb-outbox.ts` (persist-before-delivery), `batcher.ts` (bounded size/latency), `retry-policy.ts` (exponential backoff + jitter), `breadcrumbs.ts` (bounded ring buffer). No-op-safe without keys.

**Evaluation criteria**

- Schemas compile; every generated artifact is reproducible from the registry.
- Each lint gate is proven by a negative-test fixture that fails.
- The SDK cannot emit an event the registry does not know (typed at compile time, validated at runtime).

**Acceptance tests**

- Generator idempotency: regenerate → zero diff.
- Five+ negative fixtures, one per gate class, each asserting a non-zero lint exit with the expected diagnostic.
- Envelope round-trip: construct → validate → serialize → validate.
- Client SDK unit tests: offline persistence, batch bounds, eviction order (P3 → P2 → P1 shed first; P0 is never in the browser queue), breadcrumb ring-buffer bounds.
- `packages/analytics` typechecks standalone.

---

### B2 — The truth path (split B2a/B2b if review size demands)

**Scope.** Domain truth → outbox → Pub/Sub → durable ingestion → `signals_raw`. This is the primary integrity contract.

**Implementation**

1. **`PostgresOutboxStore`** (`infrastructure/messaging/outbox/PostgresOutboxStore.ts`) against an **extended** `OutboxStore`: records gain `claimed_at`, `lease_expires_at`, `attempts`, `dead_lettered`; `claim()` uses `FOR UPDATE SKIP LOCKED` with lease renewal. Migration creates the `outbox` table. `MemoryOutboxStore` remains for tests via the interface.
2. **Server emitters**: append `booking_confirmed` / `payment_succeeded` / `payment_failed` outbox records **inside the same Postgres transaction** as the business row — at `infrastructure/payments` gateway results (`RazorpayPaymentGateway`, `NoopPaymentGateway`) and booking state transitions. Browser death cannot lose P0.
3. **`services/functions/outbox-publisher`**: claims, publishes to Pub/Sub with the stable domain event ID as dedup key, acks only after Pub/Sub acceptance; lease expiry → redelivery; `attempts` ceiling → dead-letter topic + alert.
4. **`services/functions/ingestion`**: validates envelopes against generated schemas (fail-closed on unknown events), stamps `receivedAt`, verifies Worker HMAC+timestamp+nonce (decision #6), publishes to `signals_raw` stream with envelope-ID dedup, returns durable-acceptance ack. This ack is the **only** success signal the collector may trust.
5. **`signals_raw` BigQuery sink**: streaming insert, envelope ID as dedupe key, enforced `source` column (`first_party|server|shadow|ga4_export`).
6. **Level-1 reconciliation queries** from day one: outbox committed vs. published vs. persisted; gap alerting per architecture §7.3.

**Recorded at implementation (B2 complete)**

- **Emission sites** are the application boundaries — `confirmCapture` (gateway-result, `packages/payment`) and `confirmBooking` (state transition, `packages/booking`) — not inside the gateway adapters, which lack business context and would re-emit on repeated verification calls. Architecture tests forbid capability → analytics imports, so the capabilities declare `PaymentSignalSink` / `BookingSignalSink` ports and `infrastructure/messaging/outbox/signal-sinks.ts` adapts them to the emitters, bound to `tx.outbox`; the sink is invoked **before control leaves the operation**, which is what makes emission transactional.
- **Caller-supplied facts:** the booking domain owns no pricing truth (items carry `priceQuoteId`), so `booking_confirmed` value/currency/service_category come from the caller; payment derives them from `amountMinor`/`currency`.
- **Reconciliation** lives at `packages/analytics/src/server/reconciliation/level1.{ts,sql}` — `workers/` has no import alias and the import-alias test forbids `../` (that test's debt was also repaid in B2: analytics intra-package imports → `@navdhya/analytics/...` aliases).
- **No live database:** a statement-level fake `DbClient` (BEGIN/COMMIT/ROLLBACK buffering) stands in for Postgres — SQL shape (`SKIP LOCKED`, parameter order), lease/attempt math and rollback semantics are asserted against it; the real driver stays gated on `DATABASE_URL` (B0 note).
- **Workspace glob fix (B3 item 1) landed in B2** because B2 creates the nested function packages (decision #9 says "before", so it moved here).

**Evaluation criteria**

- A crash at any point in the chain loses nothing and doubles nothing: every committed domain event is either persisted exactly once in `signals_raw` or is visible in the gap report.
- P0 emission is provably transactional with the business write.

**Acceptance tests**

- Kill Pub/Sub mid-run → replay completes; `signals_raw` has exactly one row per envelope ID (dedup verified by query).
- Lease-expiry redelivery: a claimed-but-unacked record is redelivered after lease expiry; side effects remain idempotent.
- Publisher killed mid-batch → no lost, no doubled events.
- Transactional guarantee: a rolled-back business transaction leaves **no** outbox record; a committed one leaves exactly one.
- Schema rejection: unknown event name at ingestion → rejected to dead-letter, never silently dropped, never inserted.
- Level-1 reconciliation report runs and detects an injected gap in a test fixture.

---

### B3 — Edge collector + client SDK live (shadow mode)

**Scope.** First-party collection path end-to-end, in shadow mode, compared against the B0 active baseline.

**Implementation**

1. ~~Workspace fix first: `pnpm-workspace.yaml` `services/*` → `services/**` (decision #9).~~ **Done in B2** — B2 created the nested service packages, so the fix moved there.
2. **Interim collector (B3a)**: Vercel Edge Function `apps/web/app/api/ae/v1/batch/route.ts` implementing the collector contract behind the adapter interface:
   - JSON only; 64 KiB body cap; ≤25 events/batch; bounded params;
   - generated validator embedded (not fetched at runtime); fail-closed on unknown events;
   - rate limits per session + IP; forbidden-property stripping (data minimization);
   - HMAC + timestamp + nonce to ingestion (decision #6); **503 on no durable ack** — never ack-on-scheduled-background-work;
   - stateless: no journey state, no cohort logic, no business workflows, no in-memory "delivery".
3. **Cloudflare Worker (B3b)**: `services/workers/analytics-collector/` (`wrangler.toml`) — same contract, same validator module, swapped in by configuration. `waitUntil()` is **not** treated as a durable queue.
4. **Client SDK completion**: `fetch`-with-ack primary path; `sendBeacon` for unload-only best-effort; consent token per batch (decision #7); priority tiers + eviction per §4.3; `source=shadow` while comparing.

**Recorded at implementation (B3 complete)**

- **One collector contract, two adapters.** The contract — 64 KiB cap, ≤25 events/batch, forbidden-property stripping, per-session/IP fixed-window rate limits, consent gate, delivery-policy gate, source provenance, status map — lives framework-neutral in `packages/analytics/src/collector/` (`pipeline.ts`, `contract.ts`, `rate-limit.ts`, `auth.ts`). `apps/web/app/api/ae/v1/batch/route.ts` (Vercel Edge, `runtime='edge'`) and `services/workers/analytics-collector/` (Cloudflare Worker, `wrangler.toml`) are thin adapters over the same `handleCollectorRequest` — swapping is configuration, per decision #5. The worker imports the package by relative path so wrangler's esbuild needs no alias plugin.
- **Status map (honesty encoded):** 413 oversize (declared Content-Length or actual bytes, never partial); 400 batch/schema rejection naming the offender, **all-or-nothing** — valid batchmates are reported (`deferred`) but never silently forwarded; 403 no consent evidence (missing/forged/expired/denied token — fail-closed, never forwarded); 429 per-IP/per-session with Retry-After; **200 only after ingestion's durable-acceptance ack**; 503 on network error, upstream 5xx, upstream 401 (secret mismatch = deployment problem, never a client ack) or unset `SIGNALS_*` secrets — retry-safe because replay is absorbed by insertId dedup.
- **Delivery-policy enforcement moved from B4 into the collector:** only registry events with `producer=client`, `allowedSinks ∋ collector_bigquery`, and a permitted surface may enter. Server P0 and `internal_only` ad beacons are rejected at the edge. B4 extends the same gates to the GTM/MP paths.
- **Wire format:** batch body `{ events, source, consentToken? }` — the consent token is in the **body** (not a header) so `sendBeacon` can carry it. Collector → ingestion keeps the decision-#6 header `x-signals-auth: t=,n=,v1=` (Web Crypto only — no `node:crypto` in collector code) plus `x-signals-source`; ingestion stamps per-request source (new handling in `handleIngestRequest`, invalid values → 400, absent → server default). Canonical `SignalSource` moved to `packages/analytics/src/contracts/signal-source.ts`; `raw-sink` re-exports.
- **Client SDK:** fetch-with-ack is primary (ack only on collector 200); `flushBeacon()` is unload-only best-effort — never acked (events stay queued; duplicates absorbed downstream), counted in `beaconBestEffort` and **never** in the delivery-SLO denominator; `getConsentToken()` provider per batch; `source` defaults to `shadow` (flip at B5); `metrics()`/`deliveryRatio()` implement acknowledged ÷ eligible-attempted; priority-shed events count into `metrics.shed`.
- **Load harness is in-process Node, not k6/autocannon** — no new dependency enters the lockfile (items 7/9 discipline). `tests/signals/helpers/collector-load.ts` drives collector → ingestion handler → sink with injected downstream-500 windows, duplicate sends, and backlog drain. Recorded on the dev machine: sustained 10k events + 2.5k burst → **8,201 eps** achieved, p50 2.1 ms / p95 6.7 ms (p99 spikes inside the injected fault window), 55 retries, 50 duplicate sends, sink rows **12,500/12,500** (zero loss, zero dup); fault-free burst → **10,940 eps**, p95 4.6 ms, 8,750/8,750. A deployed-staging repeat can point the same contract at a real URL later.
- **Rate limiting is per-edge-isolate** (documented limitation): isolates share no bucket state; the ingestion HMAC replay window and per-batch caps remain the hard backstop.
- **Shadow comparison report** is a pure function (`packages/analytics/src/server/shadow-report.ts`): counts per event/family, schema errors with samples, indicative latency per family (client-clock caveat noted in the report), honest `deltaPct: null` when the baseline never saw an event. Driver-free CLI `tooling/scripts/shadow-report.ts` (bq-exported JSON in, markdown out) — no warehouse credential in the repo, same posture as `DATABASE_URL`.
- **Secrets:** the route/worker fail closed with 503 when `SIGNALS_HMAC_SECRET` / `SIGNALS_CONSENT_TOKEN_SECRET` / `SIGNALS_INGEST_URL` are unset; `.env.example` gained `SIGNALS_INGEST_URL`. `NEXT_PUBLIC_SIGNALS_ENABLED` stays `false` until shadow validation passes.

**Evaluation criteria**

- Failure/retry behavior proven under load, not asserted.
- Client-delivery metric computed exactly as defined: acknowledged ÷ eligible-attempted.
- Shadow dataset comparable to the active baseline: counts, schema errors, latency per event family.

**Acceptance tests**

- Load harness (`tests/` — k6/autocannon script): 10k eps sustained + burst, **with** injected retries, duplicate batches, downstream 500s, queue backlog, and recovery to zero-lag; SLOs from §1 recorded as results.
- Oversize batch (61 KiB+, 26 events) → 413/400, no partial accept.
- Unknown event in batch → 400 naming the offender; valid events in the same batch are **not** silently accepted (all-or-nothing per batch, documented).
- Replay: identical batch re-POSTed (same envelope IDs) → accepted transport, zero duplicate rows in `signals_raw`.
- Consent-token rejection: invalid/expired token → 403, event not forwarded.
- Beacon path: unload delivery enqueued via `sendBeacon` is received but marked best-effort in metrics (not counted in the delivery SLO denominator).
- Shadow comparison report generated for a 24h staging window.

---

### B4 — GTM wiring and key-event ownership

**Scope.** GTM Web container live on `apps/web` with governed tag inventory; browser key events flow `dataLayer → Google tag → GA4`; zero double-firing with the collector and server MP path.

**Implementation**

- Documented `dataLayer` event convention; consent initialization before all tags; tag inventory (purpose, owner, permitted data, consent requirement, destination); Preview/Assistant verification; test vs. production publishing separation; no free-text payloads; no Custom HTML unless reviewed.
- Delivery-policy enforcement: `gtm_ga4` events fire only via GTM; `server_ga4` events only via MP (with valid client/session context); `collector_bigquery` events only via the collector. Confirmation pages must **not** re-emit server-owned `purchase` as `purchase_viewed` duplicates — `purchase_viewed` is its own event, owned by the browser.
- MP usage rules encoded in `packages/analytics/adapters/ga4-measurement-protocol.ts`: ≤25 events/request, ≤40-char names, ≤25 params, `2xx` = receipt only; payloads validated against Google's validation endpoint in CI.

**Evaluation criteria**

- Key-event parity: for a scripted booking journey, GA4 shows exactly one `purchase` (server), one `purchase_viewed` (browser), one `booking_confirmed` (server) — no duplicates, no gaps.
- Consent grant/deny changes tag behavior deterministically (verified in Preview).

**Acceptance tests**

- Tag Assistant scripted run (documented recording) for the booking journey; event counts asserted against the parity table.
- Consent matrix test: `analytics=denied` → no GA4 tags, collector 403s, no `signals_raw` rows; `granted` → all three paths as configured.
- CI validation of MP payloads against Google's validation endpoint for the fixture set.

**Recorded at implementation (B4 complete)**

- Path normalization: the MP adapter landed at `packages/analytics/src/adapters/ga4-measurement-protocol.ts` (the package's `src/` layout), with its twin `ga4-data-layer.ts` (governed `dataLayer` pusher + Consent Mode v2 builders + container-id/URL builder).
- **Delivery policy enforced in three places, one per destination**: `pushGtmEvent()` rejects non-`gtm_ga4`, non-`client`, non-`web` and undeclared events (including `purchase` — the confirmation-page double-fire guard); `buildMpBatch()` rejects non-`server_ga4`, non-consented, unanchored (`client_id` is resolved by the sending boundary, never invented) and unmapped events; the B3 collector gate already covers `collector_bigquery`.
- MP rules encoded: ≤25 events/request, wire name ≤40 chars, ≤25 params (incl. `session_id` + `engagement_time_msec` plumbing), `2xx` → `receipt` (a `MpOutcome` union — `"processed"` is unrepresentable), governed `MP_WIRE_NAME` mapping (`payment_succeeded → purchase`; coverage pinned per registered `server_ga4` event by test).
- GTM bootstrap: `apps/web/app/gtm-provider.ts` (createElement, no JSX — keeps the bare root `tsc` error-free on touched files) mounted in `app/layout.tsx`; Consent Mode defaults (`denied`, `wait_for_update: 500`) pushed **before** the external `gtm.js` loader; StrictMode double-mount guarded; `setGtmConsent()` seam for the future consent UI; renders nothing without `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB`. CSP extended (`script-src`/`frame-src` → googletagmanager, `connect-src` → google-analytics) — no inline bootstrap needed.
- Inventory doc: `docs/analytics/gtm-tag-inventory.md` — the eight `gtm_ga4` events with purpose/owner/permitted data/consent/destination, the never-in-container list, publishing separation and Preview runbook (console steps).
- Deviation on the last acceptance item: CI cannot reach Google — the **local spec validator** (`validateMpPayload`) runs on the fixture set in the test suites (network-free, same posture as every driver gate), and the **live** Google debug endpoint is driven by `tooling/scripts/validate-mp-endpoint.ts` (env-gated: `GA4_MEASUREMENT_ID` + `GA4_API_SECRET`).
- Tests: `packages/analytics/src/adapters/__tests__/` (2 suites, 13 tests: encoded MP rules, wire-name coverage, consent/client-context gates, dataLayer gates + `purchase` guard + consent shapes) and `tests/signals/key-event-parity.test.ts` + `tests/signals/consent-matrix.test.ts` (5 tests: parity table per destination, consent denied/granted matrix across SDK → collector → MP).
- Verification: root `tests/` **439/440** (only pre-existing `customer-features`); `tests/signals` 49/49; analytics package 85/85 + `tsc` 0; `apps/web` `tsc` 0; registry in sync; booking/payment domain-check OK; apps/web jest — the same 9 documented pre-existing failures, none new; bare root `tsc` adds **zero** errors in touched files (all B4 files root-tsc-clean; parity-test cast fixed).
- Console steps still user-only: create the GTM Web container + GA4 web stream, configure the eight inventory tags with `analytics_storage=granted` consent requirements, set `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB`, run the Tag Assistant recording (evidence for the parity criterion), and provide `GA4_MEASUREMENT_ID`/`GA4_API_SECRET` for the endpoint script.

---

### B5 — Warehouse staging, marts, reconciliation

**Scope.** BigQuery dataset layout, typed models, quality checks, Level-1 + Level-2 reconciliation, cost-bounded SQL.

**Implementation**

- Datasets: `signals_raw` (first-party + server, source-tagged), `signals_stg` (typed, validated, controlled identifier joins), `signals_mart` (funnels, retention, LTV, attribution, experiment readouts, data quality, operational metrics), `signals_reconciliation`; GA4 export stays in its own namespace and is transformed into reporting models.
- Partition by event date; cluster on verified query patterns; raw expiration per retention matrix; partition filters mandatory in production queries; scheduled, version-controlled SQL models with data-quality tests.
- **Level-1 (delivery integrity)**: domain events committed → published → persisted → downstream-acked; stable event IDs; replayable failures.
- **Level-2 (analytics parity)**: authoritative transactions vs. GA4-reported/exported measurements, accounting for consent, transport differences, duplicate suppression, reporting delays. A measurement comparison — never a replacement for Level-1.
- Nightly checks: missing expected domain events; duplicate IDs; unpublished beyond SLA; published-not-persisted; schema-invalid/quarantined records; booking/payment/refund count divergence; per-family volume swings; query cost and freshness regressions. Data-trust status generated from explicit checks + timestamps.

**Evaluation criteria**

- Every nightly check has a green path, a red path, and an owner alert.
- The data-trust badge means "specified checks passed at timestamp T" — never "GA4 is complete."

**Acceptance tests**

- Injected-failure suite per check (delete a row, duplicate an ID, delay a publish, corrupt a schema) → each check goes red with the expected diagnostic.
- Cost gate: a `SELECT *` without partition filter fails SQL lint; an estimated over-budget query fails.
- Level-2 parity report on a staging week: drift % computed, threshold breach alerts.

**Recorded at implementation (B5 complete)**

- Warehouse assets are version-controlled at `infrastructure/database/bigquery/` — `ddl/` (datasets `signals_raw`/`signals_stg`/`signals_mart`/`signals_reconciliation`, partitioned+clustered `events`/`quarantined`/reconciliation tables; GA4 export stays Google-managed with a typed `signals_ga4` view), `models/stg/` (`events`, `ga4_events`), `models/mart/` (all seven families: funnels, retention, ltv, attribution, experiment_readouts, data_quality, operational_metrics), `tests/` (3 data-quality tests — one returned row = failure), `table-sizes.json` + `README.md` (schedule + refresh runbook). Every model/test carries a partition filter — `npx tsx tooling/scripts/warehouse-lint.ts` scans all 12 files and passes (0 findings).
- **Partition plumbing gap closed**: `toRawRow` now emits `event_date` (derived from `occurred_at`) so streaming inserts land in real `event_date` partitions — without it the DDL's partitioning would silently collect `__UNPARTITIONED__` rows. Test pinned in `raw-sink.test.ts`.
- **Cost gate (acceptance item 2)**: `packages/analytics/src/warehouse/sql-lint.ts` — rules `missing-partition-filter` (event_date / `_PARTITIONDATE` / `_TABLE_SUFFIX` / `@since`+`@until` idioms), `select-star-unpartitioned` (the literal `SELECT *` rule), `over-budget` (static estimate = recorded table size × 1/`partitionDays` when filtered, whole table when not; default 5 GiB, per-file `-- @budget_bytes:N`). Recorded honestly: the estimate is a CI tripwire over `table-sizes.json`; **BigQuery `maximum_bytes_billed` remains the production bill gate**.
- **Nine nightly checks** (`packages/analytics/src/server/checks/`): missing expected domain events, duplicate event IDs, unpublished-beyond-SLA, published-not-persisted (delegates to Level-1), quarantined records, booking/payment/refund count divergence, per-family volume swing, freshness, query cost — each a pure function with a green path, a red path with concrete diagnostics, a registry-vocabulary owner, and a runbook-backed owner alert. Data-trust badge (`data-trust.ts`): green statement is exactly `Specified checks passed at T.` — test-pinned to never claim GA4/export completeness; red statement explicitly disclaims it.
- **Level-2** (`reconciliation/level2.ts`): authoritative IDs vs GA4 export rows with consent-adjusted expectation (`round(authoritative × fraction)`), duplicate suppression by `eventUniqueId`, reporting-lag day exclusion, warn/alert drift thresholds, owner alert on breach — measurement only; test proves Level-1 verdicts are untouched by Level-2 outcomes.
- CLIs: `tooling/scripts/warehouse-lint.ts` (CI gate) and `tooling/scripts/warehouse-nightly.ts` (exported-JSON in → markdown report + alerts + trust statement + optional `--level2`; exits red on failures; BOM-tolerant input for Windows exports). Both smoke-tested live: red night → exit 1 with 2 owner alerts; green night + parity → exit 0 with the exact trust statement.
- Retention baseline recorded in DDL/README: `raw_30d`=30, `raw_90d`=90, `audit_long`=400 days (partition ceiling 400; per-class pruning jobs land with the B7 retention matrix).
- Tests: package units — `warehouse/__tests__/sql-lint.test.ts` (8), `server/checks/__tests__/checks.test.ts` (11), `server/reconciliation/__tests__/level2.test.ts` (6); acceptance — `tests/signals/warehouse-nightly.test.ts` (12: baseline all-green + one-injected-failure-per-check + real-SQL-lint cost gate) and `tests/signals/level2-parity.test.ts` (3: staging week, breach alert, Level-1 independence).
- Verification: root `tests/` **454/455** (only pre-existing `customer-features`); `tests/signals` 64/64; analytics package 111/111 + `tsc` 0; ingestion package + `tsc` 0; warehouse-lint CLI 12/12 clean; registry in sync; booking/payment domain-check OK; bare root `tsc` zero errors in touched files.
- Console/driver-gated steps (user-only): create the GA4 BigQuery export link (property → BigQuery), apply `ddl/*.sql` with `bq` (replace `PROJECT`), schedule `models/**` + `tests/**` as scheduled queries (`@since`/`@until` bound to run windows), export nightly inputs (bq + psql) for `warehouse-nightly.ts`, refresh `table-sizes.json` on material growth.

---

### B6 — Rule engine, delayed actions, segments v1, anomaly detection

**Scope.** The small idempotent rule engine on Cloud Run + Pub/Sub + Cloud Tasks; six starter rules; basic explainable segments; per-family volume anomaly detection.

**Implementation**

- Rule contract: typed conditions, versioned rules, window semantics, consent requirements, stable action idempotency keys. Persisted per action: rule version, triggering event IDs, decision outcome, action request ID, final delivery status.
- Starter rules (from architecture §6.1): checkout abandonment (consent- and frequency-gated), booking confirmation workflow, payment-failure retry/support, zero-results search → catalog-gap report, KYC pending reminder, assignment-delay ops alert.
- Rules that determine *outcomes* use authoritative business state; analytics signals may gate eligibility to message, never declare that a booking/payment/refund happened.
- Segments v1: high-intent, checkout abandonment, first-time vs repeat, at-risk, catalog-gap searchers, approved city/acquisition cohorts. Slow-moving analytical cohorts in BigQuery; high-frequency state in Redis only when justified. **No** segment→Remote-Config mass synchronization.
- Anomaly detection: rolling baseline + robust z-score per event family; annotate affected window; page only when revenue-adjacent families are affected.
- Remote Config: approved client behavior/rollout flags only; backend validates every consequential action; no secrets; no trust in client-delivered config for authorization.

**Evaluation criteria**

- Retried rules never double-send (idempotency keys proven, not assumed).
- Every fired action is explainable a year later from persisted records.
- An injected 10× volume spike on a diagnostic family annotates without paging; on `payment_succeeded` it pages.

**Acceptance tests**

- Rule replay test: same triggering event IDs replayed → one action, second attempt detected as duplicate.
- Cancel-path test: `checkout_started` + `booking_confirmed` within window → no reminder.
- Consent/frequency guardrail tests: denial, cooldown, daily cap each suppress correctly.
- Idempotency: action request retried to FCM/outbox boundary → downstream effect occurs once.
- Anomaly fixtures: spike on `search` (annotate, no page) vs `payment_succeeded` (page).

**Recorded at implementation (B6 complete)**

- **Rule contract** (`packages/analytics/src/rules/contract.ts`): versioned `RuleDefinition`s (typed trigger/cancel events, window `delayMs`, `requiresConsent` on the envelope's `{analytics, ads}` pair, cooldown + daily cap, an `authoritativeCheck` LABEL resolved through an injected port — no business system is hard-wired into the package) and `RuleActionRecord` persisting everything the plan lists: rule version, triggering event IDs, decision, evidence, action request ID, final delivery status, attempts. The action request ID is a deterministic composite `rule@v:subject:sortedEventIds` — replaying the same triggering events yields the same key, which is how "second attempt detected as duplicate" is structural, not heuristic.
- **Engine** (`rules/engine.ts`): documented decision order — idempotency → consent → cooldown → daily cap → window/sweep → fire (authoritative check first when declared). Suppressed decisions are PERSISTED with evidence so "why didn't X fire?" is answerable a year later. An inconclusive authoritative check still fires and records why (never silently blocks on unknown state). Windowed records due but whose rule definition is gone are left untouched and reported `rule_missing`.
- **Dispatch boundary** (`rules/store.ts`): `dispatchAction` marks `delivered` after `send` and skips anything already delivered — the acceptance property "retried to the boundary → effect occurs once". Recorded honestly: a crash between successful `send` and `markDelivered` can re-send (at-least-once, same as B2's outbox); `attempts` makes that window visible.
- **Six starter rules** (`rules/starter-rules.ts`): checkout abandonment (ads-consent + 24h cooldown + cap 1 + 30-min window + authoritative `booking_exists` at due time + opportunistic `booking_confirmed` cancel), booking confirmation workflow (transactional follow-up), payment-failure retry/support (15-min cooldown, cap 3), zero-results → catalog-gap report (analytics consent), KYC pending reminder (24h window + authoritative `kyc_pending`), assignment-delay ops page (15-min SLA window + authoritative `assignment_missing`). Messaging consent beyond `{analytics, ads}` is B7's consent API — recorded, not forgotten.
- **Persistence**: migration `2026_10_13_000001_create_rule_actions.sql` (partial indexes for the fired-count and due-sweep paths + audit view) and `infrastructure/rules/PostgresRuleActionStore.ts`, statement-level tested against `tests/signals/helpers/fake-rule-actions-client.ts` (B2's fake-client posture).
- **Segments v1** (`segments/segments-v1.ts` + `models/mart/segments.sql`): eight explainable memberships (high-intent, checkout abandonment, first-time, repeat, at-risk, catalog-gap searcher, approved city/acquisition cohorts) — every membership carries its counts/fields as evidence; lifecycle segments read `lifetimeOrderCount` from authoritative business state. Recorded: NO segment→Remote-Config mass sync exists or is permitted; Redis not used in v1 (no latency-bound consumer earns it yet).
- **Anomaly detection** (`anomaly/robust-anomaly.ts`): rolling median/MAD baseline with documented fallbacks (MAD→mean-abs-dev→definitive-flat), |z| ≥ 3.5 default; revenue-adjacent families (`payment_succeeded`, `payment_failed`, `booking_confirmed`) PAGE, everything else ANNOTATES with the affected window + baseline numbers on the record.
- **Remote Config guardrails** (`flags/remote-config.ts`): allowlist of behavior/rollout flags only; secret-like names (secret/token/password/credential/api-key/private/endpoint/url) rejected BEFORE the allowlist; typed values with ranges/permitted sets enforced server-side. Client config is never trusted for authorization — backend boundaries re-validate consequential actions regardless of flags.
- Tests: package units — `rules/__tests__/engine.test.ts` (13), `anomaly/__tests__/robust-anomaly.test.ts` (6), `segments/__tests__/segments-v1.test.ts` (7), `flags/__tests__/remote-config.test.ts` (6); acceptance — `tests/signals/rule-engine.test.ts` (5: replay, cancel-path ×2 paths, guardrails, dispatch-once, explainability) + `tests/signals/anomaly-detection.test.ts` (4) + `tests/signals/postgres-rule-actions.test.ts` (7).
- Verification: root `tests/` **470/471** (only pre-existing `customer-features`); `tests/signals` 80/80; analytics package 143/143 + `tsc` 0; warehouse-lint 13/13; bare root `tsc` zero errors in touched files.
- Driver-gated (console/driver steps, user-only): real Postgres with the migration applied; FCM/Cloud Tasks senders wired behind the injected `send` boundary; Cloud Run deployment of the engine with Pub/Sub triggers + the due-time sweep on Cloud Scheduler.

---

### B7 — Privacy lifecycle, deletion, retention, key rotation

**Scope.** End-to-end deletion/suppression across every system holding signal data; retention enforcement; HMAC pepper rotation.

**Implementation**

- Versioned retention matrix covering: private collection stream, GA4 data, exported datasets, event audit records, experiment data, messaging identifiers, consent records.
- Deletion workflow: account deletion / consent withdrawal → identify affected systems → GA4 user-deletion API, `signals_*` erasure (hard delete where required — soft-delete views alone are insufficient), identity-mapping purge, cached projections, FCM/audience removal → verified completion recorded. 30-day SLA.
- HMAC pepper rotation (KMS-managed, overlap window) with identity-continuity tests.
- Erasure-aware access: queries over erased hashes return nothing; the erasure-token table is authoritative.
- Note: "DPDP-ready" is a **legal review outcome**, not an engineering label. This phase delivers the mechanisms; the review is a separate workstream with its own owner.

**Evaluation criteria**

- A deletion request verifiably removes or suppresses the subject's data in **every** system, with recorded proof per system.
- Rotation preserves identity continuity across the overlap and breaks it after expiry.

**Acceptance tests**

- End-to-end deletion fixture: seed a subject across raw/stg/mart/reconciliation/GA4-mock/audience → run workflow → assert absence/suppression everywhere + audit record complete.
- Raw-dataset erasure test (not just view exclusion).
- Rotation test: hash continuity within overlap; old-pepper-only identifiers unresolvable post-expiry.
- Consent-withdrawal event → blocked before fan-out within the SLO; previously granted destinations verified.

**Recorded at implementation (B7 complete)**

- Retention matrix v1.0.0 in code (`packages/analytics/src/privacy/retention-matrix.ts`) + `docs/analytics/retention-matrix.md`: all seven plan domains plus erasure tokens (indefinite — deleting one resurrects the subject on the next rebuild) and deletion audit records (3 years); registry `retentionClass` → days map with throw-on-unknown coverage; B5's "baseline pending B7" note is superseded.
- Canonical HMAC pseudonymisation lands with the phase that needs it: `PepperRing` (Web Crypto only, per package rule) mints self-describing `v{keyVersion}:{digest}` identifiers; exactly-one-active validation; resolution fails closed on unknown versions and digest mismatches; overlap expiry breaks old-pepper identifiers by design. KMS fetching stays outside the package — the ring takes already-loaded peppers.
- `DeletionWorkflow` fails closed on incomplete target sets (8 systems for account deletion, 3 for withdrawal): an unwired system is an error, never a silent partial erasure. Suppression (consent ledger + erasure token) is recorded BEFORE targets execute so racing fan-out is already blocked. Every target yields a timestamped, evidence-carrying proof; the 30-day SLA is enforced by `verify()` → `sla_breached`; partial failures stay visible (`partial_failure`, red exit).
- Dry-run mode: side-effect-free planning (`planned` proofs, `dry_run` audit status, no ledger/token writes) — a plan can never masquerade as an erasure, and dry-run records are never SLA-tracked.
- Erasure tokens are authoritative and never pruned; scope only upgrades `fanout_only` → `full`. `filterErased`/`excludeErased` implement erasure-aware access (queries over erased hashes return nothing).
- Consent withdrawal is purpose-scoped, first-withdrawal-wins, SLO `WITHDRAWAL_BLOCK_SLO_MS = 15 min`; withdrawal does NOT erase raw data (the retention matrix governs it) — the `fanout_only` token records exactly that split.
- Postgres: `2026_10_14_000001_create_privacy_tables.sql` (erasure_tokens, consent_withdrawals, deletion_audit + SLA index + audit-by-subject view). CLI `tooling/scripts/privacy-erasure.ts`: dry-run default; `--live` pre-flights every credential (missing env aborts before anything is touched), persists audit/tokens/ledger via psql (libpq env mapping keeps DATABASE_URL out of argv), runs BigQuery DELETEs under a `max_bytes_billed` cap, and calls the GA4 Data Deletion API (`properties.deleteUserdata`); subprocesses run shell-free with a cmd.exe fallback that refuses `"`/`%` arguments.
- Honest view semantics: `signals_stg`/`signals_mart` are views — the CLI records re-projection rather than a fake DELETE; deployments that materialise marts wire their own target (the acceptance fixture models that shape and proves per-system hard delete).
- Acceptance: `tests/signals/privacy-deletion.test.ts` (4: e2e absence + suppression everywhere with complete audit, raw storage-level hard delete not view exclusion, erasure-aware access, withdrawal blocked before fan-out within the SLO with previously granted destinations revoked) + `tests/signals/privacy-rotation.test.ts` (continuity → migration → hard-break scenario); package units 4 files (retention-matrix 5, pepper-rotation 5, deletion-workflow 8, erasure-access 3).
- "DPDP-ready" stays a legal-review outcome — this phase ships mechanisms only (honored in the `retention-matrix.ts` header note and the doc).

---

### B8 — Experiment ledger, exposure logging, error fingerprinting, release health

**Scope.** Minimum-credible experimentation first; crash/error analysis as its own subsystem (not a GA4 dumping ground).

**Implementation**

- **Phase-1 experimentation**: deterministic assignment service (salt-per-experiment; `deterministicHash` from `lib/dlo-x/ab-testing.ts` as primitive) with ledger in Postgres (`experiment, subject_id_hash, variant, allocation_version, assigned_at`); exposures logged only when the experience is actually displayed; pre-registered primary outcomes, eligible population, observation window, guardrails (cancellation, refunds, support contacts, reliability — not conversion alone); automated SRM checks; reproducible readouts from first-party data. Remote Config delivers configuration; the ledger is assignment authority.
- Advanced methods (CUPED, sequential testing, switchback) are **later capabilities**, gated on Phase-1 validity and data-quality evidence.
- **Crash subsystem** (first-party, restricted BigQuery datasets — not GA4 event params): fingerprinting from normalized error class + selected stack frames (build hashes and framework frames stripped; fixture-tested across multiple builds); release-scoped error-group counts, error-rate and affected-session trends; bounded breadcrumb ring buffer attached on error; adaptive sampling; strict payload redaction; post-release regression alerts → actionable alert, with auto-rollback **tested before enabled**. Crash-free-session metrics described by what the web instrumentation actually measures — never equated with native coverage.

**Evaluation criteria**

- An experiment readout is reproducible from the ledger + first-party data alone.
- SRM detection catches a rigged fixture.
- Fingerprinter groups the same logical failure across two builds into one group and separates two distinct failures.

**Acceptance tests**

- Assignment determinism + ledger round-trip; exposure-without-display produces no exposure row.
- SRM fixture (skewed allocation) → flagged invalid automatically.
- Fingerprint fixtures: cross-build stability (one group), distinct-failure separation, framework-frame stripping, breadcrumb redaction (PII patterns stripped).
- Canary: injected crash-rate spike on a release → alert fires; rollback hook dry-run verified.

**Recorded at implementation (B8 complete)**

- **Phase-1 experimentation** landed as `packages/analytics/src/experiments/`:
  - `deterministic.ts` replicates the app primitive's algorithm (`deterministicHash` from `apps/web/lib/dlo-x/ab-testing.ts` is node:crypto; this package is Web Crypto only) — a test pins bit-for-bit parity against a local reference implementation of the documented algorithm (a parent-directory import is forbidden by the import-alias gate, so app-side algorithm drift must be caught by updating that reference). Key derivation adds the plan-mandated per-experiment salt + allocation_version (bump ⇒ clean re-randomisation, no carry-over bias at new weights).
  - `contract.ts`: pre-registered definitions — primary outcomes, documented eligible population, observation window, integer weights, and ≥1 guardrail (validation REJECTS conversion-only experiments, per plan wording). `update()` must strictly bump allocationVersion.
  - `ledger.ts` + `2026_10_15_000001_create_experiment_tables.sql` + `PostgresExperimentLedgerStore`: assignments are immutable first-write-wins facts (the ledger is assignment authority — Remote Config may deliver configuration, never the decision); exposures are a separate first-write-wins record.
  - `service.ts`: `ensureAssigned` reads the ledger first (authority across processes); `logExposure` is the explicit display-only call and refuses unassigned subjects — assignment alone never produces an exposure row.
  - `srm.ts`: chi-square SRM over exposures vs planned allocation (incomplete-gamma p-value, no deps); p < 0.001 ⇒ flagged invalid automatically; zero-weight/unregistered-variant exposures flagged; <100 exposures recorded, never flagged.
  - `readout.ts`: reproducible pure function of ledger + first-party outcomes; outcomes counted per exposed subject inside the pre-registered window only; two-proportion z-test for the pre-registered primary; guardrail breaches flagged; SRM-invalid ⇒ readout invalid (numbers recorded, verdict distrusts them). CUPED/sequential/switchback explicitly deferred.
- **Crash subsystem** landed as `packages/analytics/src/crash/`:
  - `fingerprint.ts`: normalized error class + top-5 app frames, build hashes and framework/vendor frames stripped before hashing (128-bit FNV group key). Cross-build fixtures pin one-group stability and distinct-failure separation; payload bounded (30 frames).
  - `redact.ts`: strict PII redaction at capture (emails, phones, JWT/bearer/API keys, sensitive query values, Luhn-passing card runs — Luhn gating keeps ids/timestamps debuggable).
  - `breadcrumbs.ts`: bounded ring buffer, redacted at add(), drained once per error.
  - `sampling.ts`: adaptive probability `clamp(target/observed, floor, 1)` over a rolling window; downstream rates are sampling-corrected by the recorded probability.
  - `trends.ts`/`canary.ts`: release-scoped regression detection → actionable alert (diagnostics + runbook); crash-free rate carries the test-pinned web-only basis ("native app crashes are out of scope" — never equated). Auto-rollback default `disabled`; `dry_run` computes the decision WITHOUT invoking the hook (the tested-before-enabled trail); `enabled` requires a configured hook and refuses to claim rollback without one.
- Restricted BigQuery: `ddl/0003_crash_datasets.sql` (`signals_crash.errors`/`sessions`, 180-day partition expiry) + `models/mart/error_groups.sql` (release-scoped groups, affected sessions, web crash-free rate, partition-filtered). Retention matrix bumped to v1.1.0 with the `crash_events` domain.
- Acceptance: `tests/signals/experiment-ledger.test.ts` (5: app-primitive parity, determinism + ledger round-trip, exposure-without-display produces no row, rigged SRM fixture flagged automatically, reproducible readout) + `tests/signals/crash-release.test.ts` (2: fingerprint fixtures incl. framework stripping + breadcrumb redaction; canary spike alert + dry-run rollback verified) + `tests/signals/postgres-experiment-ledger.test.ts` (3: first-write-wins round-trips, single exposure per subject, service over PG store via fake client). Package units 6 files (assignment 5, srm 5, readout 5, fingerprint 5, hygiene 7, trends-canary 7).

---

### B9 — Load testing, failure drills, staged rollout

**Scope.** Prove the SLOs under failure; roll out surface by surface; remove obsolete paths only after verification.

**Implementation**

- Full-matrix load test at 10k eps: retries, duplicates, downstream failure, backlog, recovery.
- Failure drills: collector outage (client queues locally; business flows unaffected), Pub/Sub backlog, ingestion 500s, consent-service outage (fail-closed for new events; essential business processing unaffected), BigQuery quota exhaustion, misdeployed schema (kill switch + registry revert).
- Runbooks (`docs/analytics/runbooks/`): collector-outage, pubsub-backlog, warehouse-freshness, schema-rollback, consent-failure, analytics-drift.
- Rollout per surface: registry/schemas/server validation → shadow collection → comparison (counts, IDs, schema errors, latency) → activate sinks one by one → consent + key-conversion verification → traffic increase with reconciliation + cost checks → remove obsolete instrumentation. Two-week shadow window for low-risk behavioral signals; **revenue/booking verification passes explicit reconciliation criteria regardless of calendar**.
- Budget alerts live at 50/80/100% with the documented reduction ladder (diagnostics first; revenue pipeline last, never automatic).

**Evaluation criteria**

- Every SLO in §1 demonstrated with recorded evidence, including recovery.
- Every drill has a written before/after; every runbook is exercised at least once, not just authored.

**Acceptance tests**

- Drill results table: each injected failure → expected detection, expected degradation, expected recovery, measured times.
- Rollback drill: release regression → rollback hook → verified return to previous version within the agreed window.
- Final release-readiness checklist (architecture §13) fully evidenced: instrumentation ownership, no double-fires, schema rejection, retry/DLQ behavior, duplicate-delivery idempotency, outbox-gap detection, PII absence, consent grant/deny/withdraw, retention/deletion E2E, 10k eps load test, alert operationality, PostHog/CSP cleanup verified.

**Recorded at implementation (B9 complete)**

- **Full-matrix load test** (`tests/signals/load-matrix.test.ts` + helpers `collector-load.ts`, `full-matrix.ts`), two legs through the §1 fault matrix (retries, duplicate delivery, downstream failure, backlog, recovery):
  - client leg: collector→ingestion→sink, 12.5k events, injected 500-window + duplicate sends — 17,113 eps, p95 3.0 ms, 55 retries, 50 dup sends, sinkRows 12,500/12,500, zero outstanding (recovery to zero-lag); numbers recorded as evidence, correctness invariants hard-asserted.
  - truth-path leg: emitters→outbox→publisher→Pub/Sub (fake, with accept-then-throw crash-replay)→ingestion consumer→sink at **10,000 committed domain events** — 1,000 transient faults + 500 duplicate redeliveries absorbed (sink dedup), backlogPeak 10,000 → backlogFinal 0 in 30 recovery cycles, sinkRows == committed, `reconcileLevel1` clean (`missingPublished: []`, `missingPersisted: []`, `duplicatedPersisted: []`), outbox→sink freshness p95 ≈ 0.7 s (≪ the 15 min §1 target); 11,722 eps wall-clock.
- **Failure drills** (`tests/signals/failure-drills.test.ts`, ledger helper `helpers/drill-ledger.ts`) — six drills, each with injected failure → detection → containment → recovery, measured times recorded into a results table that a final test gates (every PLANNED drill present, runbook file exists, containment + invariants green):
  1. collector-outage — client queues locally (ack-gated removal), business flow unaffected, heal drains to zero-lag, zero loss.
  2. ingestion-500s — storage down → shell 500s → collector status map returns retryable 503; all-or-nothing (no partial writes); heal persists every event.
  3. pubsub-backlog — transport down → backlog builds under the attempts ceiling, never dead-lettered; heal drains; Level-1 clean.
  4. consent-service-outage — token provider down → collector 403s → fail-closed (attempted, never acknowledged, queued); business flow unaffected; heal drains; DENIED consent blocks emission outright.
  5. bigquery-quota-exhaustion — 503 retryable window; status map honoured (never 400/403/413); heal → durable 200, zero loss.
  6. misdeployed-schema — unknown event rejected all-or-nothing to the dead-letter seam (zero silent rows); kill switch (`NEXT_PUBLIC_SIGNALS_ENABLED=false`) no-ops the SDK; registry revert restores flow.
- **Rollback drill** (`tests/signals/rollback-drill.test.ts`): dry-run trail verified (hook never invoked pre-enablement) → enabled-mode spike → alert fires → deploy hook executes exactly once → fake deploy surface verifiably returns to the previous release within the 15-min window; no-regression control is a no-op.
- **Budget ladder** (`packages/analytics/src/server/budget.ts` + unit test): classify at 50/80/100%; `REDUCTION_LADDER` sheds diagnostics first (crash sampling → diagnostic windows → shadow volume → experiments) and touches revenue LAST — test-pinned that no rung is automatic and the revenue rung requires explicit human approval.
- **Runbooks** (`docs/analytics/runbooks/`, 7 files): collector-outage, pubsub-backlog, warehouse-freshness, schema-rollback, consent-failure, analytics-drift, crash-spike — each with detection/degradation/recovery/verification and drill evidence pointers; the drill table test asserts each cited runbook exists (exercised, not just authored).
- **Rollout playbook** (`docs/analytics/rollout-playbook.md`): per-surface state machine (REGISTERED → SHADOW → COMPARING → SINKS_ACTIVE → CONSENT_VERIFIED → TRAFFIC_RAISED → OBSOLETE_REMOVED) with entry gates; two-week shadow window for low-risk signals; revenue/booking events gated on explicit reconciliation regardless of calendar; cost checks at every traffic step; obsolete-instrumentation removal gated on evidence.
- **Release-readiness checklist** (`docs/analytics/release-readiness.md`, 14 items each naming evidence) + meta-test (`tests/signals/release-readiness.test.ts`): parses the checklist and asserts every referenced artifact exists, all runbooks carry detection/degradation/recovery sections, load-bearing assertions are pinned by content (10k volume, drill names, rollback window, never-automatic ladder), and the playbook's state machine + revenue gate stay present — the checklist cannot rot silently.

---

## 3. Rollout strategy (summary)

1. B0–B3 establish the trusted event path before any sophisticated layer. The stateless Cloud Run pipeline is the starting point; Dataflow is a benchmark-justified later addition, not an early dependency.
2. Shadow pipeline stays **separate** from GA4 production reporting until comparison criteria pass.
3. Surfaces roll out in order: `web` → `customer` → `partner` → `dashboard`. GTM marketing tags never roll onto privileged surfaces by default.
4. Removal of any pre-existing instrumentation happens **last**, and only with grep-verifiable evidence that nothing active depends on it.

## 4. Known risks and mitigations

| Risk | Mitigation |
|---|---|
| Client delivery overstated as a guarantee | Delivery SLO denominator = eligible attempted events only; P0 is backend-side; browser events are context, never revenue evidence. |
| HMAC identifiers treated as anonymity | Recorded as pseudonymous in the privacy matrix; deletion workflow covers identity mappings; legal review is a separate workstream. |
| GTM drift (tags bypassing review) | Tag inventory + publishing separation + Preview verification in B4; consent shared with the collector prevents partial-collection drift. |
| Duplicate actions from retries | Envelope-ID + action-idempotency keys proven by replay tests in B2/B6, not assumed. |
| Cost surprise at volume | SQL lint, partition discipline, per-family sampling, budget alerts with a reduction ladder that never auto-kills revenue events; monthly cost model updated against real invoices. |
| Deletion that only hides data | B7 requires raw-dataset erasure tests, not view exclusion. |
| Premature Dataflow | Explicit stage gate: only with benchmark evidence and lag/recovery/cost budgets. |

---

*Plan version 2.1 · derived from the v2.1 production architecture decision · repository-grounded audit evidence in `docs/analytics/instrumentation-inventory.md`.*
