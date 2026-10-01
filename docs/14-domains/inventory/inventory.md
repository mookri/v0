# Inventory Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §7](./inventory.md); [ERP parity domains §8](./inventory.md)

Stock levels, movements, reservations, transfers, and adjustments with the normative concurrency model ([commercial core domains §7](./inventory.md)), plus Phase 4a extensions: serial tracking, lot/batch & FEFO with genealogy, FIFO valuation, stock aging, pick & put-away ([ERP parity domains §8](./inventory.md)). Module code `inventory`.

---

## Module: Inventory (module code `inventory`)

### 7.1 Bounded context

Stock truth: warehouses/locations, stock levels, movements (the append-only
ledger), reservations, transfers, adjustments, cycle counts. **All stock
quantity changes are movements** — no direct level edits.

### 7.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `warehouses` | id, code, name, type (`own|3pl|virtual`), org_id | |
| `locations` | id, warehouse_id, code (aisle-rack-bin), kind (`sellable|quarantine|transit|damaged`) | |
| `stock_levels` | variant_id, location_id, on_hand_qty, reserved_qty, available (generated: on_hand − reserved) | `UNIQUE (tenant_id, variant_id, location_id)`; row-locked on mutation |
| `inventory_movements` | id, seq (per tenant), movement_type (`receipt|issue|transfer_out|transfer_in|adjustment|reservation|release|cycle_count`), variant_id, from/to location, qty (+/−), uom, ref_type/ref_id (order/receipt), batch, occurred_at | **append-only**; partitioned by month |
| `stock_reservations` | id, variant_id, qty, ref_type/ref_id (sales order line), status (`held|consumed|released`), expires_at | |
| `stock_transfers` | id, number, from/to warehouse, status machine | |
| `cycle_counts` / `cycle_count_lines` | id, warehouse, status, counted vs book variance | |

### 7.3 Concurrency model (normative)

- Level mutations use `SELECT ... FOR UPDATE` on the exact
  `(variant_id, location_id)` row inside the movement's transaction.
- Insufficient stock ⇒ 422 `inventory_insufficient_stock` (module error
  code, [API platform §4.2](../../07-kernel/api-platform.md)) — no negative stock in v1.
- Every movement write = 1 row in `inventory_movements` + level delta +
  outbox event, one transaction.

### 7.4 Primary flows

1. **Reservation on sales confirmation**: consume
   `sales.order.confirmed` → create reservations against sellable locations
   (allocation strategy: FIFO by location priority); shortfall ⇒
   `inventory.reservation.partial_flagged` event (sales shows backorder state).
2. **Stock-in on receipt**: consume `purchasing.receipt.recorded` → movement
   `receipt` into quarantine or sellable (per item inspection flag) →
   `inventory.stock.received`.
3. **Issue on fulfillment**: pick list (from reserved) → issue movement →
   `inventory.fulfillment.completed` (sales marks fulfilled).
4. **Transfer**: draft → approve (sensitive above value threshold) →
   transit movement out → receipt at destination moves transit → sellable.
5. **Adjustment & cycle count**: counted vs book → variance posted as
   `adjustment` movement with mandatory reason code; approval required above
   value threshold; emits `inventory.stock.adjusted`.

### 7.5 Event catalog

| Event | Payload highlights |
|-------|--------------------|
| `inventory.stock.received` | variant_id, qty, location_id, ref (GRN) |
| `inventory.stock.issued` | variant_id, qty, ref (order) |
| `inventory.stock.adjusted` | variant_id, delta, reason_code, approver |
| `inventory.stock.transferred` | transfer_id, from/to, qty |
| `inventory.reservation.created` / `.released` / `.consumed` | reservation_id, ref |
| `inventory.reservation.partial_flagged` | order_id, shortfall lines |
| `inventory.fulfillment.completed` | order_id, issued lines |
| `inventory.valuation.changed` | variant_id, new avg cost (finance feed) |

### 7.6 API surface (`/api/v1/inventory`)

| Endpoint | Permission |
|----------|-----------|
| GET levels (`?variant_id=&warehouse_id=`), movements | `inventory:stock:view` |
| `POST /adjustments` | `inventory:adjustment:create` |
| `POST /adjustments/{id}:approve` | `inventory:adjustment:approve` (sensitive) |
| CRUD transfers + `:approve`, `:receive` | `inventory:transfer:view|create|approve` (approve sensitive) |
| `POST /cycle-counts`, `:post` | `inventory:cycle_count:create|post` |
| CRUD warehouses/locations | `inventory:warehouse:configure` |
| `GET /availability` (multi-variant probe) | `inventory:stock:view` |

Scopes: `inventory:read`, `inventory:write`, fine-grained approval scopes.

Contracts: `StockAvailability` (available qty per variant/location),
`InventoryValuation` (avg cost, stock value — finance),
`ReorderSuggestions` (below-reorder-point report — purchasing).

### 7.7 Integration points

Consumes events: sales (confirm/cancel), purchasing (receipts).
Publishes to: sales (fulfillment), finance (valuation/COGS), analytics.

---
---

## Inventory extensions (Phase 4a)

### 8.1 Scope

Serial & lot/batch tracking, valuation methods, stock aging, and
pick/put-away workflows — extending the movement-based stock model
([commercial core domains §7](./inventory.md)). All tracking state lives in inventory; other modules
consume via contracts (planning [LD-08](../../21-adrs/ADR-0009-serial-lot-inventory-contracts.md)). Full WMS (directed wave
picking, slotting) remains deferred.

### 8.2 Serial tracking (normative)

| Table | Key fields | Notes |
|-------|-----------|-------|
| `stock_serials` | id, variant_id, serial_number (UQ per tenant+variant), status (`in_stock|reserved|issued|returned|scrapped`), current_location_id NULL, received_ref, issued_ref NULL | one row per physical unit |
| `stock_serial_history` | serial_id, movement_id, occurred_at | append-only trace |

Rules:
1. Serials are captured at GRN (per-unit entry or file import), transfer,
   and issue. A movement of a serial-tracked variant MUST name its
   serials — qty vs serial-count mismatch ⇒ 422
   `inventory_serial_count_mismatch`.
2. Reservation of serial-tracked stock reserves specific serials
   (`stock_reservations.serial_ids` additive); fulfillment issues those
   serials (substitution requires explicit operator action + audit).
3. Service module ([enterprise ops domains §4](../service/service.md)) and warranties (§10.3) consume serial
   state via the `SerialQuery` contract published here.
4. Full-WMS deferral seam: `stock_serials` rows carry location; directed
   serial put-away is post-v1.

### 8.3 Lot/batch & expiry tracking (normative)

| Table | Key fields | Notes |
|-------|-----------|-------|
| `stock_batches` | id, variant_id, batch_number (UQ per tenant+variant+supplier), expiry_on NULL, received_ref, status | batch master |
| `stock_levels` (extension) | gains `batch_id NULL` in the unique key → `(tenant_id, variant_id, location_id, batch_id)` | additive migration |

Rules:
1. Batch-tracked variants carry batch on every movement (GRN already has
   batch/expiry fields, [commercial core domains §6.2](../procurement/purchasing.md) — this makes it authoritative).
2. **FEFO allocation**: reservation for batch-tracked variants allocates
   earliest-expiry batches first (allocation strategy flag per variant:
   `fifo|fefo`); expired batches are excluded from sellable allocation
   and surface in a batch-expiry report.
3. **Genealogy**: `TraceQuery` contract resolves upstream (which GRNs /
   supplier batches fed a batch) and downstream (which orders/work
   orders consumed it) from movement refs — powers recall workflows.
4. Expiry job: daily scan marks batches `expired` past `expiry_on`,
   emits `inventory.batch.expired` (feeds notifications + quality).

### 8.4 Valuation methods (normative)

1. `products` gain `valuation_method` (`weighted_average|fifo`,
   additive; default `weighted_average`). Standard costing is a deferred
   seam (planning DF note; column value reserved).
2. **Weighted average** (existing [commercial core domains](../sales/pricing.md) behavior): `inventory.
   valuation.changed` carries the new average cost.
3. **FIFO**: `stock_valuation_layers` (variant_id, qty_remaining,
   unit_cost, received_ref, received_at) are consumed by issues in
   receipt order; issue cost = Σ consumed layer qty × unit_cost;
   layers are append + decrement only (no edits).
4. COGS posting ([finance domains §1.4](../finance/gl.md)) consumes the issue-cost from the movement
   payload (`cost_basis` additive field) — the posting engine is
   valuation-agnostic.
5. Method change on a variant with stock is prohibited (422
   `inventory_valuation_change_blocked`) — re-valuation is a finance
   project, not a toggle.

### 8.5 Stock aging (normative)

Aging report over `stock_levels` × batch/valuation layers: age buckets
(0–30, 31–60, 61–90, 90+ days) by received date; value per bucket at
current cost basis. Served as `GET /api/v1/inventory/aging`
(`inventory:stock:view`) and as a report definition in finance_reports
([finance domains §8](../finance/assets-budgets-reports.md)). No new events.

### 8.6 Pick & put-away (normative, light)

1. **Put-away suggestions**: on GRN, suggest target locations by
   variant-location affinity (`variant_putaway_rules`: variant_id,
   location_id, priority) — suggestions only in v1; operator confirms or
   overrides (override audited).
2. **Pick lists**: `inventory.pick_lists` (id, ref order/transfer,
   status, lines [{movement plan, location, serial/batch}]) generated
   from reservations; pick-by-scan ([Shopify parity extensions §7.3](../commerce/giftcards-subscriptions-pos.md)) walks the list;
   short-pick emits `inventory.pick.short` (feeds backorder §3.3).
3. Events: `inventory.putaway.suggested`, `inventory.pick.completed`,
   `inventory.pick.short`. Permissions: `inventory:pick:view|operate`,
   `inventory:putaway:configure`.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| inventory.* | 10⁴ (movements dominate) |
| inventory tracking extensions | embedded in existing movement volume (10⁴) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `stock_transfer` | inventory | approve (value threshold) |
| `stock_adjustment` | inventory | approve (value threshold) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| inventory | none synchronous — event-driven |
| inventory extensions | **`SerialQuery`** + **`TraceQuery`** (own; consumed by service, contracts) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| inventory | `inventory:stock:view`, `inventory:adjustment:create|approve` (approve sensitive), `inventory:transfer:view|create|approve` (approve sensitive), `inventory:cycle_count:create|post`, `inventory:warehouse:configure` |
| inventory (extensions) | `inventory:pick:view|operate`, `inventory:putaway:configure` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Concurrency**: two concurrent confirmations racing for the last stock
   unit — exactly one succeeds; the other gets
   `inventory_insufficient_stock`.
2. **Serial lifecycle**: movement qty ≠ serial count ⇒ 422;
    fulfillment issues the reserved serials; substitution path audited;
    `SerialQuery` serves service/warranty lookups without leaking other
    tenants' serials.
3. **Batch FEFO & genealogy**: allocation consumes earliest expiry
    first; expired batches excluded from sellable reservation;
    `TraceQuery` upstream/downstream matches fixture movements
    (recall scenario).
4. **Valuation**: FIFO layer consumption produces exact issue costs
    (golden fixtures); method change with stock ⇒ 422; COGS postings
    consume movement `cost_basis`.
5. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
6. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
