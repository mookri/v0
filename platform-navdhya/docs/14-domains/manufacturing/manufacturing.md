# Manufacturing Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [enterprise ops domains §5](./manufacturing.md); [ERP parity domains §11](./manufacturing.md)

Bills of materials with circularity checks, work orders with the normative ledger postings (consumption/output), and routing ([enterprise ops domains §5](./manufacturing.md)). Phase 4a added shop-floor operation reporting and by-products & scrap ([ERP parity domains §11](./manufacturing.md)). Module code `manufacturing`. Quality inspection gates ([ERP parity domains §7](./quality.md)) live in the quality domain.

---

## Module: Manufacturing (module code `manufacturing`)

### 5.1 Bounded context

Light manufacturing: bills of materials, work orders, production
posting (consume raw materials, produce finished goods), and shop-floor
status. Full MRP II / capacity scheduling is out of v1 scope; the module
delivers BOM + work orders + material consumption + output receipt and a
**light MRP** suggestion feed into supply chain ([enterprise ops domains §6](../supply-chain/supply-chain.md)).

### 5.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `bills_of_material` | id, code (`BOM-<seq>`), variant_id (finished good), version, status (`draft|active|obsolete`), yield_qty, effective_from/to | one active BOM per variant at a time (UQ tenant, variant, active) |
| `bom_lines` | id, bom_id, component_variant_id, qty_per, scrap_pct, operation_seq, note | |
| `work_centers` | id, code, name, org_id, cost_per_hour Money NULL | |
| `operations` | id, bom_id NULL / work_order_id, seq, work_center_id, setup_minutes, run_minutes_per_unit | routing-lite |
| `work_orders` | id, number (`WO-<seq>`), bom_id, variant_id, planned_qty, produced_qty, status machine, planned_start/end, work_order_type (`make_to_stock|make_to_order`), sales_order_id NULL | |
| `work_order_consumptions` | id, work_order_id, component_variant_id, planned_qty, consumed_qty, batch NULL | posted via inventory movements |
| `work_order_outputs` | id, work_order_id, qty_good, qty_scrap, posted_at, location_id | posted via inventory movements |

### 5.3 State machine (work order)

`draft → released → in_progress → completed`, plus `closed`
(after variance posting) and `cancelled` (from draft/released only).

- `released`: component availability checked via inventory contract
  (`StockAvailability`); shortfall allowed (backorder) but flagged.
- `in_progress`: consumption postings allowed.
- `completed`: outputs posted; variance (planned vs consumed) computed
  → `manufacturing.order.completed`.
- `closed`: variance posted to GL (material variance account via
  `inventory.valuation.changed`-adjacent posting rule; [finance domains §1.4](../finance/gl.md)).

### 5.4 Primary flows

1. **BOM management**: draft → lines (components from catalog) → activate
   (validates no circular references via graph walk; depth cap 10) →
   `manufacturing.bom.activated`.
2. **Production**: create WO from BOM (or from sales order for MTO) →
   release → issue materials: each consumption posting creates an inventory
   `issue` movement ([commercial core domains §7](../inventory/inventory.md)) with ref `work_order` →
   `manufacturing.consumption.posted`.
3. **Output receipt**: post good/scrap quantities → inventory `receipt`
   movement into sellable (good) or damaged (scrap) →
   `manufacturing.output.posted`; scrap above threshold % flags
   `manufacturing.scrap.exceeded`.
4. **Variance close**: completed → close computes (planned cost − actual
   cost) → GL variance entry via posting rule → `manufacturing.order.closed`.
5. **Light MRP feed**: nightly job explodes open work orders + sales
   demand (orders) + reorder points into component requirements →
   `manufacturing.requirement.computed` (consumed by supply chain
   replenishment, §6).

### 5.5 Event catalog

`manufacturing.bom.created` / `.activated` / `.obsoleted`,
`manufacturing.order.created` / `.released` / `.started` /
`.completed` / `.closed` / `.cancelled`,
`manufacturing.consumption.posted`, `manufacturing.output.posted`,
`manufacturing.scrap.exceeded`, `manufacturing.requirement.computed`.

### 5.6 API surface (/api/v1/manufacturing)

| Endpoint | Permission |
|----------|-----------|
| CRUD BOMs + :activate, :obsolete | `manufacturing:bom:view|create|update`, `manufacturing:bom:activate` |
| CRUD work centers/operations | `manufacturing:work_center:configure` |
| GET/POST work orders, verbs :release, :start, :complete, :close, :cancel | `manufacturing:order:view|create`, `manufacturing:order:lifecycle` |
| POST /work-orders/{id}/consumptions, /outputs | `manufacturing:posting:create` (sensitive — stock ledger impact) |
| GET requirements (MRP view) | `manufacturing:requirement:view` |

Scopes: `manufacturing:read`, `manufacturing:write`, fine
sensitive `manufacturing:posting:create`.

Contracts: `BomLookup` (component explosion for supply chain),
consumes `StockAvailability` + `ProductLookup`.

### 5.7 Integration points

Consumes: catalog (variants), inventory (movements/availability).
Publishes to: inventory (movements via events/contract), gl (variance
posting), supply_chain (requirements).

---
---

## Manufacturing extensions — shop floor & by-products (Phase 4a)

### 11.1 Scope

Shop-floor operation reporting and by-product/scrap handling on top of
[enterprise ops domains §5](./manufacturing.md) (BOMs, work orders, work centers). Full capacity planning /
finite scheduling remains deferred (planning DF-21).

### 11.2 Shop-floor operation reporting (normative)

| Table | Key fields |
|-------|-----------|
| `manufacturing_operation_reports` | id, work_order_id, step_id (routing step), work_center_id, reported_by membership_id, qty_completed, qty_rejected, started_at, completed_at NULL, downtime_minutes, notes |

Rules:
1. Routing steps ([enterprise ops domains §5](./manufacturing.md)) gain `quality_gate` (bool) and
   `report_mode` (`quantity|time|both`) — additive columns.
2. A step's completion report is accepted only when the **preceding**
   step's quality gate (if any) has a passed inspection (§7.3 rule 3);
   otherwise 422 `manufacturing_gate_not_cleared`.
3. Reports advance work-order progress (completed qty per step); labor
   + machine time accumulate on the work order and are absorbed into
   output cost at close ([enterprise ops domains §5](./manufacturing.md) close posting, additive cost
   components).
4. Rejected qty at an operation feeds quality: when the step has a
   quality gate, an NCR (§7.2) is auto-created with source
   `in_process`.
5. Events: `manufacturing.operation.reported`,
   `manufacturing.operation.completed`. Permissions:
   `manufacturing:operation:report` (operators),
   `manufacturing:operation:view`.

### 11.3 By-products & scrap (normative)

1. BOM lines ([enterprise ops domains §5](./manufacturing.md)) gain `line_kind`
   (`component|by_product|scrap`, additive; default `component`) with
   `output_qty_ratio` for non-component kinds.
2. **By-products**: at work-order close, by-product lines are received
   into stock as receipt movements (`ref_type=work_order_byproduct`) at
   the tenant's valuation choice (`net_realizable_value|zero`,
   manufacturing setting); received qty = output qty × ratio (operator
   may adjust within ±10%, audited).
3. **Scrap**: scrap lines post scrap issue movements + a balanced GL
   entry via the posting engine (debit scrap/loss account, credit WIP)
   using the absorbed cost basis.
4. Events: `manufacturing.order.byproduct_received`,
   `manufacturing.order.scrap_recorded` (quantities + refs; costs stay
   in the ledger, not events).
5. Permissions: postings remain `manufacturing:posting:create`
   (sensitive, [enterprise ops domains §7.1](../hr/hr.md)).

### 11.4 API surface (additions, `/api/v1/manufacturing`)

`POST /work-orders/{id}/operations/{stepId}:report`
(`manufacturing:operation:report`, idempotent per (step, started_at));
`GET /work-orders/{id}/operation-reports`
(`manufacturing:operation:view`). BOM line-kind edits under existing
`manufacturing:bom:*` permissions.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| manufacturing.* | 10²–10³ (consumption/output postings) |
| manufacturing extensions | 10²–10³ (operation reports) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `work_order` | manufacturing | postings (stock ledger impact) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| manufacturing | `ProductLookup` (catalog), `StockAvailability` (inventory) |
| manufacturing extensions | quality gates (quality), cost absorption (gl posting engine) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| manufacturing | `manufacturing:bom:view|create|update|activate`, `manufacturing:work_center:configure`, `manufacturing:order:view|create|lifecycle`, `manufacturing:posting:create` (sensitive), `manufacturing:requirement:view` |
| manufacturing (extensions) | `manufacturing:operation:view|report` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **BOM circularity**: A→B→A activation rejected; depth cap enforced.
2. **Work order ledger**: consumption/output postings produce exactly the
   expected inventory movements with `ref_type=work_order`; variance
   close posts a balanced GL entry.
3. **Shop-floor & by-products**: operation report rejected before gate
    passes; rejected qty creates NCR; by-product receipt qty within
    ±10% tolerance else 422; scrap posting balanced.
4. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
5. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
