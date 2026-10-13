# Instrumentation Inventory (B0 audit evidence)

Audit date: 2026-10-12 · Scope: repository-wide search for code that emits, forwards,
stores or displays analytics/telemetry events. This is the **active baseline** the
Signals shadow pipeline (B3) compares against. Removal decisions per the plan:
inventory ≠ removal; only dead instrumentation was removed in B0.

Searches run: `track|beacon|telemetry|analytics|posthog|gtag` across `apps/`,
`packages/`, `infrastructure/`, `workers/`.

## Removed in B0 (dead instrumentation — evidence-backed)

| Path | What it was | Evidence of deadness | Action |
|---|---|---|---|
| `apps/web/components/analytics/PostHogProvider.tsx` | PostHog stub (PR 0.25): `initPostHog`, `trackEvent`, `identifyUser`, `PostHogPageview` | Zero imports of all 4 symbols anywhere in `apps/` or the repo; never mounted in any layout; env vars `NEXT_PUBLIC_POSTHOG_KEY/HOST` read but never set anywhere | **Deleted** |
| `posthog-js` dependency (`apps/web/package.json`) | Runtime for the stub | Only referenced by the deleted stub's dynamic string import | **Removed** |
| CSP `https://*.posthog.com` in `apps/web/next.config.ts` `connect-src` | Network allowance for PostHog | No remaining code can reach PostHog | **Removed** |
| `@vercel/analytics` | Referenced in `audit-04june.md` (layout.tsx:4) | **Stale reference** — no `@vercel/analytics` or `SpeedInsights` import exists anywhere in the repo | Nothing to remove; audit doc claim corrected |

## Active instrumentation (kept — the real baseline)

| Path | What it does | Destination | Owner surface | Signals disposition |
|---|---|---|---|---|
| `apps/web/components/ad-network/adapters/adapters.ts` | Ad Engine v2: 30+ ad-tech adapters (GAM, Meta, Amazon DSP, DV360, TTD, Xandr, Prebid, OpenRTB, VAST/VPAID, Segment, mParticle, Rudderstack, …); `trackEvent` forwards ad events per adapter; fan-out at `adapters.ts:1163` (`try { a.trackEvent(payload); } catch {}`) | Third-party ad networks; `https://graph.facebook.com` is the CSP-confirmed live egress | web | **Keep for B0–B3.** Future: governed as GTM tags (B4) on web only. Not registered in the Signals registry until its disposition is decided. |
| `apps/web/app/api/ads/track/route.ts` + `track-beacon/route.ts` → `tracking-handler.ts` | First-party ad impression/click tracking batches. Pipeline: 64 KB cap → JSON → zod `trackingBatchSchema` → per-IP throttle (60 batches/min/IP) → `getAdOrchestrationRuntime()` recording + side effects. Unauthenticated by design, bounded. | Internal (ad orchestration runtime / frequency capping via `infrastructure/cache/redis/ads/FrequencyStore.ts`) | web | **Keep.** Candidate to fold into the Signals registry as `internal_only` events at B4, reusing its (already sound) validation/throttle pattern as reference for the collector contract. |
| `apps/web/lib/dlo-x/telemetry.ts` | `TelemetryService.track(event, properties)` — routes every payload through `removePII` (`lib/dlo-x/security.ts`); dev-only console sink; production fetch is a commented-out example | **No production sink today** (dev logging only) | web | **Keep.** The `removePII` chokepoint pattern is the reference implementation for the B1 privacy firewall requirement; a future Signals sink may back this service. |
| `apps/web/lib/search/federated.ts:487-521` | Persists one search into Laravel's `search_queries` table (analytics record of what was searched). Fire-and-forget; payload carries query text only — never identity, token or header; failures swallowed (throttled sink is not a search failure). | Laravel backend `/search/analytics` | web | **Keep.** To be folded into the Signals envelope/taxonomy when search events enter the registry (B1 `search` event); until then it remains the authoritative search log. |
| `apps/partner/presentation/temples/TempleAnalyticsView.tsx` | Partner-app view **displaying** temple analytics (read-only UI over an `analytics` prop) | None (display only) | partner | **Keep.** Consumer, not emitter. Its data source becomes Signals marts (B5+) when partner reporting moves to the warehouse. |
| `workers/outbox/worker.ts` + `packages/contracts/src/messaging/outbox.ts` + `infrastructure/messaging/outbox/MemoryOutboxStore.ts` | Transactional outbox contract and a **memory-only** store (wired in `create-platform.ts`; `transaction.ts` notes "becomes outbox/distributed strategy later"). No Postgres implementation exists. | In-process | platform | **Keep — B2 extends this.** The `OutboxStore` interface gains lease semantics (`claimed_at`, `lease_expires_at`, `attempts`, `dead_lettered`) and a `PostgresOutboxStore` implementation. This is the business-truth spine of the Signals truth path. |

## Compatibility notes

- **Service worker** (`apps/web/public/sw.js`, v4.0.3): network-only with a 5s timeout
  race; non-navigation requests get a 503 on timeout/network failure; only
  `/offline.html` and `/navdhya-logo.png` are cached. Verdict: **compatible** with the
  Signals collector SDK — a 503 is a retryable response for the SDK's
  fetch-with-ack path, and `sendBeacon` bypasses service workers entirely. No SW
  change required; the SDK must treat 5xx as retryable (contract in B1/B3).
- **CSP after B0**: `graph.facebook.com` retained (active ad-adapter egress).
  GA4/GTM/collector allowances are **not** added until B3/B4 — they would be dead
  allowances before then.

## Verification commands

```sh
rg -i posthog apps/ packages/ infrastructure/ services/   # expect: no matches
rg '@vercel/analytics'                                     # expect: no matches
rg 'posthog' next.config.ts apps/web/next.config.ts        # expect: no matches
```
