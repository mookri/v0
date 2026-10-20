# Supply Chain Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [enterprise ops domains §6](./supply-chain.md)

Replenishment policies, suggestions, and the stockout-risk projection; converting a suggestion creates the right document (PO or transfer) in the owning module with a back-reference ([enterprise ops domains §6](./supply-chain.md)). Module code `supply_chain`. The domain is event-driven — no synchronous cross-module reads.

---

## Module: Supply Chain (module code `supply_chain`)

### 6.1 Bounded context

Demand planning and replenishment orchestration: forecasts, reorder-point
planning, replenishment suggestions (buy / make / transfer), and the
execution bridge into purchasing, manufacturing, and inventory transfers.
The module **suggests and orchestrates**; it never writes POs, work orders,
or stock directly — execution goes through the owning modules' contracts
and APIs.

### 6.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `demand_plans` | id, name, horizon_weeks, method (`history_average|seasonal|manual`), status, org scope | per planning cycle |
| `forecast_lines` | id, plan_id, variant_id, week_start, forecast_qty, source (`computed|manual`) | |
| `replenishment_policies` | id, variant_id, warehouse_id, method (`reorder_point|min_max|mrp`), reorder_point_qty, reorder_qty, safety_stock_qty, lead_time_days, preferred_supplier_id NULL, preferred_source (`buy|make|transfer`) | per variant×warehouse |
| `replenishment_suggestions` | id, run_id, variant_id, warehouse_id, need_qty, suggested_source, suggested_ref (supplier/bom/source warehouse), due_date, status (`open|converted|dismissed`) | |
| `replenishment_runs` | id, number (`RR-<seq>`), run_at, inputs JSON (summary), status | |
| `stock_transfer_plan` | id, suggestion grouping for inter-warehouse balancing | |

### 6.3 Primary flows

1. **Forecasting**: demand plan computes per-variant weekly forecast from
   sales history (orders/checkout events history via analytics read model
   contract, [intelligence](../../17-ai/intelligence.md)) + manual overrides → `supply_chain.forecast.computed`.
2. **Replenishment run** (scheduled or manual): for each policy:
   - compute projected available = on_hand + on_order (open POs, via
     purchasing contract) − open demand (open sales orders + forecast in
     lead-time window);
   - if below reorder_point / min → create suggestion with qty and due_date
     (lead-time offset);
   - source selection: preferred_source policy → buy (supplier), make (BOM
     explosion via manufacturing contract), transfer (other warehouse with
     excess).
   → `supply_chain.replenishment.computed`.
3. **Execution bridge**: planner converts a suggestion → creates the target
   document **through the owning module's API/contract** (PO draft via
   purchasing, work order draft via manufacturing, transfer draft via
   inventory) with `suggestion_id` back-reference → suggestion
   `converted` → `supply_chain.suggestion.converted`.
4. **Multi-warehouse balancing**: nightly scan finds warehouses above
   max with siblings below reorder point → transfer suggestions.

### 6.4 Event catalog

`supply_chain.plan.created` / `.computed`,
`supply_chain.forecast.computed`,
`supply_chain.replenishment.computed` (run summary counts),
`supply_chain.suggestion.created` / `.converted` / `.dismissed`,
`supply_chain.stockout_risk.detected` (projected stockout inside lead
time — ops alert).

### 6.5 API surface (/api/v1/supply-chain)

| Endpoint | Permission |
|----------|-----------|
| CRUD demand plans, forecast lines | `supply_chain:plan:view|create|update` |
| CRUD replenishment policies | `supply_chain:policy:configure` |
| POST /replenishment-runs, GET runs/suggestions | `supply_chain:run:view|execute` |
| POST /suggestions/{id}:convert, :dismiss | `supply_chain:suggestion:convert` |
| GET stockout-risk view | `supply_chain:risk:view` |

Scopes: `supply_chain:read`, `supply_chain:write`, fine
`supply_chain:suggestion:convert`.

Contracts: consumes `ReorderSuggestions` (inventory),
`PurchaseOrderLookup`/`OpenPurchaseExposure` (purchasing),
`BomLookup` (manufacturing), `StockAvailability` (inventory),
`SupplierLeadTimeQuery` (suppliers), `SalesHistoryQuery`
(analytics read model, [intelligence](../../17-ai/intelligence.md)). Publishes suggestions consumed via its own
API (execution modules do not depend on this module — direction preserved).

### 6.6 Integration points

Consumes contracts/events: inventory, purchasing, suppliers, manufacturing,
sales, analytics. Publishes to: purchasing/manufacturing/inventory (via
their APIs, initiated here), notifications (stockout risk), analytics.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| supply_chain.* | 10¹–10² (run summaries) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `replenishment_suggestion` | supply_chain | convert (creates documents in other modules) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| supply_chain | `ReorderSuggestions` (inventory), `OpenPurchaseExposure` (purchasing), `BomLookup` (manufacturing), `SupplierLeadTimeQuery` (suppliers), `SalesHistoryQuery` (analytics) |
| supply_chain (Phase 4) | `SalesHistoryQuery` (analytics) — direction: Phase 4 consumes Phase 6's read contract; allowed because analytics is a read-only kernel-adjacent surface, and supply chain never depends on copilot/agents |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| supply_chain | `supply_chain:plan:view|create|update`, `supply_chain:policy:configure`, `supply_chain:run:view|execute`, `supply_chain:suggestion:convert`, `supply_chain:risk:view` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Replenishment execution**: converting a suggestion creates the right
    document type in the owning module with back-reference; dismissed
    suggestions never convert; stockout-risk detection fires on the fixture
    projection.
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
