# Gift Cards, Subscriptions & POS Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commerce domains §6](./giftcards-subscriptions-pos.md)–8; [Shopify parity extensions §7.1–§7.4](./giftcards-subscriptions-pos.md), §8

Gift cards with the ledger invariant ([commerce domains §6](./giftcards-subscriptions-pos.md)), subscriptions with the lifecycle state machine ([commerce domains §7](./giftcards-subscriptions-pos.md)), and POS with offline sync and shift management ([commerce domains §8](./giftcards-subscriptions-pos.md)). Phase 2a added barcode resolution & scan operations plus POS terminal peripherals ([Shopify parity extensions §7.1–§7.4](./giftcards-subscriptions-pos.md)) and selling plans — preorders & trials ([Shopify parity extensions §8](./giftcards-subscriptions-pos.md)). Module codes `giftcards`, `subscriptions`, `pos`. Digital products & delivery ([Shopify parity extensions §7.5–§7.6](./catalog.md)) live in the catalog domain.

---

## Module: Gift Cards (module code `giftcards`)

### 6.1 Bounded context

Stored-value instruments: gift card products, issuance, balance ledger,
redemption at checkout/POS. Gift cards are a **payment method**, not a
discount.

### 6.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `gift_cards` | id, code (UQ per tenant, masked in APIs), store_id, currency, initial_amount, balance (derived from ledger), status (`active|depleted|void|expired`), expires_at NULL, purchaser_customer_id, recipient_email NULL | |
| `gift_card_ledger` | id, gift_card_id, kind (`issue|redeem|refund|adjust|void`), amount (+/−), order_id NULL, actor | **append-only**; balance = sum(ledger) |
| `gift_card_orders` | order lines of type `digital/giftcard` fulfill by issuance | |

Normative: balance is never updated directly; it is computed from
`gift_card_ledger` (cached column for reads, rebuilt by verification job).
Ledger writes share the checkout/payments transaction.

### 6.3 Primary flows

1. **Purchase**: gift card product in cart → checkout completes → issuance
   job creates card + `issue` ledger entry → delivery notification with
   code (email) → `giftcards.card.issued`.
2. **Redemption**: checkout/POS applies code → evaluator validates
   (active, currency match with cart, not expired) → applies up to balance →
   on order placement: `redeem` ledger entry in the order transaction →
   `giftcards.card.redeemed`.
3. **Refund to card**: returns resolution=store_credit or explicit choice →
   `refund` ledger entry (new or existing card).
4. **Void/adjust**: admin actions (`giftcards:card:configure`, sensitive)
   with mandatory reason; adjust requires ACR 2.

### 6.4 Event catalog

`giftcards.card.issued` / `.redeemed` / `.refunded` / `.voided` /
`.adjusted`, `giftcards.balance.low_flagged` (optional notification hook).

### 6.5 API surface (`/api/v1/giftcards`)

Admin CRUD/view under `giftcards:card:view|create|configure` (configure
sensitive); `POST /giftcards/{id}:void`, `POST /giftcards/{id}/adjustments`;
balance lookup `GET /giftcards/balance?code=...` under `giftcards:card:view`.
Buyer public: `POST /api/v1/public/giftcards/balance` (throttled, masked
response). Scopes: `giftcards:read`, `giftcards:write`.

Contracts: `GiftCardPaymentMethod` (pluggable into
`PaymentGatewayDriver` interface as method `gift_card`).

### 6.6 Integration points

Consumes: checkout/POS (redemption), returns (store credit). Publishes to:
payments (as a method), finance (liability accounting — gift card balances
are deferred revenue, [finance domains](../finance/gl.md)), notifications.

---
---

## Module: Subscriptions (module code `subscriptions`)

### 7.1 Bounded context

Recurring commerce: subscription plans, customer subscriptions, renewal
billing, proration, pauses and cancellations. Renewals generate orders +
invoices through the normal Phase 1/2 funnels — no parallel billing stack.

### 7.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `subscription_plans` | id, code, name, store_id, variant_id (the subscription product), billing_interval (`week|month|quarter|year`), interval_count, trial_days, pricing JSON (base + per-seat optional), status | |
| `subscriptions` | id, number (`SUB-<seq>`), customer_id, plan_id, status machine, current_period_start/end, next_billing_at, payment_method_ref (saved payment intent via payments contract), qty, cancelled_at | |
| `subscription_events` | append-only lifecycle log | |
| `billing_attempts` | id, subscription_id, due_at, status (`pending|succeeded|failed|dunning`), order_id NULL, attempt_count | dunning schedule |

### 7.3 State machine

`trialing → active → past_due → cancelled`, `active → paused → active`,
`active → cancelled` (at period end or immediate). `past_due` enters a
dunning schedule (retry days 1, 3, 5, 7; then cancel or grace per tenant
setting).

### 7.4 Primary flows

1. **Signup**: checkout with a subscription plan line → initial order +
   payment → subscription active → `subscriptions.subscription.created`.
2. **Renewal**: scheduler (per-tenant scan) finds `next_billing_at <= now`
   → creates renewal order via CheckoutCapture contract (idempotent per
   period key `<subscription_id>:<period_start>`) → capture → on success
   advance period + emit `subscriptions.renewal.billed`; on failure →
   dunning.
3. **Proration**: plan change mid-period → prorated credit note + new plan
   effective immediately (tenant setting) via invoicing credit note.
4. **Pause/resume/cancel**: policy-guarded transitions; cancellations keep
   access until period end unless immediate.
5. **Payment method updates**: via payments contract (saved instruments;
   PCI scope per [security baseline §2](../../04-security/security-baseline.md) — platform never stores raw card data).

### 7.5 Event catalog

`subscriptions.subscription.created` / `.activated` / `.paused` /
`.resumed` / `.past_due` / `.cancelled`,
`subscriptions.renewal.billed` / `.failed`,
`subscriptions.payment_method.updated`.

### 7.6 API surface (`/api/v1/subscriptions`)

Admin CRUD plans/subscriptions under `subscriptions:plan:view|create|update`,
`subscriptions:subscription:view|update`; verbs
`POST /subscriptions/{id}:pause|:resume|:cancel`
(`subscriptions:subscription:update`; cancel above value threshold
sensitive); `GET /subscriptions/{id}/attempts`. Buyer self-service:
`/api/v1/me/subscriptions` (view + pause/cancel own; authenticated user
scope `kernel:profile:read`). Scopes: `subscriptions:read`,
`subscriptions:write`.

Contracts: `SubscriptionStatusQuery` (customer service screens).

### 7.7 Integration points

Consumes: checkout (capture funnel), payments (methods/refunds), invoicing
(renewal invoices + proration credit notes), notifications (dunning).
Publishes to: finance (recurring revenue), analytics (churn/MRR).

---
---

## Module: POS (module code `pos`)

### 8.1 Bounded context

Point-of-sale terminals in physical stores: terminal pairing (device flow,
[identity & token platform §3.4](../../04-security/identity-and-token-platform.md)), shifts, offline-capable sale capture, cash management,
receipts. POS is a **channel adapter + thin local state machine** — every
sale still flows through CheckoutCapture into sales orders.

### 8.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `pos_terminals` | id, store_id, name, client_id (oauth device client), status (`paired|active|revoked`), last_seen_at | 1:1 with oauth client profile |
| `pos_shifts` | id, terminal_id, opened_by membership_id, opened_at, closed_at, opening_cash, declared_cash, counted_cash, variance, status | |
| `pos_sales` | id, terminal_id, shift_id, local_seq (terminal-local), order_id (after sync), customer_id NULL, lines JSON snapshot, totals, payment_summary JSON, synced_at | offline queue rows until sync |
| `cash_movements` | id, shift_id, kind (`opening|sale|refund|payout|drop|closing`), amount | |

### 8.3 Primary flows

1. **Pairing**: admin creates terminal → device code flow on terminal →
   operator approves → terminal active with scopes limited to POS contract
   routes ([API platform §10.3](../../15-integrations/integration-framework.md)).
2. **Shift lifecycle**: open (cash declaration) → sales → drops (cash
   removal to safe) → close (count + variance) → `pos.shift.closed`;
   variance above tolerance flagged for review.
3. **Online sale**: cart on terminal → CheckoutCapture → payment (cash /
   card terminal external / gift card) → order created → receipt (template
   via Files/notifications) → `pos.sale.recorded`.
4. **Offline sale**: terminal stores `pos_sales` locally; on reconnect,
   syncs with client-generated idempotency keys
   (`<terminal_id>:<local_seq>`) → server replays CheckoutCapture →
   conflicts (stock/price drift) reported to terminal for operator
   resolution, never silently altered.
5. **POS returns**: ReturnEligibility contract + refund to original method
   (cash refunds require shift open; card refunds via payments).

### 8.4 Event catalog

`pos.terminal.paired` / `.revoked`, `pos.shift.opened` / `.closed`,
`pos.sale.recorded` (order_id, terminal_id, shift_id, totals),
`pos.cash.variance_flagged`, `pos.sync.conflict_detected`.

### 8.5 API surface (`/api/v1/pos`)

Terminal-authenticated (device-flow client): `POST /pos/shifts`,
`POST /pos/shifts/{id}:close`, `POST /pos/sales:sync` (bulk, idempotency
required), `GET /pos/terminals/{id}/catalog-delta` (projection sync for
local cache). Admin: `pos:terminal:pair` (sensitive), `pos:terminal:revoke`,
`pos:shift:view`, `pos:sale:view`. Scopes: `pos:read`, `pos:write`,
fine `pos:terminal:pair`.

Contracts: consumes `CheckoutCapture`, `ReturnEligibility`, `StoreLookup`.

### 8.6 Integration points

Consumes: checkout, store, inventory (availability), returns, giftcards.
Publishes to: sales (orders via funnel), finance (cash reconciliation),
analytics (store performance).

---
---

## Barcode operations & POS peripherals (Phase 2a)

Barcode and POS-terminal extensions from Phase 2a ([Shopify parity extensions §7](./giftcards-subscriptions-pos.md)); the digital-product half of that section (§7.5–§7.6) is owned by the catalog domain.

### 7.1 Scope

Cross-channel scan workflows (SH-030/147), POS peripheral configuration
(SH-149), and the digital-product delivery flow (SH-012). No new module:
these extend `catalog`, `pos`, `inventory`, and sales fulfillment.

### 7.2 Barcode resolution contract (normative)

`Catalog\Contracts\BarcodeLookup.resolve(code, tenant)`:

1. Exact match on `products.barcode` or `product_variants.barcode`.
2. Exact match on `products.sku` / `product_variants.sku`.
3. No match ⇒ null (caller maps to 404 `catalog_barcode_unknown`).

Resolution MUST honor tenant isolation; barcode values are unique per
tenant ([commercial core domains §1.3](./catalog.md) flow 2) and the lookup is indexed (`idx` on barcode).

### 7.3 Barcode operations

- **Admin/POS scan lookup**: `GET /api/v1/catalog/barcodes/{code}`
  (`catalog:product:view`) returns variant summary + availability (via
  inventory contract).
- **Receive-by-scan** (purchasing): GRN line entry accepts barcode in
  place of variant_id ([commercial core domains §6](../procurement/purchasing.md) flow 2 unchanged otherwise).
- **Pick-by-scan** (inventory): pick confirmation accepts barcode;
  mismatch ⇒ 422 `inventory_pick_mismatch` (no silent substitution).
- **POS sell-by-scan**: terminal cart add via barcode (CheckoutCapture
  line add); offline mode queues scans with the same local_seq
  idempotency ([commerce domains §8.3](./giftcards-subscriptions-pos.md) flow 4).

### 7.4 POS terminal peripherals (normative)

`pos_terminal_peripherals`: `terminal_id`, kind
(`receipt_printer|barcode_scanner|cash_drawer|customer_display`),
name, driver (`generic_escpos|hid_keyboard_wedge|api`), config JSON,
status. Peripherals are **configuration only** — printing/scanning
executes locally on the terminal ([API platform §10.3](../../15-integrations/integration-framework.md)); the platform never
spools print jobs in v1. Managed under `pos:terminal:configure`
(added to the pos permission set; pairing stays the sensitive step).

Events: `pos.terminal.peripheral_updated`.
---

## Selling plans — preorders & trials (Phase 2a)

### 8.1 Purpose

Shopify-class selling plans generalize subscriptions: **subscription**
plans (recurring), **preorders** (pay now/later, ship later), and
**trial** plans. The Phase 2 `subscriptions` module ([commerce domains §7](./giftcards-subscriptions-pos.md)) is
extended without breaking its existing entities.

### 8.2 Entities (normative)

| Table | Key fields | Notes |
|-------|-----------|-------|
| `selling_plans` | id, store_id, code, name, kind (`subscription|preorder|trial`), billing JSON (interval, count, trial_days), pricing JSON (`recurring|deposit_then_balance|full_prepay`), availability JSON (variant ids), status | replaces the plan-per-variant limitation |
| `selling_plan_policies` | plan_id, kind (`cancel|pause|swap|reschedule`), allowed (bool), conditions JSON | buyer self-service policy |
| `preorder_reservations` | id, plan_id, variant_id, expected_available_on, reserved_qty_limit NULL, committed_qty | preorder capacity tracking |

`subscriptions` ([commerce domains §7.2](./giftcards-subscriptions-pos.md)) gains `selling_plan_id` (additive;
existing rows backfill to a generated 1:1 plan) — existing state machine
([commerce domains §7.3](./giftcards-subscriptions-pos.md)) unchanged.

### 8.3 Preorder mechanics (normative)

1. A preorder selling plan marks its variants purchasable while
   `expected_available_on` is future; checkout captures per the plan's
   pricing kind (`full_prepay` captures now; `deposit_then_balance`
   creates a payment schedule via order_ops §3.5).
2. `preorder_reservations.committed_qty` increments atomically at
   capture (same row-lock pattern as stock, [commercial core domains §7.3](../inventory/inventory.md)); exceeding
   `reserved_qty_limit` ⇒ 409 `selling_plan_capacity_reached`.
3. When stock arrives (inventory receipt event), allocation serves
   committed preorders FIFO by capture time → fulfillment proceeds
   normally; oversell beyond receipts emits
   `selling_plan.preorder_shortfall` (ops alert).

### 8.4 Trial mechanics

Trial plans create subscriptions in `trialing` ([commerce domains §7.3](./giftcards-subscriptions-pos.md)) with the
first billing attempt at trial end; conversion is the existing renewal
scheduler path — no new money flow.

### 8.5 Event catalog (additions)

`selling_plan.created` / `.updated` / `.activated` / `.archived`
(namespace `subscriptions.*`: `subscriptions.selling_plan.*`),
`subscriptions.preorder.committed`, `subscriptions.preorder.shortfall`.

### 8.6 API & permissions

CRUD plans under `subscriptions:selling_plan:view|create|update`;
storefront exposes eligible plans on the public product endpoint
([commerce domains §1.5](./store-checkout.md) additive field `selling_plans`). Buyer purchase flows
through checkout unchanged. Scopes: `subscriptions:read|write`.

### 8.7 Integration points

Consumes: checkout (capture), order_ops (deposit schedules), inventory
(receipts for preorder allocation), notifications. Publishes to: finance
(deferred revenue), analytics.
---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| selling plans / preorder | 10¹–10² |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `subscription` | subscriptions | cancel-above-threshold |
| `pos_shift` | pos | close with variance override |
| `gift_card` | giftcards | void/adjust |
| `preorder_allocation` | subscriptions | shortfall override |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| pos | `CheckoutCapture` (checkout), `ReturnEligibility` (returns), `StoreLookup` (store) |
| subscriptions | `CheckoutCapture` (checkout), payment methods (payments) |
| pos | `BarcodeLookup` (catalog), `CheckoutCapture` (checkout), pickup completion |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| giftcards | `giftcards:card:view|create|configure` (configure sensitive) |
| subscriptions | `subscriptions:plan:view|create|update`, `subscriptions:subscription:view|update` |
| pos | `pos:terminal:pair|revoke` (pair sensitive), `pos:shift:view`, `pos:sale:view`, `pos:terminal:operate` (device scope) |
| pos (extensions) | `pos:terminal:configure` (peripherals; pairing remains the sensitive step) |
| subscriptions (selling plans) | `subscriptions:selling_plan:view|create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Offline POS sync**: terminal replays 50 offline sales; idempotent sync
   creates 50 orders; re-sync is a no-op; conflicting price surfaces
   `pos.sync.conflict_detected`.
2. **Gift card ledger**: balance invariant test (sum of ledger = cached
   balance) after random operation sequences; void/adjust require ACR 2.
3. **Subscription renewal**: scheduler double-run for the same period key
   creates exactly one renewal order.
4. **Preorder allocation**: capture increments committed_qty atomically;
    capacity limit returns 409; receipt allocation serves FIFO; shortfall
    event emitted exactly once per oversell condition.
5. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
6. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
