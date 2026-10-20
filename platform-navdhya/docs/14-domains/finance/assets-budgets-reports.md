# Assets, Budgets & Financial Reports Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [finance domains §6](./assets-budgets-reports.md)–8; [ERP parity domains §12.3](./assets-budgets-reports.md)

Fixed assets with depreciation and disposal ([finance domains §6](./assets-budgets-reports.md)), budgets with the budget-check contract ([finance domains §7](./assets-budgets-reports.md)), and the v1 financial report inventory with normative mechanics ([finance domains §8](./assets-budgets-reports.md)). Phase 4a added profitability analysis ([ERP parity domains §12.3](./assets-budgets-reports.md)). Module codes `assets`, `budgets`, `finance_reports`.

---

## Module: Fixed Assets (module code `assets`)

### 6.1 Bounded context

Asset register, capitalization from purchasing, depreciation schedules and
runs, disposals, and asset lifecycle.

### 6.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `fixed_assets` | id, code (`AST-<seq>`), name, category_id, status (`draft|capitalized|disposed|written_off`), capitalized_on, cost (Money), salvage_value, useful_life_months, method (`straight_line|declining_balance`), location/org, bill_id NULL (source), gl_account_id (asset), depreciation_account_id, accumulated_depreciation (cached) | |
| `asset_categories` | id, name, default life/method/accounts | seeded defaults |
| `depreciation_schedules` | id, asset_id, period (month), amount, posted_entry_id NULL, status | generated at capitalization; regenerated on revaluation |
| `depreciation_runs` | id, period_id, run_at, status, entry_id | monthly batch |
| `asset_disposals` | id, asset_id, disposed_on, proceeds, gain_loss, entry_id | |

### 6.3 Primary flows

1. **Capitalization**: from an approved bill with asset flag (purchasing/
   invoicing) or manual → draft → `assets.asset.capitalized` → schedule
   generated; GL posts D asset account / C clearing (bill already credited
   payable).
2. **Monthly depreciation**: run (`assets:depreciation:execute`) posts
   schedule lines for the period → GL entry D depreciation expense /
   C accumulated depreciation → `assets.depreciation.posted`.
3. **Disposal**: sell/scrap → compute NBV → gain/loss → GL entry → asset
   `disposed` → `assets.asset.disposed`.
4. **Revaluation/impairment**: adjust cost basis (sensitive, audited) →
   schedule regenerated prospectively.

### 6.4 Event catalog

`assets.asset.capitalized` / `.disposed` / `.written_off`,
`assets.depreciation.posted` (run summary), `assets.revaluation.applied`.

### 6.5 API surface (/api/v1/assets)

CRUD assets/categories under `assets:asset:view|create|update`,
`assets:category:configure`; `POST /assets/{id}:capitalize`,
`POST /assets/{id}/disposals` (`assets:disposal:create`, sensitive);
`POST /depreciation-runs` (`assets:depreciation:execute`, sensitive);
schedules read `assets:schedule:view`. Scopes: `assets:read`,
`assets:write`, fine sensitive scopes.

Contracts: `AssetRegisterQuery` (reports).

### 6.6 Integration points

Consumes: invoicing (bills with asset flag), purchasing. Publishes to: gl
(capitalization, depreciation, disposal entries), analytics.

---
---

## Module: Budgets (module code `budgets`)

### 7.1 Bounded context

Budget planning per fiscal year × account × org (dimension), budget vs
actual comparison, and soft enforcement on purchasing/journal creation.

### 7.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `budgets` | id, fiscal_year_id, name, status (`draft|submitted|approved|locked`), prepared_by | |
| `budget_lines` | id, budget_id, account_id, org_id, period_number NULL (NULL = annual), amount | unique (budget, account, org, period) |
| `budget_controls` | id, tenant setting per account group: `enforcement` (`none|warn|block`), tolerance_pct | soft-control config |
| `budget_vs_actual_snapshots` | id, as_of, fiscal_year_id, rollup JSON | nightly job |

### 7.3 Primary flows

1. **Preparation**: draft budget (from template of prior year + growth %) →
   submit → approval workflow (`budgets:budget:approve`, sensitive) →
   locked.
2. **Consumption check**: contract `BudgetCheck` called by purchasing
   (PO submit) and gl (manual journal on expense accounts): returns
   `ok|warn|blocked` with remaining budget; `block` fails the
   transition with `budget_exceeded` error code, `warn` proceeds
   with an audit warning + notification to budget owners.
3. **Reporting**: snapshot job computes budget vs actual per account/org from
   GL balances → `budgets.snapshot.computed`.

### 7.4 Event catalog

`budgets.budget.created` / `.approved` / `.locked`,
`budgets.limit.exceeded` (blocked attempt — security/ops surface),
`budgets.snapshot.computed`.

### 7.5 API surface (/api/v1/budgets)

CRUD budgets/lines under `budgets:budget:view|create|update`;
`POST /budgets/{id}:submit|:approve` (approve sensitive);
`GET /budgets/compare?...` under `budgets:report:view`; control config
under `budgets:control:configure`. Scopes: `budgets:read`,
`budgets:write`, fine sensitive.

Contracts: `BudgetCheck` (purchasing, gl).

### 7.6 Integration points

Consumes: gl (actuals). Consumed by: purchasing, gl (manual journals).
Publishes to: notifications (warnings), analytics.

---
---

## Module: Financial Reports (module code `finance_reports`)

### 8.1 Bounded context

Standard financial statements and regulatory exports computed from GL +
sub-ledgers. Reports are **read-only projections**; the module owns no
authoritative money state.

### 8.2 Report inventory (v1)

| Report | Code | Source | Permission |
|--------|------|--------|-----------|
| Trial Balance | `trial_balance` | GL balances by account/period | `finance_reports:report:view` |
| Profit & Loss | `profit_and_loss` | revenue/expense accounts, period or YTD, org dimension | same |
| Balance Sheet | `balance_sheet` | asset/liability/equity at date | same |
| Cash Flow (indirect) | `cash_flow` | GL movements on cash + working-capital deltas | same |
| AR Aging | `ar_aging` | ar snapshots + contract | `ar:aging:view` |
| AP Aging | `ap_aging` | ap snapshots | `ap:account:view` |
| Customer Ledger / Statement | `customer_ledger` | ar entries | `ar:account:view` |
| Supplier Ledger | `supplier_ledger` | ap entries | `ap:account:view` |
| Tax Summary / Return Data | `tax_summary` | tax module contract | `tax:return:view` |
| Fixed Asset Schedule | `asset_schedule` | assets module contract | `assets:schedule:view` |
| Budget vs Actual | `budget_variance` | budgets snapshots | `budgets:report:view` |
| Journal Register | `journal_register` | journal entries by period/journal | `gl:journal:view` |

### 8.3 Mechanics (normative)

1. Every report request is parameterized by (period/date range, org scope,
   currency view) and **respects org scoping** ([authorization & policy §4.2](../../04-security/authorization-and-policy.md)) — a report
   never crosses the actor's org visibility.
2. Computation runs against the tenant connection with read intent; heavy
   reports (balance sheet across years) run as queued jobs producing a Files
   artifact (CSV/XLSX/PDF) rather than inline rendering; job status via
   standard resource.
3. Report outputs carry generation metadata: as_of timestamp, period
   closure state (a report over a soft-closed/closed period is stamped),
   actor watermark on exports ([kernel services §5.4](../../07-kernel/kernel-services.md)).
4. Report **definitions** (which accounts roll into which line) are
   tenant-configurable per report code via `report_line_definitions`
   (seeded defaults per country package); changes audited.
5. Exports are sensitive (`finance_reports:report:export`) and audited —
   bulk financial data is PII-adjacent for compliance ([security baseline §6](../../04-security/security-baseline.md)).

### 8.4 Event catalog

`finance_reports.report.generated` (code, params hash, actor — audit
telemetry), `finance_reports.definition.updated`.

### 8.5 API surface (/api/v1/finance-reports)

| Endpoint | Permission |
|----------|-----------|
| POST /reports/{code} (render or enqueue), GET /reports/{code}/results/{id} | `finance_reports:report:view` |
| GET /definitions, PUT /definitions/{code} | `finance_reports:definition:configure` (sensitive) |
| Export (file artifact) | `finance_reports:report:export` (sensitive) |

Scopes: `finance_reports:read`, `finance_reports:write`.

Contracts: consumes `AccountLookup` (gl), `ReceivablesAging` (ar),
`PayablesAging` (ap), `TaxSummaryQuery` (tax), `AssetRegisterQuery`
(assets).

---
---

## Profitability analysis (Phase 4a)

Profitability analysis was specified within the Phase 4a finance extensions ([ERP parity domains §12](./gl.md)); it is a read model over GL dimensions owned by the finance_reports module.

### 12.3 Profitability analysis (normative)

1. Profitability is a **read-model** in analytics ([intelligence §1](../../16-analytics/analytics.md)) built from
   posted journal lines (gl events): revenue accounts vs COGS vs
   directly-attributed expense lines, sliced by dimension
   (org / cost center / project / customer via `dimension` refs).
2. Project profitability (ERP-135) uses the project dimension populated
   by project billing postings ([enterprise ops domains §3](../projects/projects.md)); customer profitability uses
   the customer ref on AR revenue lines.
3. Margin thresholds per view (tenant config) emit
   `finance_reports.margin.below_threshold` (debounced per period +
   slice) — feeds anomaly detection ([intelligence §3](../../17-ai/intelligence.md)).
4. Contract published: `ProfitabilityQuery` (analytics-owned; consumed
   by dashboards and copilot tooling within effective authority).
   Permissions: `finance_reports:report:view` (existing).
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| assets.* | 10¹–10² |
| budgets.* | 10¹ |
| finance_reports.* | telemetry only |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `budget` | budgets | approve |
| `asset_disposal` | assets | approve |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| finance_reports | `AccountLookup` (gl), `ReceivablesAging` (ar), `PayablesAging` (ap), `TaxSummaryQuery` (tax), `AssetRegisterQuery` (assets) |
| finance extensions | posting engine (gl), aging (ar), report definitions (finance_reports) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| assets | `assets:asset:view|create|update`, `assets:category:configure`, `assets:disposal:create` (sensitive), `assets:depreciation:execute` (sensitive), `assets:schedule:view` |
| budgets | `budgets:budget:view|create|update|approve` (approve sensitive), `budgets:report:view`, `budgets:control:configure` (sensitive) |
| finance_reports | `finance_reports:report:view|export` (export sensitive), `finance_reports:definition:configure` (sensitive) |
| finance_reports (extensions) | `finance_reports:schedule:view|create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Report accuracy fixtures**: seeded golden dataset (known invoices,
   payments, receipts) produces exact trial balance / P&L / balance sheet
   figures (regression suite).
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
