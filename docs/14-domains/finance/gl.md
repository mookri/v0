# General Ledger Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [finance domains §1](./gl.md), §9.5; [ERP parity domains §12.2](./gl.md), §12.4

Chart of accounts, journal entries, the normative double-entry posting engine, multi-currency model, and period close ([finance domains §1](./gl.md)). Phase 4a added cost centers & dimension validation and recurring journal entries ([ERP parity domains §12.2](./gl.md), §12.4). Module code `gl`. Profitability analysis ([ERP parity domains §12.3](./assets-budgets-reports.md)) lives in the assets, budgets & reports domain; dunning ([ERP parity domains §12.5](./ar-ap-banking.md)) lives in the AR/AP & banking domain.

---

## Module: GL — General Ledger (module code `gl`)

### 1.1 Bounded context

The ledger of record: chart of accounts, accounting periods, journals,
journal entries, the **posting engine** (event → journal templates), and
multi-currency translation.

### 1.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `chart_of_accounts` | id, code (UQ per tenant, hierarchical e.g. `4010`), name, type (`asset|liability|equity|revenue|expense`), subtype (e.g. `cash|receivable|inventory|fixed_asset|payable|tax|retained_earnings|sales|cogs|...`), parent_id, is_postable (leaf-only), status | tree; seeded from a template COA per country package |
| `fiscal_years` | id, name, starts_on, ends_on, status | tenant setting `tenant.fiscal_year_start` drives generation |
| `accounting_periods` | id, fiscal_year_id, number, starts_on, ends_on, status (`open|soft_closed|closed`) | contiguous, gap-free per fiscal year |
| `journals` | id, code (`SALES|PURCHASE|CASH|GENERAL|PAYROLL|ADJUSTMENT`), name, allowed entry types | grouping + control |
| `journal_entries` | id, number (`JE-<fy>-<seq>`), journal_id, entry_date, period_id, source_type/source_id (event/module doc), narration, status (`draft|posted|reversed`), posted_at, posted_by, reversed_by_entry_id NULL | posted = immutable |
| `journal_entry_lines` | id, entry_id, account_id, debit, credit, currency, fx_rate, base_debit, base_credit, org_id, dimension JSON (department/project/cost-center tags) | debit XOR credit > 0 per line |
| `posting_rules` | id, event_name (catalog ref), condition JSON, template JSON (line specs: account_expr, debit/credit expr, dimensions), priority, status | the event→journal mapping (§1.4) |
| `account_mappings` | id, key (`revenue:<category>`, `cogs:<category>`, `inventory`, `receivable`, `payable`, `giftcard_liability`, ...), account_id | indirection used by templates |
| `fx_rates` | id, from_ccy, to_ccy, rate DECIMAL(18,8), effective_on, source (`manual|feed`) | daily table |
| `revaluation_runs` | id, period_id, run_at, gains_losses JSON, entry_id | monetary revaluation output |
| `posting_errors` | id, source event ref, rule_id, reason code, status, retry_count | engine failure queue |

### 1.3 State machines

**Journal entry**: `draft → posted`, `posted → reversed` (by a linked
reversing entry; the original row status flips to `reversed` with
`reversed_by_entry_id`). No other transitions.

**Period**: `open → soft_closed → closed`.
- `soft_closed`: postings blocked for non-accountants; finance users with
  `gl:period:post_late` may still post late documents (late-post log).
- `closed`: no postings at all; reopening requires `gl:period:reopen`
  (sensitive, ACR 2, platform-audited) and emits `gl.period.reopened` as a
  security event.

### 1.4 Posting engine (normative)

The posting engine is the **only** writer of journal entries. It is
event-driven and deterministic:

1. Consumes declared events (from any module's catalog) matched against
   `posting_rules` by `event_name` (+ optional condition on payload fields).
2. For each matched rule (multiple may fire, ordered by priority), resolves
   the **template**:
   - `account_expr`: literal account code, or an `account_mappings` key
     resolved with payload context (e.g. revenue account per product
     category), or a contract lookup (e.g. customer's receivable account).
   - `amount_expr`: payload arithmetic in Money (e.g. total_minor,
     tax_minor, discount_minor fields of the payload).
   - `dimensions`: org (from event envelope), plus optional
     project/department from payload.
3. Builds the entry; validates balance per currency leg; applies FX
   translation to base currency using `fx_rates` at `entry_date`
   (missing rate → entry parked in `posting_errors` queue, never dropped).
4. Posts in one transaction with an outbox row for `gl.entry.posted`.
5. Idempotency: `UNIQUE (tenant_id, source_type, source_id, posting_rule_id)`
   — replayed events never double-post.
6. Failures (unknown account, unbalanced template, closed period) →
   `posting_errors` table with reason + retry action; alert after backlog
   threshold (`gl.posting.backlog_alerted`).

Seeded posting rules (v1 minimum set, per event):

| Source event | Journal template (D = debit, C = credit) |
|--------------|------------------------------------------|
| `invoicing.invoice.issued` | D receivable (customer) = total; C revenue (per category) = subtotal net of tax; C tax_payable = tax |
| `invoicing.credit_note.issued` | mirror of above (D revenue, D tax, C receivable) |
| `payments.payment.captured` (inbound) | D bank/cash account; C receivable |
| `payments.payment.applied` | no post — application is AR bookkeeping; capture already moved cash |
| `payments.payout.executed` | D payable; C bank |
| `payments.refund.issued` | D sales_refunds/expense; C bank/cash |
| `purchasing.receipt.recorded` | D inventory = accepted qty × cost; C accrual (GRNI) |
| `invoicing.bill.registered` (approved) | D expense/inventory (per line), D tax_recoverable; C payable; reverse GRNI portion |
| `inventory.stock.adjusted` | D/C inventory vs inventory_adjustment expense (by sign) |
| `inventory.valuation.changed` | revaluation difference entry (per costing run) |
| `giftcards.card.issued` | D cash/AR; C gift_card_liability |
| `giftcards.card.redeemed` | D gift_card_liability; C revenue |
| `subscriptions.renewal.billed` | D deferred_revenue; C revenue (recognized portion) |
| `checkout.order.placed` (deferred revenue flag) | D cash/AR; C deferred_revenue |
| `payroll.run.approved` ([enterprise ops domains](../hr/hr.md)) | D salary expense, D employer contributions; C payroll_payable, C tax_withholding |

Rules are tenant-editable (account mappings) but the **template structure
per event is versioned**; a template change is additive + audited
(`gl.rule.updated`).

### 1.5 Multi-currency model

- Every entry records **transaction currency** amounts and **base currency**
  equivalents (`base_debit/base_credit`) using the rate at entry_date.
- Tenant base currency is immutable after the first posted entry
  ([tenancy & data §3](../../06-tenancy/tenancy-and-data.md), [kernel services §9.2](../../07-kernel/kernel-services.md).5).
- **Period-end revaluation**: `gl:revalue` command revalues monetary
  foreign-currency balances (cash, AR, AP) at the period-end rate;
  differences post to `fx_gain_loss` accounts via a generated entry; the
  run is recorded in `revaluation_runs`.

### 1.6 Primary flows

1. **COA setup**: tenant provisioning seeds the country-package COA template
   → finance admin tailors accounts + account_mappings →
   `gl.coa.configured` (posting blocked until required mappings exist).
2. **Period management**: fiscal year generation (annual job creates the
   next FY + periods per tenant calendar) → month-end soft close → hard
   close with lock.
3. **Event posting**: as above; `posting_errors` queue visible in admin
   with one-click retry after fix.
4. **Manual journal**: `draft` entry via UI/API (mandatory narration,
   balanced) → approval workflow → posted (`gl:journal:approve`, sensitive;
   SoD `sod.journal_post_approve` — creator ≠ approver).
5. **Reversal**: select posted entry → reversal wizard → reversing entry
   (optionally dated first day of next period) → both linked.

### 1.7 Event catalog

| Event | Payload highlights |
|-------|--------------------|
| `gl.entry.posted` | entry_id, journal, date, totals, source refs |
| `gl.entry.reversed` | entry_id, reversing_entry_id |
| `gl.period.status_changed` | period_id, old/new status |
| `gl.period.reopened` | period_id, reopened_by (security event; ACR 2) |
| `gl.coa.configured` | required accounts + mappings ready (posting unblocked) |
| `gl.posting.error` | source event ref, reason code |
| `gl.posting.backlog_alerted` | backlog count, oldest error age (ops alert) |
| `gl.revaluation.completed` | period_id, net difference |
| `gl.rule.updated` / `gl.mapping.updated` | config diff |

### 1.8 API surface (/api/v1/gl)

| Endpoint | Permission |
|----------|-----------|
| GET accounts tree; GET /accounts/{id}/balance | `gl:account:view` |
| CRUD accounts (respecting postable/has-children rules) | `gl:account:configure` |
| GET periods; POST /periods/{id}:soft_close, :close, :reopen | `gl:period:view` / `gl:period:close` / `gl:period:reopen` (sensitive) |
| GET entries (+lines); POST /entries (draft); POST /entries/{id}:submit | `gl:journal:view` / `gl:journal:create` |
| POST /entries/{id}:approve; POST /entries/{id}:reverse | `gl:journal:approve` (sensitive) / `gl:journal:reverse` (sensitive) |
| GET/PUT posting rules, account mappings | `gl:rule:configure` (sensitive) |
| GET posting errors; POST /posting-errors/{id}:retry | `gl:posting_errors:view` / `gl:posting_errors:retry` |
| GET fx rates; POST /fx-rates | `gl:fx:view` / `gl:fx:configure` |
| POST /periods/{id}:revalue | `gl:revalue:execute` (sensitive) |

Scopes: `gl:read`, `gl:write`, plus the fine-grained sensitive scopes above.

Contracts published: `AccountLookup`, `GlPostingGate` (modules may ask
"is posting configured for my event?" for UI hints), `FxRateQuery`.

### 1.9 Integration points

Consumes events: invoicing, payments, purchasing, inventory, giftcards,
subscriptions, checkout, payroll ([enterprise ops domains](../hr/hr.md)). Consumes contracts:
`ReceivablesAging`, `PayablesAging`, `InventoryValuation`. Publishes to:
reports, analytics, notifications (posting errors).

---
---

## Cost centers & dimensions (Phase 4a)

Phase 4a finance extensions; profitability ([ERP parity domains §12.3](./assets-budgets-reports.md)) and dunning ([ERP parity domains §12.5](./ar-ap-banking.md)) are owned by the assets-budgets-reports and AR/AP domains respectively.

### 12.2 Cost centers & dimensions (normative)

1. `finance_cost_centers`: `code` (UQ per tenant), `name`, `org_id`
   NULL (default org binding), `status`. `finance_dimension_rules`:
   account selector (account code range/type), `required_dimensions`
   JSON (`cost_center`, `project`, custom keys), `status`.
2. **Posting validation**: the posting engine ([finance domains §1.4](./gl.md)) validates
   every line against `finance_dimension_rules`; a required dimension
   missing/invalid ⇒ the entry is rejected into `posting_errors`
   ([finance domains §1.2](./gl.md)) with reason `dimension_missing` — never posted
   partially.
3. Manual journals ([finance domains §1.6](./gl.md)) expose dimension entry; the Blade
   console validates before submit.
4. **Cost-center reporting**: finance_reports ([finance domains §8](./assets-budgets-reports.md)) gains the
   `cost_center_trial_balance` definition — trial balance sliced by
   cost-center dimension, same mechanics (queued, org-scoped).
5. Events: `gl.cost_center.created` / `.updated`. Permissions:
   `gl:cost_center:view|configure` (configure sensitive).
---

## Recurring journal entries (Phase 4a)

### 12.4 Recurring journal entries (normative)

1. `gl_recurring_templates`: `name`, `journal_id`, `frequency`
   (`weekly|monthly|quarterly|yearly`), `anchor_day`, `line_template
   JSON` (account expressions per [finance domains §1.4](./gl.md) template grammar),
   `status`, `last_run_period NULL`.
2. Scheduler generates a **draft** journal entry per active template per
   period, idempotent per (template_id, period key) — double scheduler
   runs never double-generate (unique constraint).
3. Generated entries flow through the normal manual-journal approval
   workflow ([finance domains §1.3](./gl.md) + SoD `sod.journal_post_approve`); the engine
   never auto-posts.
4. Skips (missing account mapping, closed period) land in
   `posting_errors` + `gl.recurring.skipped` event.
5. Events: `gl.recurring.generated`, `gl.recurring.skipped`.
   Permissions: `gl:recurring:view|create|update|execute` (execute =
   run-now, sensitive).
---

## Tenancy mode (Phase 3)

All Phase 3 tables are **row-level tenant-scoped** (`tenant_id` +
`org_id` where applicable) on the tenant data connection per [tenancy & data](../../06-tenancy/tenancy-and-data.md).
Journal entries, ledger entries, statements, statements lines and schedules
are append-only; config tables (COA, tax codes, budgets) are soft-deletable
only when unreferenced.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| gl.* (entries posted) | 10⁴ (1–5 entries per commercial event) |
| gl/ar finance extensions | 10¹–10² (recurring + dunning runs) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `journal_entry` | gl | approve, reverse (SoD creator ≠ approver) |
| `accounting_period` | gl | reopen |
| `recurring_journal` | gl | post (SoD) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| gl (posting templates) | `AccountLookup` (own), `CustomerLookup` (customers), `SupplierLookup` (suppliers) |
| gl (manual journals) | `BudgetCheck` (budgets) |
| finance extensions | posting engine (gl), aging (ar), report definitions (finance_reports) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| gl | `gl:account:view|configure`, `gl:period:view|close|reopen` (reopen sensitive), `gl:journal:view|create|approve|reverse` (approve/reverse sensitive), `gl:rule:configure` (sensitive), `gl:posting_errors:view|retry`, `gl:fx:view|configure`, `gl:revalue:execute` (sensitive), `gl:period:post_late` |
| gl (extensions) | `gl:cost_center:view|configure` (configure sensitive), `gl:recurring:view|create|update|execute` (execute sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Double-entry invariant**: property test — randomized valid event
   sequences through the posting engine produce balanced entries per
   currency leg; an intentionally broken template is rejected at validation
   time (never posted).
2. **Idempotent posting**: every Phase 1–2 source event replayed 3× produces
   exactly one journal entry per rule (unique constraint test).
3. **Period lock**: posting attempts into a closed period fail with
   `period_closed`; reopen requires ACR 2 and emits the security event.
4. **Reversal integrity**: reversing entry mirrors every line (debit↔credit)
   and links both directions; reversed entry cannot be reversed again.
5. **SoD in finance**: journal creator ≠ approver enforced; AP run approver
   with vendor-master edit history in scope is blocked.
6. **Cost centers**: posting with missing required dimension lands in
    `posting_errors` (never partial post); cost-center trial balance
    equals full trial balance when sliced+summed across all centers.
7. **Recurring journals**: double scheduler run generates one draft;
    generated drafts require normal approval (SoD creator ≠ approver);
    skip cases recorded with reason.
8. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
9. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
