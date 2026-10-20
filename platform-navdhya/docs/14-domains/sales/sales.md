# Sales Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §5](./sales.md); [Shopify parity extensions §3](./sales.md); [ERP parity domains §3](./sales.md) (except §3.4)

Quotes and sales orders with the normative lifecycle ([commercial core domains §5](./sales.md)), Phase 2a order operations — draft orders, order edits, deposits & payment terms, risk annotation, tags/notes ([Shopify parity extensions §3](./sales.md)) — and Phase 4a extensions: blanket orders, backorder management, drop-shipping ([ERP parity domains §3](./sales.md)). Module codes `sales`, `order_ops`. Pro-forma invoices ([ERP parity domains §3.4](../finance/invoicing.md)) live in the invoicing domain; contracts & renewals ([ERP parity domains §10](./contracts.md)) live in the contracts file.

---

## Module: Sales (module code `sales`)

### 5.1 Bounded context

Quote → Order → Fulfillment hand-off. Owns quotes, sales orders, order
lines, and the order state machine; **does not own stock or cash** —
inventory reservation and payment capture happen through contracts/events.

### 5.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `sales_quotes` | id, number (`Q-<yyyy>-<seq>`), customer_id, status (machine), currency, price_list_id, valid_until, totals JSON, salesperson_membership_id | |
| `sales_quote_lines` | id, quote_id, variant_id, description, qty, uom, unit_price, discount_pct, tax_code, line_total | |
| `sales_orders` | id, number (`SO-<yyyy>-<seq>`), customer_id, org_id, status (machine), source (`manual|api|pos|channel:<x>`), currency, quote_id NULL, shipping_address JSON, totals JSON, payment_status (`unpaid|partial|paid` — maintained by payments events), fulfillment_status (derived) | indexed `(tenant_id, status, created_at)` |
| `sales_order_lines` | like quote lines + reserved_qty, fulfilled_qty | |
| `sales_order_history` | append-only status history (mirrors workflow history) | |

Shadow (denormalized, event-refreshed): customer name/code on order header.

### 5.3 State machine (workflow engine)

```text
draft ──submit──▶ pending_approval ──approve──▶ confirmed ──fulfill──▶ fulfilled
  ▲                    │                          │
  └────reject──────────┘                          └──close──▶ closed
draft / confirmed ──cancel──▶ cancelled
```

- Guards: `has_lines`, `prices_resolved`, `credit_within_limit` (contract
  call; breach routes to a credit-approval workflow instead of outright
  rejection).
- `approve` is sensitive (ACR 2) when order total > tenant threshold
  (approval policy, [kernel services §4.3](../../11-workflow/workflow-engine.md)).
- `confirmed` triggers reservation (event); `fulfilled` is set by the
  inventory fulfillment confirmation (event), never manually.

### 5.4 Primary flows

1. **Quote lifecycle**: draft → add lines (price via PriceResolver, editable
   with `sales:order:discount` guard) → send (notification + PDF via Files)
   → `:convert` to order (one-click, idempotent).
2. **Order confirmation**: submit → credit check → approval (if threshold) →
   confirm → emit `sales.order.confirmed` → inventory reserves.
3. **Fulfillment**: warehouse picks/packs (inventory issues stock) →
   inventory emits `inventory.fulfillment.completed` with order reference →
   sales marks lines fulfilled → order `fulfilled` → invoicing becomes
   eligible.
4. **Cancellation**: allowed until fulfillment starts; confirmed→cancel
   releases reservation via event.

### 5.5 Event catalog

| Event | Payload highlights |
|-------|--------------------|
| `sales.quote.created` / `.updated` / `.sent` / `.converted` | quote_id, customer_id, totals |
| `sales.order.created` | order_id, customer_id, totals, source |
| `sales.order.submitted` / `.approved` / `.rejected` | order_id, approver |
| `sales.order.confirmed` | order_id, lines [{variant_id, qty}], org_id |
| `sales.order.fulfilled` | order_id, fulfillment refs |
| `sales.order.cancelled` | order_id, reason, released_reservation |

### 5.6 API surface (`/api/v1/sales`)

| Endpoint | Permission | Notes |
|----------|-----------|-------|
| GET/POST quotes, `POST /quotes/{id}:send`, `:convert` | `sales:quote:view|create|update` | convert is idempotent |
| GET orders, `GET /orders/{id}` (+lines) | `sales:order:view` | |
| `POST /orders` | `sales:order:create` | idempotency required |
| `POST /orders/{id}:submit`, `:approve`, `:reject`, `:cancel` | `sales:order:submit` / `sales:order:approve` (sensitive) / `sales:order:cancel` | workflow verbs |
| `PATCH /orders/{id}` (draft only) | `sales:order:update` | state guard |
| `GET /orders/{id}/history` | `sales:order:view` | workflow history |

Scopes: `sales:read`, `sales:write`, fine `sales:order:approve`.

Contracts: `SalesOrderLookup` (summary + status by id/number),
`OpenOrdersExposure` (for customer credit computation).

### 5.7 Integration points

Consumes contracts: pricing (resolve), customers (credit/status), catalog
(variants/uom), inventory (availability pre-check).
Consumes events: `inventory.fulfillment.completed`.
Publishes to: inventory (reserve/release/issue), invoicing, finance,
analytics.

---
---

## Order operations (Phase 2a)

### 3.1 Bounded context

Staff-facing order lifecycle tooling layered on the single sales-order
aggregate ([commercial core domains §5](./sales.md)): **draft orders** (invoice-link orders created by
staff), **order editing** after placement, **deposits & payment
schedules**, **payment terms (B2B net-N at checkout)**, and **order risk
annotation**. `order_ops` owns draft metadata and edit ledgers; it never
owns stock or cash.

### 3.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `draft_orders` | id, number (`DO-<yyyy>-<seq>`), sales_order_id (UQ, the underlying `sales_orders` row, status `draft`), customer_id NULL, status (`open|invoice_sent|completed|cancelled`), invoice_url_token (hashed), valid_until, deposit_schedule JSON, payment_term_id NULL | one draft → one sales order ([LD-05](../../21-adrs/ADR-0006-draft-orders-sales-projection.md)) |
| `order_notes` | id, order_id, body, visibility (`internal|customer`), author_membership_id | audited |
| `order_tags` | id, order_id, tag | tags mirrored to `sales_orders.tags` ([Shopify parity extensions §3.7](./sales.md)) |
| `order_edits` | id, order_id, status (`pending|applied|rejected`), requested_by, applied_by, change_set JSON (line adds/removes/qty, shipping, discount), restock (bool), totals_delta JSON | edit ledger, append-only |
| `order_edit_lines` | id, order_edit_id, action (`add|remove|modify`), order_line_id NULL, variant_id, qty, unit_price | normalized edit detail |
| `payment_schedules` | id, order_id, kind (`deposit|installment|balance`), amount (Money), due_at, status (`pending|captured|failed|waived`), payment_id NULL | deposits & installments |
| `order_risk_assessments` | id, order_id, score, verdict (`low|medium|high`), signals JSON, assessed_at, assessed_by (`rule|model`) | post-capture annotation ([LD-12](../../21-adrs/ADR-0013-order-risk-post-capture.md)) |

`sales_orders` gains (additive migration): `tags JSON NULL`,
`order_notes_count`, `risk_verdict VARCHAR(10) NULL`.

### 3.3 Draft order flow (normative)

1. **Create**: staff (or API) creates a draft → `order_ops` creates the
   underlying `sales_orders` row with `status=draft`, `source=draft_order`
   ([commercial core domains §5.2](./sales.md)) → lines priced via `PriceResolver` → totals.
2. **Invoice link**: `POST /draft-orders/{id}:send-invoice` generates a
   single-use, hashed `invoice_url_token`, marks `invoice_sent`, and sends
   a buyer payment link (notifications). The buyer completes payment via
   the public checkout funnel ([commerce domains §2](../commerce/store-checkout.md)) bound to this draft.
3. **Complete**: payment captured → draft `completed`, underlying order
   proceeds through the normal sales state machine ([commercial core domains §5.3](./sales.md)) from
   `confirmed`.
4. **Cancel**: releases nothing (draft holds no reservation until
   confirmed); emits `order_ops.draft.cancelled`.

Drafts do **not** reserve stock until the underlying order is confirmed
([commercial core domains §5.3](./sales.md) guard) — consistent with [LD-05](../../21-adrs/ADR-0006-draft-orders-sales-projection.md).

### 3.4 Order editing flow (normative)

Editing applies only to orders in `confirmed` or `fulfilled` (partial);
`cancelled`/`closed` are immutable.

1. **Propose edit**: `POST /orders/{id}/edits` with a `change_set`
   (add/remove/modify lines, shipping change, discount change) and
   `restock` flag. Creates `order_edits` (`pending`) + `order_edit_lines`.
2. **Validate**: re-resolve prices for added lines; check stock for adds
   via `StockAvailability`; compute `totals_delta`.
3. **Apply** (`POST /order-edits/{id}:apply`, idempotent, sensitive above
   amount threshold):
   - Added lines → reserve stock (event → inventory, [commercial core domains §7.4](../inventory/inventory.md) flow 1).
   - Removed lines → release reservation; if `restock=true` and already
     fulfilled, create a restock movement (inventory) + optional refund
     (payments) + credit note (invoicing).
   - Recompute order totals; update `sales_orders.totals`.
   - Emit `order_ops.edit.applied` with full before/after delta.
4. **Reject**: `order_edits.rejected`, no side effects.

Edits are the **only** sanctioned post-confirmation mutation path; raw
status/totals writes remain prohibited ([API platform §2.2](../../07-kernel/api-platform.md)).

### 3.5 Deposits & payment terms (normative)

- **Deposits**: a draft (or order) may carry a `payment_schedules` plan
  (e.g. 30% deposit, 70% on fulfillment). Each schedule entry captures via
  the payments contract ([commercial core domains §8](../finance/payments.md)) with its own idempotency key;
  `payments.payment.captured` advances the schedule entry. The order's
  `payment_status` ([commercial core domains §5.2](./sales.md)) reflects the schedule aggregate.
- **Payment terms (B2B)**: when a buyer checks out with a payment term
  (e.g. net-30) instead of immediate payment, checkout emits a terms-based
  capture: the order is created with `payment_status=on_terms` and an AR
  invoice is issued immediately (invoicing, [commercial core domains §9](../finance/invoicing.md)) with the term's due
  date. Collection flows through AR ([finance domains §3](../finance/ar-ap-banking.md)), not gateway capture.
  Terms eligibility is a B2B-lite attribute (§15).

### 3.6 Order risk annotation (normative)

1. Trigger: consume `checkout.order.placed` ([commerce domains §2.6](../commerce/store-checkout.md)) asynchronously.
2. Evaluate deterministic v1 rules (velocity: orders per customer/hour;
   amount threshold; address/billing mismatch; first-time high value);
   each rule contributes to `signals`.
3. Write `order_risk_assessments` + mirror `sales_orders.risk_verdict`;
   emit `order_ops.risk.assessed`.
4. High-risk verdict notifies staff (notifications) and feeds anomaly
   detection ([intelligence §3](../../17-ai/intelligence.md)) as a candidate signal. **Never** blocks the
   capture that already happened ([LD-12](../../21-adrs/ADR-0013-order-risk-post-capture.md)); blocking fraud rules are a
   post-v1 policy layer.

### 3.7 Tags & notes

Tags are free-form strings stored on `order_tags` and mirrored to
`sales_orders.tags` for list filtering ([API platform §3](../../07-kernel/api-platform.md) filters). Notes
(`internal` vs `customer`) are audited; `customer` notes surface in the
buyer account order view (§5.2). Both are permission-gated.

### 3.8 Event catalog

`order_ops.draft.created` / `.invoice_sent` / `.completed` / `.cancelled`,
`order_ops.edit.proposed` / `.applied` / `.rejected`,
`order_ops.deposit.captured` / `.schedule_completed`,
`order_ops.terms.invoice_issued`, `order_ops.risk.assessed`.

### 3.9 API surface (`/api/v1/order-ops`)

| Endpoint | Permission | Notes |
|----------|-----------|-------|
| CRUD draft orders | `order_ops:draft:view|create|update` | idempotency on create |
| `POST /draft-orders/{id}:send-invoice` / `:cancel` | `order_ops:draft:update` | send = notification |
| `POST /orders/{id}/edits`, `GET /order-edits/{id}` | `order_ops:edit:view|create` | |
| `POST /order-edits/{id}:apply` / `:reject` | `order_ops:edit:apply` (sensitive above threshold) | idempotent apply |
| CRUD `order-notes`, `order-tags` | `order_ops:note:view|create` | audited |
| `GET /orders/{id}/schedules`, `POST .../schedules/{sid}:capture` | `order_ops:schedule:view|capture` (capture sensitive) | deposits |
| `GET /orders/{id}/risk` | `order_ops:risk:view` | |

Public buyer invoice payment: the draft's invoice link enters the public
checkout funnel ([commerce domains §10.2](../commerce/store-checkout.md) allowlist pattern) — no new public surface
beyond the documented checkout paths. Scopes: `order_ops:read`,
`order_ops:write`, fine-grained sensitive `order_ops:edit:apply`,
`order_ops:schedule:capture`.

Contracts published: `OrderEditEligibility` (consumed by POS/service),
`DraftOrderLookup`.

### 3.10 Integration points

Consumes: sales (order aggregate), pricing, inventory (reserve/release/
restock), payments (capture/refund), invoicing (credit notes, terms
invoices), customers (terms), notifications. Publishes to: finance
(deposit/COGS/revenue adjustments via posting engine), analytics, anomaly.

---
---

## Sales extensions (Phase 4a)

Phase 4a sales extensions; pro-forma invoices ([ERP parity domains §3.4](../finance/invoicing.md)) are owned by the invoicing domain.

### 3.1 Scope

Enterprise sales patterns on top of the existing order model ([commercial core domains](./pricing.md)
§5): blanket orders, backorder management, pro-forma invoices,
drop-shipping. No new order aggregate ([Shopify parity extensions §1](../commerce/shopify-parity-extensions.md) rule 1 applies
platform-wide).

### 3.2 Blanket orders (normative)

| Table | Key fields |
|-------|-----------|
| `sales_blanket_orders` | id, number (`BSO-<yyyy>-<seq>`), customer_id, status (`open|exhausted|expired|closed`), valid_from, valid_to, currency, totals JSON |
| `sales_blanket_order_lines` | blanket_id, variant_id, committed_qty, released_qty, unit_price (Money) |

Rules:
1. Release = create a standard sales order with `blanket_ref` (order
   carries the blanket line's unit_price unless overridden with
   `sales:order:discount` guard).
2. `released_qty` increments atomically in the release transaction
   (row lock, [commercial core domains §7.3](../inventory/inventory.md) pattern); over-release ⇒ 422
   `sales_blanket_over_release`.
3. Expiry job flips `open → expired` past `valid_to`; manual `:close`
   releases remaining commitment (audited).
4. Events: `sales.blanket.created` / `.released` / `.expired` /
   `.closed`. Permissions: `sales:blanket:view|create|update|close`
   (close sensitive).

### 3.3 Backorder management (normative)

Builds on the existing reservation shortfall path ([commercial core domains §7.4](../inventory/inventory.md) flow 1):

1. **Report**: backorders = sales order lines with `reserved_qty <
   qty` in `confirmed` orders, joined locally within sales + the
   `inventory.reservation.partial_flagged` payload snapshot
   (`sales_backorder_snapshots` table refreshed by listener).
2. **Allocation run**: `POST /api/v1/sales/backorders:allocate`
   (`sales:backorder:allocate`) — consumes available stock FIFO by order
   confirmation time; each successful allocation emits the standard
   reservation events ([commercial core domains §7.5](../inventory/inventory.md)); the run is idempotent per
   (run_id) and reports per-line outcomes.
3. Automatic trigger: on `inventory.stock.received` for a variant with
   pending backorders, a queued allocation run executes (debounced).
4. Events: `sales.backorder.flagged` / `.allocated` /
   `.allocation_run_completed`. Permissions:
   `sales:backorder:view|allocate`.

### 3.5 Drop-shipping (normative)

1. Sales order lines flagged `drop_ship=true` are **excluded from stock
   reservation** (inventory listener skips them).
2. On `sales.order.confirmed`, a purchasing listener auto-creates a PO
   per supplier via the purchasing module's `PurchaseOrderFactory`
   contract (new additive contract published by `purchasing`): unit
   cost from `supplier_items.last_cost`, ship-to address = the
   customer's shipping address, `drop_ship_ref=order_line_id`.
3. GRN against a drop-ship PO performs **no stock movement**; instead it
   emits `purchasing.receipt.dropship_completed` (line refs) which the
   sales listener maps to line fulfillment →
   `inventory.fulfillment.completed` is emitted by the inventory module
   as a virtual fulfillment (keeps the sales state machine untouched by
   drop-ship specifics).
4. Returns on drop-shipped lines follow the normal returns flow
   ([commerce domains §5](../commerce/promotions-shipping-returns.md)) with disposition `return_to_supplier`.
5. Events: `sales.order.dropship_initiated`,
   `purchasing.receipt.dropship_completed`. Permissions: drop-ship flag
   on lines requires `sales:order:create` (no new permission).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| sales.* | 10³–10⁴ |
| order_ops.* | 10²–10³ (edits/drafts) |
| sales/purchasing extensions | 10¹–10² (blankets/backorders) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `sales_order` | sales | approve (threshold) |
| `sales_quote` | sales | — |
| `draft_order` | order_ops | send-invoice (operational), complete |
| `order_edit` | order_ops | apply (amount threshold) |
| `sales_blanket_order` / `purchase_blanket_order` | sales / purchasing | close |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| sales | `PriceResolver` (pricing), `CustomerCreditCheck` / `CustomerStatusQuery` (customers), `ProductLookup` (catalog), `StockAvailability` (inventory) |
| sales (credit chain via customers) | `CustomerBalanceQuery` (ar) |
| order_ops | `PriceResolver` (pricing), `StockAvailability` (inventory), payments capture/refund, invoicing credit notes + terms invoices |
| sales (drop-ship) | **`PurchaseOrderFactory`** (purchasing, new additive) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| sales | `sales:quote:view|create|update`, `sales:order:view|create|update|submit|approve|cancel` (approve sensitive), `sales:order:discount` |
| order_ops | `order_ops:draft:view|create|update`, `order_ops:edit:view|create|apply` (apply sensitive above threshold), `order_ops:note:view|create`, `order_ops:schedule:view|capture` (capture sensitive), `order_ops:risk:view` |
| sales (extensions) | `sales:blanket:view|create|update|close` (close sensitive), `sales:backorder:view|allocate` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Order lifecycle E2E**: quote → convert → submit → credit-block → approve
   → confirm → reserve → fulfill → invoice → capture → paid; assert every
   event fired in order with correct causation ids.
2. **Idempotency**: duplicate `POST /orders` with the same key returns the
   same order; duplicate `:issue` on an invoice is a no-op replay.
3. **Draft order E2E**: draft → invoice link → buyer payment → order
   confirmed; no stock reserved while draft; cancel leaves no residue.
4. **Order edit integrity**: apply edit with add+remove lines updates
   totals exactly (Money math), reserves/releases stock, restock movement
   + credit note created when fulfilled lines are removed; duplicate
   `:apply` replays idempotently; SoD/threshold sensitivity enforced.
5. **Deposit schedule**: 30/70 schedule captures independently; order
   payment_status tracks aggregate; double-capture of one schedule entry
   rejected by unique ledger ref.
6. **Blanket orders (both directions)**: over-release ⇒ 422; expiry job
   flips state once; close releases commitment with audit; released POs
   inherit blanket pricing.
7. **Backorder allocation**: FIFO by confirmation time; run idempotent
   per run_id; receipt-triggered run allocates exactly the received
   quantity across flagged lines.
8. **Drop-ship E2E**: confirmed drop-ship line reserves nothing; PO
   auto-created with customer ship-to; GRN posts no stock movement;
   sales line reaches fulfilled via the virtual fulfillment event;
   return disposition `return_to_supplier` routes correctly.
9. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
10. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
