# Expenses & Reimbursements Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [ERP parity domains §6](./expenses.md)

Expense reports with receipt requirements, approval SoD, reimbursements executed through payments payout batches, billable charges into project billing, and GL posting per category mapping ([ERP parity domains §6](./expenses.md)). Module code `expenses`. Employee master data lives in the HR domain.

---

## Module: Expenses & Reimbursements (module code `expenses`)

### 6.1 Bounded context

Employee expense capture, approval, reimbursement, and GL posting.
Consumes `hr` (employee identity), `payments` (payouts), `invoicing`
(supplier bills for corporate cards are out of scope), and posts through
the GL posting engine ([finance domains §1.4](../finance/gl.md)). Travel claims use the same model
(trip = expense report context).

### 6.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `expense_categories` | id, code (UQ per tenant), name, gl_account_key (maps to `account_mappings`), requires_receipt (bool), status | seeded + tenant-defined |
| `expense_reports` | id, number (`EXP-<yyyy>-<seq>`), employee_id, org_id, status machine (`draft|submitted|pending_approval|approved|rejected|reimbursed`), trip_ref JSON NULL (purpose, from/to dates), currency, totals JSON, submitted_at NULL | |
| `expense_report_lines` | id, report_id, category_id, occurred_on, amount (Money), tax_amount (Money) NULL, description, receipt_file_id NULL, billable (bool), customer_id NULL (billable re-charge) | |
| `expense_approvals` | id, report_id, approver_membership_id, decision (`approved|rejected`), note, decided_at | SoD: approver ≠ submitter |
| `expense_reimbursements` | id, report_id, payout_ref (payments module), amount (Money), status (`pending|paid|failed`), paid_at NULL | |

### 6.3 State machine & flows (normative)

1. **Draft → submit**: employee (or HR on their behalf) adds lines;
   receipts required where `requires_receipt=true` (missing ⇒ 422
   `expenses_receipt_required`); `:submit` freezes lines.
2. **Approval**: workflow approval with the employee's reporting manager
   as default predicate ([kernel services §4.3](../../11-workflow/workflow-engine.md)); amount above tenant threshold
   escalates (second approver). SoD: submitter cannot approve own report
   ([authorization & policy §7.1](../../04-security/authorization-and-policy.md) approval_chain). Rejection returns to `draft` with note.
3. **Reimbursement**: approved report → `expense_reimbursements` →
   payments payout batch (`payments.payout.*`, [commercial core domains §8.4](../finance/payments.md) flow 2);
   `payments.payout.executed` marks `reimbursed`.
4. **Billable re-charge**: lines with `billable=true` + customer_id
   become project-billing inputs via the projects billing run
   ([enterprise ops domains §3.4](../projects/projects.md)) — the projects module consumes
   `expenses.report.approved` and reads billable lines through the
   `ExpenseBillableQuery` contract published here.
5. **GL posting**: `expenses.report.reimbursed` posts via posting rules —
   debit expense category account, credit payout/bank account; billable
   amounts post to a receivable-suspense account until re-invoiced.

### 6.4 Event catalog

`expenses.report.created` / `.submitted` / `.approved` / `.rejected` /
`.reimbursed`, `expenses.approval.requested`, `expenses.line.flagged`
(policy violation, e.g. duplicate receipt hash).

### 6.5 API surface (`/api/v1/expenses`)

| Endpoint | Permission |
|----------|-----------|
| CRUD categories | `expenses:category:configure` |
| CRUD reports + `:submit`, `:approve`, `:reject` | `expenses:report:view|create|update`, `expenses:report:approve` (approve sensitive above threshold) |
| `GET /reports/{id}/receipts`, upload | `expenses:report:update` (Files) |
| `POST /reports/{id}:reimburse` | `expenses:reimbursement:execute` (sensitive; routes through payments SoD) |
| Self-service: `GET|POST /me/expenses...` | employee own-report scope (`kernel:profile:read` pattern, [enterprise ops domains §1.6](./hr.md)) |

Scopes: `expenses:read`, `expenses:write`, fine-grained sensitive scopes.
Contracts published: `ExpenseBillableQuery` (projects).

### 6.6 Integration points

Consumes: hr (`EmployeeLookup`), payments (payouts), Files (receipts).
Publishes to: gl (posting engine), projects (billable lines),
notifications (approval requests), analytics.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| expenses.* | 10² (report lines dominate) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `expense_report` | expenses | approve, reimburse |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| expenses | `EmployeeLookup` (hr), payout batches (payments), **`ExpenseBillableQuery`** (own; consumed by projects) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| expenses | `expenses:category:configure`, `expenses:report:view|create|update|approve` (approve sensitive above threshold), `expenses:reimbursement:execute` (sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Expenses**: missing receipt on required category ⇒ 422;
    self-approval ⇒ 403 `sod_conflict`; reimbursement executes through
    payments payout (SoD applies); billable lines appear in exactly one
    project billing run; GL postings balanced per category mapping.
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
