# Promotions, Shipping & Returns Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commerce domains §3](./promotions-shipping-returns.md)–5; [Shopify parity extensions §9](./promotions-shipping-returns.md)

Discounts & coupons with the normative evaluation algorithm ([commerce domains §3](./promotions-shipping-returns.md)), shipping zones/methods/shipments ([commerce domains §4](./promotions-shipping-returns.md)), and returns & refunds with the RMA state machine ([commerce domains §5](./promotions-shipping-returns.md)). Phase 2a added the `applies_on_sale_items` discount condition extension ([Shopify parity extensions §9](./promotions-shipping-returns.md)). Module codes `discounts`, `shipping`, `returns`. Local delivery zones and pickup locations ([Shopify parity extensions §10](./promotions-shipping-returns.md)) live in the store & checkout domain; the local-shipping permission row stays registered here with the shipping module.

---

## Module: Discounts & Coupons (module code `discounts`)

### 3.1 Bounded context

Promotion rules, coupons, and usage accounting. Discounts apply to carts
(via `PromotionEvaluator`), sales quotes/orders (manual + rule-driven), and
are the **only** legal way to reduce document totals besides line-level
price edits (which are themselves permission-gated, [commercial core domains §5.4](../sales/sales.md)).

### 3.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `promotion_rules` | id, code, name, kind (`percentage|fixed_amount|buy_x_get_y|free_shipping|bundle_price`), target (`line|order|shipping`), value JSON, conditions JSON (collections, variants, customer groups, min subtotal, channels), priority, combinable (bool), starts_at/ends_at, status, usage_limit, per_customer_limit | tenant-scoped; stacks by priority within a target |
| `coupons` | id, rule_id, code (UQ per tenant), type (`single|multi_use`), max_redemptions, per_customer, expires_at, status | one rule may have many codes |
| `coupon_redemptions` | id, coupon_id, customer_id NULL, order_id NULL, cart_id NULL, redeemed_at | append-only usage ledger |
| `discount_applications` | id, target_type/id (cart/order/quote line), rule_id, coupon_id NULL, amount | stored on the document for audit + finance |

### 3.3 Evaluation algorithm (normative)

`PromotionEvaluator.evaluate(cart|order, coupon_codes[])`:

1. Validate coupons (active, not expired, usage limits, customer eligibility)
   — invalid codes return structured reasons (never silent ignore).
2. Collect eligible rules (conditions match); sort by priority.
3. Apply non-combinable rules: the best single rule per target wins.
4. Apply combinable rules in priority order; **stacking cap: 3 rules per
   target** (tenant setting `discounts.max_stack`, default 3).
5. Compute amounts with Money math; discounts never push a line below zero
   (excess truncated; recorded as `truncated: true`).
6. Return applications with rule provenance; checkout stores them in
   `checkout_calculations`; sales/invoicing persist
   `discount_applications`.

Buy-X-get-Y: Y lines are added as zero/percent-off lines referencing the
rule; removal of X re-evaluates the whole cart (idempotent).

### 3.4 Primary flows

1. **Coupon redemption**: cart applies code → evaluator → applications shown
   line-by-line; on order placement → `coupon_redemptions` committed in the
   same transaction as the order (one shot — no double redemption under
   concurrency, enforced by a unique redemption-attempt key).
2. **Automatic promotions**: rules without coupons apply silently at
   evaluation time (e.g. site-wide 10% weekend sale).
3. **Manual discount on documents**: `sales:order:discount` permission adds
   an ad-hoc line/order discount with mandatory reason (audited); rule
   discounts and manual discounts coexist but both appear in
   `discount_applications`.

### 3.5 Event catalog

`discounts.rule.created` / `.updated` / `.activated` / `.expired`,
`discounts.coupon.redeemed` (order_id, amount), `discounts.usage.limit_hit`.

### 3.6 API surface (`/api/v1/discounts`)

CRUD rules/coupons under `discounts:rule:view|create|update`,
`discounts:coupon:view|create|update`; `POST /discounts/evaluate` (dry run)
under `discounts:rule:view`; redemption ledger read under
`discounts:redemption:view`. Scopes: `discounts:read`, `discounts:write`.

Contracts: `PromotionEvaluator` (consumed by checkout & sales).

### 3.7 Integration points

Consumes: catalog (conditions), customers (groups), store (channels).
Publishes to: checkout, sales, finance (discount accounting), analytics
(promotion effectiveness).

---
---

## Module: Shipping (module code `shipping`)

### 4.1 Bounded context

Shipping methods, zones, rates, carrier abstraction, shipments and tracking
for orders. Rate **quotes** serve checkout; **shipments** serve fulfillment.

### 4.2 Core entities

| Table | Key fields |
|-------|-----------|
| `shipping_zones` | id, name, countries/regions/pincodes JSON (match order: pincode > region > country) |
| `shipping_methods` | id, zone_id, name, kind (`flat|weight|price_based|carrier`), rate_table JSON, handling_fee, min/max delivery_days, status |
| `shipments` | id, number (`SHP-<seq>`), order_id, warehouse_id, carrier_id NULL, status machine, shipped_at, delivered_at |
| `shipment_packages` | id, shipment_id, weight, dimensions, tracking_number, label_file_id |
| `shipment_lines` | order_line_id, qty |
| `carriers` | id, code, name, driver (`manual|<provider>`), config_ciphertext |
| `tracking_events` | append-only carrier status feed (webhook ingestion) |

### 4.3 State machine (shipment)

`draft → packed → shipped → in_transit → delivered`, plus `exception`,
`returned` (hand-off to returns module), `lost`.

### 4.4 Primary flows

1. **Quote**: `ShippingQuote.for(address, packages, cart_value)` → matching
   zone → eligible methods sorted by cost; returned to checkout pipeline
   step 3.
2. **Fulfillment → shipment**: after `inventory.fulfillment.completed`,
   sales order (or warehouse UI) creates shipment draft → pack (packages +
   weight) → ship (carrier + tracking) → emit `shipping.shipment.shipped`
   → customer notification with tracking link.
3. **Carrier tracking**: carrier driver polls or receives webhook tracking
   updates → `tracking_events` → `shipping.tracking.updated` → delivered
   state auto-advances.
4. **Partial shipments**: one order → many shipments; order
   `fulfillment_status` derived from shipped/total quantities.

### 4.5 Event catalog

`shipping.shipment.created` / `.packed` / `.shipped` / `.delivered` /
`.exception_raised`, `shipping.tracking.updated` (order_id, status),
`shipping.zone.updated`.

### 4.6 API surface (`/api/v1/shipping`)

CRUD zones/methods/carriers under `shipping:zone:view|create|update`,
`shipping:method:view|create|update`, `shipping:carrier:configure`;
shipments CRUD + `POST /shipments/{id}:pack|:ship` under
`shipping:shipment:view|create|update`; `POST /shipping/quote` under
`shipping:method:view`; public tracking lookup
`GET /api/v1/public/tracking/{trackingNumber}` (kernel public marker,
throttled, returns coarse status only — no customer PII).
Scopes: `shipping:read`, `shipping:write`.

Contracts: `ShippingQuote` (checkout), `ShipmentLookup` (service module,
[enterprise ops domains](../hr/hr.md), for delivery-linked tickets).

### 4.7 Integration points

Consumes: inventory (source warehouse), sales (order lines/addresses).
Publishes to: sales (fulfillment status), notifications, analytics
(carrier performance), returns (RMA receipt routing).

---
---

## Module: Returns & Refunds (module code `returns`)

### 5.1 Bounded context

Return Merchandise Authorization (RMA), return receipts, and refund
orchestration. Returns converts a delivered order line back into stock
(via inventory) and money (via payments refunds + invoicing credit notes).

### 5.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `return_requests` | id, number (`RMA-<seq>`), order_id, customer_id, channel_id, status machine, reason_code, requested_at | buyer- or staff-initiated |
| `return_lines` | id, return_request_id, order_line_id, qty, condition (`new|opened|damaged`), resolution (`refund|exchange|store_credit`) | |
| `return_receipts` | id, return_request_id, warehouse_id, received_at, inspected_by | |
| `return_receipt_lines` | qty_accepted, qty_rejected, disposition (`restock|quarantine|scrap`) | drives inventory movements |
| `refund_orders` | id, return_request_id, payment_id, amount Money, status, refund_ref (payments module id) | |
| `return_policies` | id, store_id, window_days, condition rules, restocking_fee_pct | tenant/store settings |

### 5.3 State machine (return request)

`requested → approved → shipped_by_customer → received → inspected →
resolved`, plus `rejected`, `cancelled`. Approval is a workflow transition
(`returns:return:approve`, sensitive above amount threshold).

### 5.4 Primary flows

1. **Request**: buyer (public endpoint, order number + email match) or staff
   creates RMA from order lines → policy validation (window, eligibility) →
   approval (auto below threshold, workflow above) → RMA instructions +
   label (shipping module) → `returns.return.approved`.
2. **Receipt & inspection**: warehouse receives package → per-line
   accept/reject + disposition → inventory movements (restock → sellable;
   damaged → damaged location; scrap → adjustment out) →
   `returns.return.received`.
3. **Refund**: resolution=refund → create refund_order → invoke payments
   refund ([commercial core domains §8.4](../finance/payments.md), sensitive) → on `payments.refund.issued` →
   invoicing credit note ([commercial core domains §9](../finance/invoicing.md)) → `returns.refund.completed`.
4. **Store credit**: resolution=store_credit → gift cards module issues a
   credit code for the refund amount → customer notified.
5. **Exchange**: resolution=exchange → create a zero-value sales order for
   the replacement lines linked to the RMA (inventory reserved normally).

### 5.5 Event catalog

`returns.return.requested` / `.approved` / `.rejected` / `.received` /
`.inspected` / `.resolved`, `returns.refund.requested` /
`.completed`, `returns.exchange.order_created`.

### 5.6 API surface (`/api/v1/returns`)

- Admin: RMA CRUD + `POST /returns/{id}:approve|:reject|:receive|:inspect`
  under `returns:return:view|create|approve` (approve sensitive),
  `returns:receipt:create`, `returns:refund:create` (sensitive).
- Buyer public: `POST /api/v1/public/returns` (order number + email
  verification, throttled; kernel public marker) and
  `GET /api/v1/public/returns/{number}?email=...` status lookup.
- Scopes: `returns:read`, `returns:write`, fine `returns:return:approve`,
  `returns:refund:create`.

Contracts: `ReturnEligibility` (policy check — reused by POS), publishes
refund intents to payments.

### 5.7 Integration points

Consumes: sales (orders), shipping (labels/tracking), inventory
(disposition), payments (refunds), invoicing (credit notes), giftcards
(store credit). Publishes to: analytics (return rates), notifications.

---
---

## Discount condition extension (Phase 2a)

### 9.1 Delta (normative)

`promotion_rules.conditions` ([commerce domains §3.2](./promotions-shipping-returns.md)) gains one additive condition
key (planning SH-067):

- `applies_on_sale_items` (`bool`, default `true`): when `false`, the rule
  does **not** apply to lines already reduced by another rule or a manual
  discount (`discount_applications` present on the line).

Evaluation algorithm ([commerce domains §3.3](./promotions-shipping-returns.md)) is unchanged otherwise; step 3/4
eligibility filtering consults the flag per line. The flag is stored in
the rule's conditions JSON — no schema migration.

### 9.2 Verification hooks

- Evaluator unit tests: sale-item line + `applies_on_sale_items=false`
  rule ⇒ no application; `true` ⇒ applies.
- `discount_applications` provenance MUST record the flag evaluation so
  finance reporting can explain exclusions.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `return_request` | returns | approve (threshold), refund |
| `shipment` | shipping | — (pack/ship operational) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| returns | `ReturnEligibility` (own; consumed by POS), refund via payments, credit notes via invoicing, restock via inventory events |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| discounts | `discounts:rule:view|create|update`, `discounts:coupon:view|create|update`, `discounts:redemption:view` |
| shipping | `shipping:zone:view|create|update`, `shipping:method:view|create|update`, `shipping:carrier:configure`, `shipping:shipment:view|create|update` |
| returns | `returns:return:view|create|approve` (approve sensitive), `returns:receipt:create`, `returns:refund:create` (sensitive), `returns:policy:configure` |
| shipping (local) | `shipping:pickup_location:view|create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Coupon concurrency**: single-use coupon applied from two carts
   simultaneously — exactly one redemption ledger row.
2. **Return cycle**: RMA → receive (partial accept) → refund → credit note;
   stock restored to correct disposition location; refund SoD enforced.
3. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
4. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
