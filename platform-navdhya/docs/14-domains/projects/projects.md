# Projects Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [enterprise ops domains §3](./projects.md)

Projects, tasks, timesheets, project expenses, budget thresholds, and the billing run that turns approved timesheets into invoices ([enterprise ops domains §3](./projects.md)). Module code `projects`. Billable expense lines from the expenses module enter via `ExpenseBillableQuery` ([ERP parity domains §6](../hr/expenses.md)).

---

## Module: Projects (module code `projects`)

### 3.1 Bounded context

Client and internal projects: work breakdown (tasks/milestones), resource
assignment, time capture (timesheets), expenses, project budgets, and
**project billing** (time-and-materials and fixed-fee) that produces
invoices through the invoicing module.

### 3.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `projects` | id, code (`PRJ-<seq>`), name, customer_id NULL (shadow), status machine (`draft|active|on_hold|completed|cancelled`), billing_type (`time_materials|fixed_fee|non_billable`), budget_hours, budget_amount Money, start_date, end_date, org_id, manager_membership_id | soft delete on draft only |
| `project_milestones` | id, project_id, name, due_date, status, billable_amount Money NULL (fixed-fee) | fixed-fee billing anchors |
| `project_tasks` | id, project_id, milestone_id NULL, parent_id NULL, title, status (`todo|in_progress|blocked|done`), assignee_membership_id NULL, estimated_hours, due_date, priority | tree |
| `task_comments` | id, task_id, body, membership_id | |
| `timesheets` | id, project_id, task_id NULL, membership_id NULL, employee_id, date, hours, billable (bool), rate Money NULL, status machine, description | UQ (tenant, employee, date, task) |
| `project_expenses` | id, project_id, employee_id, expense_date, category_id, amount Money, billable, receipt_file_id NULL, status machine | |
| `expense_categories` | id, code, name, billable_default | seeded + tenant |
| `project_rates` | id, project_id NULL (NULL = tenant default), role_id NULL, employee_id NULL, rate Money, effective_from | rate resolution: employee > role > project default > tenant default |
| `billing_runs` | id, project_id, number (`PB-<seq>`), period_from/to, status machine, unbilled_hours, unbilled_expenses, invoice_id NULL | workflow-managed |
| `billing_run_lines` | id, run_id, kind (`time|expense|milestone`), ref_id, qty, rate, amount | what gets invoiced |

### 3.3 State machines

- **Project**: `draft → active → (on_hold ↔ active) → completed`,
  `active → cancelled`. Completion requires all tasks done OR explicit
  close; emits `projects.project.completed`.
- **Timesheet**: `draft → submitted → approved → billed`, plus
  `rejected` (back to draft). Approval workflow (default: project
  manager predicate). Once `approved`, a timesheet can be included in a
  billing run; `billed` is terminal and immutable (corrections = new
  adjusting timesheet, negative, with reason).
- **Expense**: same shape as timesheet (draft → submitted → approved →
  billed / rejected).
- **Billing run**: `draft → pending_approval → approved → invoiced → closed`;
  approval sensitive above amount threshold; `invoiced` links the
  created invoice id.

### 3.4 Primary flows

1. **Time capture**: employee (self-service `/me/projects/timesheets`)
   or manager enters hours → submit → approve → `projects.timesheet.approved`.
   Validation: date within an open payroll/attendance period, hours ≤ policy
   max/day, employee linked and active (HR contract).
2. **Rate resolution on approval**: line rate resolved from
   `project_rates` at timesheet date; stored on the timesheet (snapshot —
   later rate edits do not rewrite approved history).
3. **Billing (time & materials)**: create billing run for period → engine
   selects approved unbilled timesheets + expenses → line generation →
   approval → create invoice via invoicing contract ([commercial core domains §9](../finance/invoicing.md)) with
   `source_type=project_billing_run` → mark lines `billed` →
   `projects.billing.invoiced` (GL posts revenue via the normal invoice
   rule, [finance domains §1.4](../finance/gl.md)).
4. **Billing (fixed fee)**: milestone completed → billing run of kind
   `milestone` → same approval → invoice.
5. **Budget tracking**: nightly snapshot compares actual (approved hours ×
   rate + expenses) vs `budget_hours`/`budget_amount` →
   `projects.budget.threshold_breached` alert at 80%/100% (tenant settings).

### 3.5 Event catalog

`projects.project.created` / `.activated` / `.completed` /
`.cancelled`, `projects.task.updated` (status),
`projects.timesheet.approved` / `.rejected`,
`projects.expense.approved`, `projects.billing.approved` /
`.invoiced`, `projects.budget.threshold_breached`.

### 3.6 API surface (/api/v1/projects)

| Endpoint | Permission |
|----------|-----------|
| GET/POST projects, PATCH | `projects:project:view|create|update` |
| Lifecycle verbs (:activate, :hold, :complete, :cancel) | `projects:project:lifecycle` |
| CRUD milestones/tasks | `projects:task:view|create|update` |
| GET/POST timesheets, :submit, :approve, :reject | `projects:timesheet:view|create`, `projects:timesheet:approve` |
| GET/POST expenses, same verbs | `projects:expense:view|create`, `projects:expense:approve` |
| Rate tables CRUD | `projects:rate:configure` (sensitive) |
| Billing runs CRUD + :approve | `projects:billing:view|create`, `projects:billing:approve` (sensitive above threshold) |
| Self-service: GET /me/projects, POST /me/projects/timesheets | `kernel:profile:read` |

Scopes: `projects:read`, `projects:write`, fine sensitive scopes.

Contracts: `ProjectLookup` (summary DTO), `UnbilledProjectRevenue`
(finance/analytics).

### 3.7 Integration points

Consumes: hr (employee validation via `EmployeeLookup`), customers
(shadow), invoicing (invoice creation contract). Publishes to: invoicing
(events), gl (via invoice posting), analytics.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| projects.* | 10²–10³ (timesheets dominate) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `timesheet` / `project_expense` | projects | approve |
| `project_billing_run` | projects | approve (amount threshold) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| projects | `EmployeeLookup` (hr), `CustomerLookup` (customers), invoice creation (invoicing) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| projects | `projects:project:view|create|update|lifecycle`, `projects:task:view|create|update`, `projects:timesheet:view|create|approve`, `projects:expense:view|create|approve`, `projects:rate:configure` (sensitive), `projects:billing:view|create|approve` (approve sensitive above threshold) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Timesheet → billing integrity**: billing run includes each approved
   timesheet exactly once; billed timesheets reject re-approval or edits.
2. **Project budget alerts**: fixture dataset crossing 80%/100% emits
   threshold events exactly once per crossing.
3. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
4. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
