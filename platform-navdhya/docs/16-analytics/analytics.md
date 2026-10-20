# Analytics Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [intelligence §1](./analytics.md); [ERP parity domains §13.2](./analytics.md)

The read-model platform: event-driven projections of module state into denormalized, query-optimized tables on the analytics connection, plus the KPI engine, dashboards, exports ([intelligence §1](./analytics.md)), and scheduled report dispatch ([ERP parity domains §13.2](./analytics.md)). Analytics is the sanctioned cross-module read surface — modules never join across schemas for reads; they consume projections and the published read contracts (`SalesHistoryQuery`, `KpiSnapshotQuery`, `FunnelMetricsQuery`). Module code `analytics`. Forecasting, anomaly detection, recommendations, copilot, and agents live in the [intelligence register](../17-ai/intelligence.md).

---

## Module: Analytics (module code `analytics`)

### 1.1 Bounded context

The read-model platform: event-driven projections of module state into
denormalized, query-optimized tables on a **read connection**, plus the KPI
engine, dashboards, and exports. This is the sanctioned cross-module read
surface ([tenancy & data §5.3](../06-tenancy/tenancy-and-data.md) exception).

### 1.2 Read-path architecture (normative)

```text
domain writes ──commit──▶ outbox ──relay──▶ analytics projector workers
                                              │ (per-domain projectors)
                                              ▼
                              read replica / analytics connection
                              (materialized projection tables)
                                              │
                              ┌───────────────┼───────────────┐
                              ▼               ▼               ▼
                          dashboards     forecasting     copilot tools
```

1. **Event-sourced projections**: projector workers consume the event
   stream ([kernel services §3](../12-events/events-and-outbox.md) relay topics) and maintain projection tables.
   Projections are **eventual** (lag target ≤ 30 s p95) and **rebuildable**:
   every projection has a full-rebuild command from source-of-truth
   contracts (`platform:analytics-rebuild --domain=<x>`).
2. **Connection isolation**: projections live on the analytics connection
   (read replica in prod; same DB different connection in small deploys).
   The copilot/forecasting/recommendation code paths MUST use this
   connection only — architecture test enforces no writes from
   Intelligence modules except to their own bookkeeping tables.
3. **Tenant isolation is inherited**: projection tables carry `tenant_id`
   and the kernel global scopes apply; the `AssertTenantIsolation`
   suite covers projections too ([tenancy & data §11](../06-tenancy/tenancy-and-data.md)).
4. **Projection tables (v1 set)**:
   | Table | Source events | Grain |
   |-------|---------------|-------|
   | `fact_sales_daily` | sales.*, checkout.order.placed | tenant × org × day × variant × channel |
   | `fact_revenue_daily` | invoicing.invoice.issued/paid, credit notes | tenant × org × day × account category |
   | `fact_inventory_daily` | inventory.* movements | tenant × warehouse × variant × day (closing balance) |
   | `fact_purchases_daily` | purchasing.* | tenant × supplier × day |
   | `fact_cash_daily` | payments.*, banking.position.computed | tenant × account × day |
   | `dim_customers_snapshot` | customers.* | customer attributes + lifetime value rollup |
   | `fact_funnel_daily` | checkout.cart.*, checkout.order.placed | tenant × store × day (carts → checkouts → orders) |
   | `fact_service_daily` | service.* | tenant × queue × day (created/resolved/breach counts) |
   | `fact_workforce_daily` | hr.attendance.closed, payroll.run.closed | tenant × org × day (headcount, hours, cost aggregate) |
5. **History retention**: projections retain 36 months online; older data
   archives to object storage (queryable via queued artifact reports only).

### 1.3 KPI engine & dashboards

- **KPI definitions** (`kpi_definitions`): code, name, formula type
  (`sum|count|ratio|delta_pct`), source fact, dimensions, period
  granularity, target values per tenant (optional), comparison basis
  (previous period / same period last year / target).
- **Dashboards** (`dashboards` + `dashboard_widgets`): Velzon
  admin pages composed of widgets (line/bar/funnel/table/KPI card) bound to
  KPI queries; per-role default dashboards seeded (owner, manager,
  accountant); tenants can clone + customize (sharing org/tenant).
- **Query governance**: widget queries compile to bounded SQL against fact
  tables only (no ad-hoc joins to OLTP); max range 36 months; results
  cached in Redis per (tenant, kpi, params) with 60 s TTL; expensive
  queries (> 5 s estimated) run as queued artifacts.
- **Drill-down**: every widget value links to the underlying list endpoint
  of the owning module (respecting its permissions) — dashboards never
  bypass module authorization for details.

### 1.4 Contracts published (consumed by other modules)

| Contract | Consumer | Content |
|----------|----------|---------|
| `SalesHistoryQuery` | supply_chain ([enterprise ops domains §6](../14-domains/supply-chain/supply-chain.md)), forecasting | daily sales by variant/org/channel with 36-month depth |
| `KpiSnapshotQuery` | copilot (§5), notifications (insight digests) | latest KPI values + deltas |
| `FunnelMetricsQuery` | store module dashboards | cart → order conversion by store/period |

### 1.5 Event catalog

`analytics.projection.rebuilt` (domain, duration),
`analytics.kpi.threshold_breached` (KPI code, tenant, direction,
value — drives insight notifications), `analytics.dashboard.shared`,
`analytics.export.generated` (audit).

### 1.6 API surface (/api/v1/analytics)

| Endpoint | Permission |
|----------|-----------|
| GET /kpis, GET /kpis/{code}/values (params: period, dims, compare) | `analytics:kpi:view` |
| CRUD KPI targets | `analytics:kpi:configure` |
| GET /dashboards, CRUD widgets, clone/share | `analytics:dashboard:view|create|update`, share = `analytics:dashboard:share` |
| POST /exports (queued artifact), GET /exports/{id} | `analytics:export:create` (sensitive — bulk data) |
| GET /funnel, /cohorts (store metrics) | `analytics:funnel:view` |

Scopes: `analytics:read`, `analytics:write`, fine sensitive
`analytics:export:create`. Org scoping applies to every query
([authorization & policy §4.2](../04-security/authorization-and-policy.md)).

### 1.7 Integration points

Consumes: all domain events (read-only). Publishes to: notifications
(insight digests), copilot tools, forecasting/anomaly inputs.

---
---

## Scheduled report dispatch (Phase 4a)

Scheduled reports were specified within the Phase 4a operational extensions ([ERP parity domains §13](./analytics.md)); they are owned by the analytics surface because dispatch fans out through the analytics/report queued-artifact path with org-scoped artifacts.

1. `report_schedules`: `id`, `report_ref` (finance_reports definition
   or analytics KPI ref), `recipients JSON` (membership ids),
   `frequency` (`daily|weekly|monthly`), `anchor_day NULL`, `format`
   (`pdf|xlsx|csv`), `filters JSON`, `status`, `last_sent_at NULL`.
2. Scheduler generates the artifact through the owning report's queued
   path ([finance domains §8.3](../14-domains/finance/assets-budgets-reports.md)), then dispatches via notifications with a
   tenant-scoped signed file URL ([kernel services §7](../07-kernel/kernel-services.md)); delivery logged in
   `notifications_log` with campaign tag `scheduled_report`.
3. **Org-scoped fan-out (normative)**: the report executes once per
   distinct recipient org-scope set — a recipient never receives rows
   beyond their org visibility ([authorization & policy §4.2](../04-security/authorization-and-policy.md)). Architecture test:
   crafted schedule with mixed-scope recipients produces per-scope
   artifacts.
4. Schedule creation requires the underlying report's `view`
   permission; recipients are validated to hold it too (422
   `report_schedule_recipient_unauthorized` otherwise).
5. Events: `finance_reports.schedule.sent`,
   `finance_reports.schedule.failed`. Permissions:
   `finance_reports:schedule:view|create|update`.
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| analytics.* (ops telemetry) | 10¹ |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `kpi_threshold_alert` | analytics | acknowledge (operational) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| analytics | all domain events (read-only projections) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| analytics | `analytics:kpi:view|configure`, `analytics:dashboard:view|create|update|share`, `analytics:export:create` (sensitive), `analytics:funnel:view` |
| finance_reports (extensions) | `finance_reports:schedule:view|create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../04-security/authorization-and-policy.md) register and [ADR-0002](../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Projection integrity**: for each fact table, a reconciliation test
   compares projection aggregates against contract-sourced recomputation on
   a fixture dataset (tolerance: exact for Money/counts).
2. **Rebuild idempotency**: `platform:analytics-rebuild` twice →
   identical tables; rebuild during live traffic converges (event catch-up).
3. **Tenant isolation**: all tables + projections covered by
    `AssertTenantIsolation`; copilot conversations of tenant A are
    invisible to tenant B admins even with crafted conversation ids.
4. **Scheduled reports**: mixed-scope recipients receive per-scope
    artifacts (no cross-org rows); recipient without report permission
    rejected at schedule creation; delivery logged.
5. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../19-testing/testing-strategy.md).
