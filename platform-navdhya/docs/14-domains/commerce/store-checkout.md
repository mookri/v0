# Store & Checkout Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commerce domains §1](./store-checkout.md)–2; [Shopify parity extensions §4](./store-checkout.md), §10–§13

Storefront surfaces: stores, collections, and the public buyer paths ([commerce domains §1](./store-checkout.md)), plus cart & checkout with the normative calculation pipeline and checkout state machine ([commerce domains §2](./store-checkout.md)). Phase 2a extended checkout with buy rules ([Shopify parity extensions §4](./store-checkout.md)), local commerce — BOPIS, local delivery, store locator ([Shopify parity extensions §10](./promotions-shipping-returns.md)), abandoned-checkout recovery ([Shopify parity extensions §11](./store-checkout.md)), storefront discovery — feeds, sitemaps, redirects ([Shopify parity extensions §12](./store-checkout.md)), and payment capability flags ([Shopify parity extensions §13](./store-checkout.md)). Module codes `store`, `checkout`. Markets ([Shopify parity extensions §2](./markets.md)) and content ([Shopify parity extensions §6](./content.md)) are separate domain files; channel adapters ([commerce domains §9](../../15-integrations/integration-framework.md)) live under integrations.

---

## Module: Store (module code `store`)

### 1.1 Bounded context

The **storefront projection** of the product master: stores, sales channels,
collections, storefront product visibility, merchandising, and the public
(buyer-facing) read API. The store module is the only Phase 2 module with
public, unauthenticated read endpoints.

### 1.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `stores` | id, code, name, slug (UQ), currency, timezone, status (`active|maintenance|closed`), default_warehouse_id, checkout_settings JSON | one tenant may run many stores (brands/regions) |
| `sales_channels` | id, store_id, kind (`web|pos|marketplace|partner`), code, status | orders record their channel ([commercial core domains](../sales/pricing.md) `sales_orders.source`) |
| `collections` | id, store_id, parent_id, slug, title, kind (`manual|automated`), rules JSON (automated: conditions on tags/category/price) | tree per store |
| `collection_products` | collection_id, variant_id, position | manual membership |
| `storefront_products` | store_id, variant_id, visibility (`visible|hidden`), published_at, title_override, description, images JSON (file ids), seo JSON, tags | projection refreshed from catalog events |
| `storefront_inventory_views` | store_id, variant_id, availability_state (`in_stock|low_stock|out_of_stock`), updated_at | refreshed from inventory events; low-stock threshold per store |
| `wishlists` / `wishlist_items` | customer/user id, variant_id | optional for v1 storefront |

Projection rule (normative): `storefront_products` and
`storefront_inventory_views` are **eventual read models**. They are rebuilt
from `catalog.*` and `inventory.*` events ([kernel services](../../07-kernel/kernel-services.md) listeners) and may lag
authoritative truth by seconds; checkout re-validates against live contracts,
never against the projection.

### 1.3 Primary flows

1. **Publish to store**: catalog activates a product → listener creates
   `storefront_products` rows (default hidden) → merchant publishes per
   store → `store.product.published`.
2. **Collections**: manual curation or automated rules evaluated on catalog
   events; collection membership drives storefront navigation/search.
3. **Availability sync**: inventory events update
   `storefront_inventory_views`; out-of-stock variants stay purchasable only
   if the store allows backorders (setting, default off).
4. **Store maintenance mode**: `maintenance` status serves a kernel-rendered
   holding response on buyer endpoints; admin/API unaffected.

### 1.4 Event catalog

`store.store.created` / `.updated`, `store.product.published` /
`.unpublished`, `store.collection.updated`,
`store.availability.changed` (store_id, variant_id, new state — feeds
notification campaigns later).

### 1.5 API surface

**Admin** (`/api/v1/store`, authenticated): CRUD stores/channels/collections
under `store:store:view|create|update`, `store:collection:view|create|update`;
publish actions under `store:product:publish`. Scopes `store:read`,
`store:write`.

**Buyer (public)** (`/api/v1/public/stores/{storeSlug}`): `GET /products`
(+filters via kernel Search), `GET /products/{slug}`, `GET /collections`,
`GET /availability`. No auth; tenant resolved from store slug; aggressive
edge caching ([API platform §6.2](../../07-kernel/api-platform.md) throttle tier `public`); org scoping not applied
(buyer surface); permission marker: kernel public allowlist entry
([authorization & policy §8.1](../../04-security/authorization-and-policy.md)).

Contracts published: `StoreLookup` (store config by slug/id),
`StorefrontAvailability` (buyer-facing availability state).

---
---

## Module: Cart & Checkout (module code `checkout`)

### 2.1 Bounded context

Carts, checkout sessions, order **capture** into sales orders, and the
tax/shipping/discount **calculation pipeline**. Checkout is the single
funnel: web storefront, POS and partner channels all capture through it.

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `carts` | id, store_id, customer_id NULL (guest carts keyed by `guest_token` hash), channel_id, status (`open|abandoned|converted|expired`), currency, totals JSON | guest→account merge on login |
| `cart_lines` | id, cart_id, variant_id, qty, unit_price (resolved at add-time, re-validated at checkout), discount JSON, meta | |
| `checkout_sessions` | id, cart_id, status machine, shipping_address JSON, billing_address JSON, shipping_method_id, idempotency_key, expires_at | 1 session per cart at a time |
| `checkout_calculations` | id, session_id, line_items JSON, discount JSON, shipping JSON, tax JSON, totals JSON, calculated_at | snapshot for audit/dispute |
| `abandoned_cart_events` | append-only markers for recovery notifications | |

### 2.3 Checkout state machine

```text
open ──start_checkout──▶ addressing ──choose_shipping──▶ payment_pending
payment_pending ──capture_payment──▶ converting ──order_created──▶ completed
any state ──expire/abandon──▶ expired / abandoned
```

- `converting`: cart → sales order ([commercial core domains](../sales/pricing.md)) via idempotent
  `POST /api/v1/sales/orders` with the checkout session's idempotency key;
  the sales order carries `source = channel:<channel_kind>` and
  `checkout_session_id`.
- Failed order creation rolls the session back to `payment_pending` (never
  partial: no order without payment intent, no captured payment without
  order — reconciliation job §2.5).
- `completed` emits `checkout.order.placed`; cart → `converted`.

### 2.4 Calculation pipeline (normative order)

`CheckoutCalculator.compute(session)` runs in exactly this order and the
result snapshot is stored before payment capture:

1. **Base prices**: `PriceResolver` (pricing contract) per line.
2. **Discounts**: discounts module contract `PromotionEvaluator` — line
   discounts first, then order-level, then shipping discounts; each
   application recorded with rule id ([commerce domains §3](./promotions-shipping-returns.md)).
3. **Shipping**: shipping module contract `ShippingQuote` by address,
   weight/volume, method.
4. **Tax**: tax computation via finance tax contract (Phase 3); v1 fallback
   = tenant tax tables by tax_code + jurisdiction; amounts per line +
   summary.
5. **Totals**: subtotal, discount_total, shipping_total, tax_total, grand
   total — Money arithmetic only ([tenancy & data §9](../../13-data/data-architecture.md)).

Rounding: half-up at the **line** level for taxes/discounts; grand total is
the exact sum of rounded components (no residual fudging; a
`rounding_adjustment` line item is emitted if gateway constraints force a
minor-unit reconciliation, audited).

### 2.5 Primary flows

1. **Guest cart → purchase**: add lines → checkout addressing → shipping →
   payment (payments contract capture) → convert → order placed.
2. **Authenticated merge**: login merges guest cart into account cart
   (dedupe by variant, qty sum, cap at stock).
3. **Stock re-validation**: at `payment_pending` entry, live
   `StockAvailability` check; shortfall → 409 `checkout_stock_changed`
   with updated availability (client re-renders).
4. **Payment**: `PaymentGatewayDriver` capture ([commercial core domains §8](../finance/payments.md)) with session
   idempotency key; gateway failure keeps session retryable.
5. **Abandonment**: sessions idle > 30 min → `abandoned` → notification
   candidate (store setting); expired carts purged after 7 days.
6. **Reconciliation**: nightly job cross-checks captured payments without
   orders (payments events vs checkout sessions) → auto-complete or refund +
   `checkout.reconciliation.exception_raised` alert.

### 2.6 Event catalog

`checkout.cart.created` / `.line_added` / `.line_updated` / `.abandoned`,
`checkout.session.started`, `checkout.payment.captured`,
`checkout.order.placed` (order_id, store_id, totals, channel),
`checkout.reconciliation.exception_raised`.

### 2.7 API surface (`/api/v1/checkout` + public buyer paths)

| Endpoint | Auth | Permission/Scope |
|----------|------|------------------|
| `POST /public/stores/{slug}/carts`, lines CRUD | public (guest token) / user | kernel public marker; per-tenant bot throttle |
| `POST /carts/{id}/checkout`, `PATCH .../addressing`, `/shipping-method` | buyer | same |
| `POST /checkout/{id}:capture` | buyer | mediated by store-level public marker + strict throttle; service-side permission `checkout:order:create` |
| Admin: `GET /carts`, `GET /checkout-sessions` | admin | `checkout:cart:view` |

Idempotency: `:capture` **requires** `Idempotency-Key` ([API platform §5.7](../../07-kernel/api-platform.md)).
Buyer-facing routes are the only tenant routes with public markers; the
architecture test pins the exact allowlist.

Contracts: `CheckoutCapture` (used by POS/partner channels to run the same
funnel server-to-server with client-credentials tokens).

### 2.8 Integration points

Consumes: pricing, inventory, discounts, shipping, finance (tax), payments,
store. Publishes to: sales (orders), notifications (abandonment), analytics.

---
---

## Checkout buy rules (Phase 2a)

### 4.1 Purpose

Line-level purchase constraints (min/max quantity per order, increment
steps) declared per product/variant and enforced in the checkout pipeline
([commerce domains §2.4](./store-checkout.md)) and at cart mutation time.

### 4.2 Entities & mechanics (normative)

- `checkout_buy_rules` (checkout module): `id`, `store_id NULL`
  (NULL = all stores), `variant_id NULL` (NULL = product-level),
  `min_qty`, `max_qty`, `increment`, `status`. Most specific rule wins
  (variant > product > store default); rules never override stock truth.
- Enforcement points: cart line add/update (immediate 422
  `checkout_buy_rule_violated` with the rule detail) and re-validated at
  `payment_pending` entry ([commerce domains §2.5](./store-checkout.md) flow 3 pattern) — a rule added
  after cart creation still blocks capture.
- POS ([commerce domains §8](./giftcards-subscriptions-pos.md)) applies the same rules via CheckoutCapture; offline
  sales validate at sync with conflict surfacing ([commerce domains §8.3](./giftcards-subscriptions-pos.md) flow 4).

### 4.3 API & permissions

CRUD under `POST|PATCH|GET /api/v1/checkout/buy-rules` with
`checkout:buy_rule:view|configure` (configure sensitive: rules can block
sales). Scope `checkout:write` + fine-grained `checkout:buy_rule:configure`.

Events: `checkout.buy_rule.updated`.

---
---

## Local commerce — BOPIS, local delivery, store locator (Phase 2a)

### 10.1 Scope

Extends `shipping` + `store` + `pos` for in-person fulfillment modes.
No new module; additive shipping method kinds and pickup points.

### 10.2 Entities (normative)

| Table | Key fields | Notes |
|-------|-----------|-------|
| `pickup_locations` | id, store_id, name, address JSON, lat/lng, pickup_instructions, hours JSON, status | doubles as store-locator entries (public) |
| `shipping_methods` (extension) | `kind` adds `local_pickup`, `local_delivery` ([commerce domains §4.2](./promotions-shipping-returns.md)) | rate_table applies (delivery fee; pickup usually 0) |
| `local_delivery_zones` | method_id, pincodes JSON, fee_rule JSON | delivery area gating |

### 10.3 Checkout integration (normative)

1. `ShippingQuote` ([commerce domains §4.6](./promotions-shipping-returns.md)) returns pickup options when the cart's
   variants are available at a `pickup_locations.warehouse` binding
   (availability via `StockAvailability` per location).
2. Choosing `local_pickup` suppresses shipping address requirement
   (contact details still required) and sets the order's fulfillment
   intent `pickup_at = location_id`.
3. Choosing `local_delivery` validates the address pincode against
   `local_delivery_zones`; outside ⇒ method hidden (never selectable).

### 10.4 Fulfillment flows

- **BOPIS**: order confirmed → pick at store (inventory issue) →
  `ready_for_pickup` notification → POS staff hands over →
  `POST /api/v1/pos/pickups/{orderId}:complete` (`pos:terminal:operate`)
  marks fulfilled (same event path as [commerce domains §8](./giftcards-subscriptions-pos.md)).
- **Local delivery**: order → delivery run (staff assignment, optional
  route batching post-v1) → mark shipped/delivered via standard shipment
  machine ([commerce domains §4.3](./promotions-shipping-returns.md)) with carrier `manual`.
- **Store locator**: public `GET /api/v1/public/stores/{slug}/locations`
  returns active pickup locations (name, address, hours) — coarse public
  data only; added to the public allowlist (§16.2).

### 10.5 API & permissions

CRUD pickup locations/delivery zones under
`shipping:pickup_location:view|create|update` and
`shipping:zone:view|create|update` (reused). POS pickup completion under
existing `pos:terminal:operate`. Events:
`shipping.pickup.ready` / `.completed`, `shipping.local_delivery.assigned`.

---
---

## Abandoned checkout recovery (Phase 2a)

### 11.1 Pipeline (normative)

1. Source: `checkout.cart.abandoned` ([commerce domains §2.6](./store-checkout.md)) — emitted when a
   session idles > 30 min ([commerce domains §2.5](./store-checkout.md) flow 5).
2. Recovery listener evaluates store settings
   (`store.recovery`: enabled, delay schedule e.g. [1h, 24h], max sends
   per cart = 2, quiet hours).
3. Sends recovery notification (email channel, template
   `checkout_abandoned_recovery`) containing a **signed restore link**
   (cart token, TTL 7 days, single-cart bound; kernel Files-style signed
   token, not a session).
4. Restore link re-hydrates the cart into a fresh guest/user session and
   lands on checkout (`payment_pending` if previously reached).
5. Conversion tracking: `checkout.order.placed` with `restored=true`
   payload flag; recovery sends recorded in `notifications_log` with
   campaign tag `abandoned_recovery` for reporting.

### 11.2 Rules

- Guest carts: recovery only when an email was captured (addressing step
  reached).
- Buyer carts: respect `marketing_opt_in` = false ⇒ no recovery sends
  (transactional classification still allows the *restore link* in the
  buyer account "saved cart" view, which always exists).
- Duplicate suppression: one recovery chain per cart id (unique send-key
  in `notifications_log`); re-abandonment after restore starts a new
  chain capped by max sends.

### 11.3 API & permissions

Store recovery settings: `store:settings:configure` ([commerce domains §10.1](./store-checkout.md)).
Recovery report: `GET /api/v1/checkout/recovery-report`
(`checkout:session:view`). No new permissions. Events:
`checkout.recovery.sent`, `checkout.recovery.restored`,
`checkout.recovery.converted`.

---
---

## Storefront discovery — feeds, sitemaps, redirects (Phase 2a)

### 12.1 Purpose

Machine-facing storefront surfaces: product feeds for listing channels,
XML sitemaps for crawlers, and URL redirect management. All read-only,
cache-heavy, tenant-bound by store slug.

### 12.2 Feeds (normative)

- `feed_definitions`: `store_id`, `code` (`google|meta|custom`),
  `field_map JSON` (platform field → feed field), `filter JSON`
  (collections/tags/availability), `schedule`, `status`.
- Generation: queued job composes the feed from `storefront_products` +
  `storefront_inventory_views` + market prices (§2.3 when a market is
  bound) → writes a versioned file to kernel Files → exposes
  `GET /api/v1/public/stores/{slug}/feeds/{code}` (302 to signed file
  URL; edge-cached).
- Feed rows MUST exclude unpublished/hidden products and MUST include
  availability state; out-of-stock rows follow the feed template's policy
  (include-with-flag, default).
- Regeneration triggers: `store.product.*`, `store.availability.changed`,
  `markets.price_rule.updated`, schedule; jobs are idempotent per
  (store, code, version).

### 12.3 Sitemaps (normative)

`GET /api/v1/public/stores/{slug}/sitemap.xml` (+ per-section splits):
generated from published products/collections/pages/articles with
`lastmod` from projection timestamps; max 50k URLs per file with a
sitemap index; regenerated on content publish events and daily.

### 12.4 Redirects (normative)

`storefront_redirects`: `store_id`, `from_path` (UQ per store),
`to_path`, `kind` (`301|302`), `status`. Handle changes on products/\npages auto-insert a 301 from the old handle (event listener on
`store.product.published` / content publish with handle diff). Public
resolution happens at the storefront edge ([API platform §6.2](../../07-kernel/api-platform.md) cache tier):
lookup miss ⇒ normal routing; hit ⇒ redirect before content fetch.

### 12.5 API & permissions

Feed definitions CRUD: `store:feed:view|create|update`; sitemaps public;
redirects CRUD: `store:redirect:view|create|update`. Scopes:
`store:read|write`. Events: `store.feed.generated`,
`store.redirect.created`.

---
---

## Payment capability flags (Phase 2a)

### 13.1 Delta (normative)

`payment_accounts.gateway_config` ([commercial core domains §8.2](../finance/payments.md)) gains a parsed
`capabilities` block maintained by the gateway driver on
configuration/refresh:

```json
{ "cards": true, "wallets": ["apple_pay", "google_pay"],
  "refunds": "full|partial", "captures": "manual|auto",
  "stored_instruments": true, "webhooks": true }
```

Rules:
1. Drivers declare capabilities statically; runtime probes may narrow
   them (never widen).
2. Checkout exposes only methods whose capabilities satisfy the flow
   (e.g. wallet buttons render only when `wallets` includes the wallet;
   subscription signup requires `stored_instruments`).
3. Capability mismatch at capture ⇒ 422
   `payments_capability_unsupported` (fail closed, never attempt).

Events: `payments.account.capabilities_updated`. No new permissions
(configuration stays `payments:account:configure`).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| checkout.recovery.* | 10²–10³ (abandonment volume) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `checkout_session` | checkout | capture (idempotent, not ACR-sensitive) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| checkout | `PriceResolver` (pricing), `PromotionEvaluator` (discounts), `ShippingQuote` (shipping), `StockAvailability` (inventory), `PaymentGatewayDriver` + `GiftCardPaymentMethod` (payments/giftcards), tax contract (finance) |
| checkout / invoicing | `TaxEngine` (tax) |
| checkout | `MarketPriceQuery` / `MarketDutyQuery` (markets), `StoreCreditBalance` (customer_accounts), buy rules (own), tax engine (finance) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| store | `store:store:view|create|update`, `store:collection:view|create|update`, `store:product:publish`, `store:settings:configure` |
| checkout | `checkout:cart:view`, `checkout:order:create` (service-side), `checkout:session:view` |
| checkout (extensions) | `checkout:buy_rule:view|configure` (configure sensitive) |
| store (discovery) | `store:feed:view|create|update`, `store:redirect:view|create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Checkout E2E**: guest cart → coupon + shipping + tax → capture → order;
   assert `checkout_calculations` snapshot equals final order totals;
   duplicate ` :capture` replays the same order (idempotency).
2. **Stock race**: two carts with one unit left — second capture gets 409
   `checkout_stock_changed`, no orphan payment (reconciliation job finds
   nothing).
3. **Tenant isolation**: all tables in this phase covered by the
   `AssertTenantIsolation` matrix; public endpoints never leak
   cross-store/cross-tenant data (store slug → tenant binding tested).
4. **Buy rules**: min/max/increment enforced at cart mutation and again
   at `payment_pending`; POS offline sync surfaces violations as
   conflicts, never silent changes.
5. **Recovery pipeline**: abandoned cart with email ⇒ scheduled sends
    honoring max-sends + quiet hours; restore link re-hydrates exactly
    the abandoned cart; conversion flags `restored=true`; no sends when
    `marketing_opt_in=false`.
6. **Feeds/sitemaps**: generated feed excludes hidden products, includes
    availability; regeneration idempotent per version; sitemap ≤50k URLs
    per file with index; handle change creates 301 redirect that resolves
    at the edge.
7. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
