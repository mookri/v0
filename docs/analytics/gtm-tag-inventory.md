# GTM Web Container — Governed Tag Inventory (Navdhya Signals, phase B4)

Governing plan: [`signals-platform-plan.md`](./signals-platform-plan.md) → "B4 — GTM wiring and key-event ownership".

This inventory is the review record for everything the GTM Web container is
allowed to do on `apps/web`. A tag that is not on this list (or whose data is
not on this list) does not ship.

---

## 1. Delivery policy — one event, one path per destination

Three delivery paths exist. The registry's `allowedSinks` field is the single
source of truth for which path an event may take, and each path is enforced in
code, not by convention:

| Path | Enforced by | Gate |
| --- | --- | --- |
| Browser → GTM → GA4 | `pushGtmEvent()` (`packages/analytics/src/adapters/ga4-data-layer.ts`) | `allowedSinks ∋ gtm_ga4`, `producer === 'client'`, surface `web`, schema-validated, forbidden keys stripped |
| Server → GA4 MP | `buildMpBatch()` (`packages/analytics/src/adapters/ga4-measurement-protocol.ts`) | `allowedSinks ∋ server_ga4`, analytics consent `true`, client/session context resolved (never invented), governed wire-name mapping |
| Browser → collector → `signals_raw` | collector pipeline (`packages/analytics/src/collector/pipeline.ts`) | `allowedSinks ∋ collector_bigquery`, consent token, HMAC, rate limits |

**The `purchase` rule (§0.3).** `purchase` is *not* a registry event — it is
the GA4 wire name of the server-owned `payment_succeeded`. Confirmation pages
must never re-fire it: `pushGtmEvent(dataLayer, 'purchase', …)` is rejected
("unknown event"), so a page cannot duplicate server truth through GTM. The
browser's own confirmation event is `purchase_viewed`, owned by the browser.

Parity is pinned by `tests/signals/key-event-parity.test.ts`: one
`purchase` (server), one `purchase_viewed` (browser), one `booking_confirmed`
(server) — no destination ever receives an event by two paths.

---

## 2. Tag inventory

Only the eight registry events with `gtm_ga4` in `allowedSinks` may reach the
container. Every tag in the container is bound to one of these `dataLayer`
event names.

| # | dataLayer event (registry) | GA4 event | Purpose | Owner | Permitted data (params) | Consent requirement | Destination |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | `page_view` | `page_view` | Traffic and content measurement | growth (per registry) | `page_type` | `analytics_storage=granted` | GA4 (web) |
| 2 | `view_item` | `view_item` | Catalog item interest | growth | `item_type`, `item_category`, `value` | `analytics_storage=granted` | GA4 (web) |
| 3 | `search` | `search` | On-site search demand | growth | `search_term`, `result_count` | `analytics_storage=granted` | GA4 (web) |
| 4 | `checkout_started` | `checkout_started` | Funnel: basket entered | growth | `value_basket`, `item_count` | `analytics_storage=granted` | GA4 (web) |
| 5 | `begin_checkout` | `begin_checkout` | Funnel: payment handoff | growth | `value_basket`, `payment_method` | `analytics_storage=granted` | GA4 (web) |
| 6 | `purchase_viewed` | `purchase_viewed` | Browser-side confirmation view (never `purchase`) | growth | `value`, `item_count` | `analytics_storage=granted` | GA4 (web) |
| 7 | `sign_up` | `sign_up` | Acquisition → account conversion | growth | `method` | `analytics_storage=granted` | GA4 (web) |
| 8 | `login` | `login` | Returning-user measurement | growth | `method` | `analytics_storage=granted` | GA4 (web) |

**Never in the container** (rejected by `pushGtmEvent`):

- `payment_succeeded`, `payment_failed`, `booking_confirmed` — server-owned,
  GA4 receives them only via Measurement Protocol.
- `zero_results_search`, `kyc_submitted`, `app_error` — collector-only.
- `ad_impression`, `ad_click` — `internal_only`; never leaves the first-party
  warehouse.
- Any undeclared event name (including `purchase`).

**Permitted data rule.** Params are exactly the registry-declared set for the
event, schema-validated, with forbidden keys (`email`, `phone`, `uidHash`,
`auth`, `token`, …) stripped before the push. No free-text beyond declared
params; no undeclared Custom HTML payloads. `search_term` is GA4's canonical
`search` dimension — declared and reviewed in the registry, not a free dump.

---

## 3. Consent initialization (Consent Mode v2)

Order is enforced in `apps/web/app/gtm-provider.tsx`:

1. `consent_default` pushed to `dataLayer` **before the container loads** —
   `analytics_storage: denied`, `ad_storage: denied`, `wait_for_update: 500`.
2. Container loads only with `NEXT_PUBLIC_GTM_CONTAINER_ID_WEB` set (web
   surface only; unset → renders nothing).
3. The real consent record drives `setGtmConsent()` → `consent_update`
   (`granted`/`deterministic denied`; `UNKNOWN` maps to **denied**).

Until a consent UI calls `setGtmConsent`, all tags stay at the denied
defaults — fail-closed by construction. Tag-level consent: every tag above is
configured to require `analytics_storage=granted`; consent deny therefore
suppresses GA4 tags deterministically (matrix test:
`tests/signals/consent-matrix.test.ts`).

---

## 4. Container operations (console steps — owner: platform team)

These are external GTM/GA4 console actions, recorded here as the runbook:

- **Preview / Tag Assistant verification**: before publishing, run the
  scripted booking journey in Preview and record the tag-firing sheet
  (evidence for the B4 evaluation criterion — exactly one firing per key
  event per destination).
- **Test vs production publishing**: publish to the GTM *Staging* environment
  (test container / test GA4 stream) first; production publishing is a
  separate, reviewed action. Never point a test container at the production
  GA4 property.
- **No Custom HTML tags** without architecture review; **no free-text
  payloads**; triggers bound only to inventory event names.
- **Data refresh**: registry changes (new event / new param) require the
  inventory table above to be updated in the same PR — `registry:check` and
  review gate the drift.

---

## 5. CSP

`apps/web/next.config.ts` allows exactly what this inventory needs:
`script-src` → `https://www.googletagmanager.com` (loader), `connect-src` →
`https://www.google-analytics.com` / `https://*.google-analytics.com` (GA4
collection), `frame-src` → `https://www.googletagmanager.com` (no-JS
fallback frame). No inline GTM bootstrap is used — the loader is an external
`gtm.js` script.
