# Payroll Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [enterprise ops domains §2](./payroll.md)

Payroll structures, the deterministic run state machine, statutory computation hooks, payslip immutability, and payout via the payments module ([enterprise ops domains §2](./payroll.md)). Module code `payroll`. PF/ESI/PT statutory filing exports ([ERP parity domains §9.4](../../15-integrations/connectors/india-statutory.md)) live under integrations connectors; employee master data lives in the HR domain.

---

## Module: Payroll (module code `payroll`)

### 2.1 Bounded context

Salary structures, payroll runs (periodic batch), statutory deductions
(tax withholding, social contributions), payslips, and final settlements.
Payroll is **batch-first**: every mutation happens inside a run with an
explicit approval gate; there is no ad-hoc salary payment path.

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `salary_structures` | id, employee_id, effective_from, currency, components JSON ([{code, kind (`earning|deduction|employer_contribution`), type (`fixed|pct_of_basic|statutory`), amount_or_rate}]), status | versioned per employee; change = new row |
| `payroll_calendars` | id, name, frequency (`monthly|biweekly`), pay_day, period_start_rule | per tenant |
| `payroll_runs` | id, number (`PR-<yyyy>-<seq>`), calendar_id, period_start/end, status machine, gross_total, deductions_total, net_total, currency, approved_by, paid_at | workflow-managed |
| `payroll_run_lines` | id, run_id, employee_id, inputs JSON (attendance days, leave days, overtime hours), earnings JSON, deductions JSON, employer_contributions JSON, gross, net, status (`computed|adjusted|final`) | per employee |
| `payroll_adjustments` | id, run_line_id, kind (`earning|deduction`), code, amount, reason, created_by | pre-final manual corrections |
| `statutory_rules` | id, jurisdiction_code, code (`income_tax|pf|esi|professional_tax|...`), slabs JSON, effective_from/to | country-package seeded; tenant-editable rates within package |
| `payslips` | id, run_line_id, file_id (kernel Files), issued_at, status | generated at finalize; immutable PDF |
| `final_settlements` | id, employee_id, run_id, components JSON (notice pay, leave encashment, deductions), status | termination workflow output |

### 2.3 Run state machine

```text
draft → computing → computed → pending_approval → approved → paid → closed
computed → draft (recompute after adjustments)
any pre-approval → cancelled
```

- `computing`: engine iterates active employees (status `active` or
  `on_leave` per policy), pulls attendance/leave inputs (HR contracts),
  applies salary structure + statutory rules; deterministic — same inputs ⇒
  same outputs (rounding: half-up per component, per-line).
- `pending_approval → approved`: `payroll:run:approve`, sensitive,
  ACR 2, SoD `sod.payroll_run_approve` — approver MUST differ from the
  membership that created/adjusted the run.
- `approved → paid`: creates a payments payout batch ([commercial core domains §8](../finance/payments.md)) for net
  amounts; on `payments.payout.executed` → run `paid`; GL posting fires
  via `payroll.run.approved` ([finance domains §1.4](../finance/gl.md) seeded rule) at approval.
- `closed`: payslips issued, run immutable.

### 2.4 Primary flows

1. **Monthly run**: calendar triggers draft (scheduler) → compute → review
   UI (variance vs last run flagged > threshold %) → adjustments → approve
   → payout batch → paid → close → payslips emailed (kernel notifications,
   per-employee encrypted file links) → `payroll.run.closed`.
2. **Salary revision**: new salary structure row effective-dated; mid-period
   changes prorate by days in the run's computation.
3. **Statutory computation**: per-jurisdiction rules compute withholding and
   employer contributions; rule changes are effective-dated and never
   rewrite history; a run always records the rule versions used
   (`statutory_rule_versions JSON` on the run).
4. **Final settlement**: termination workflow creates settlement → computes
   outstanding components → included as a special line in the next run (or
   an off-cycle run `payroll:run:create_offcycle`).
5. **Recompute guard**: recomputing after approval is impossible; errors are
   fixed via adjustments pre-approval or an off-cycle correction run
   post-close (audited, reason mandatory).

### 2.5 Event catalog

`payroll.run.created` / `.computed` / `.approved` / `.paid` /
`.closed` / `.cancelled`, `payroll.payslip.issued` (employee_id,
run id — no amounts), `payroll.structure.changed` (employee_id, effective
date — no amounts), `payroll.settlement.completed`,
`payroll.variance.flagged` (ops alert payload: run id, variance %).

Event payloads MUST NOT contain salary amounts or employee personal data
beyond ids (same rule as HR §1.5).

### 2.6 API surface (/api/v1/payroll)

| Endpoint | Permission |
|----------|-----------|
| GET structures; POST /employees/{id}/structures | `payroll:structure:view` / `payroll:structure:configure` (sensitive) |
| GET runs, GET /runs/{id} (+lines) | `payroll:run:view` |
| POST /runs (create draft), POST /runs/{id}:compute, :recompute | `payroll:run:create` |
| POST /runs/{id}:approve | `payroll:run:approve` (sensitive, SoD) |
| POST /runs/{id}:close; payslip re-issue | `payroll:run:close` (sensitive) |
| CRUD adjustments (pre-approval only) | `payroll:adjustment:create` |
| GET statutory rules; PUT rates | `payroll:statutory:view|configure` (configure sensitive) |
| Self-service: GET /me/payslips, GET /me/payslips/{id} (file) | `kernel:profile:read` |
| POST /final-settlements | `payroll:settlement:create` (sensitive) |

Scopes: `payroll:read`, `payroll:write`, fine sensitive scopes above.

Contracts: `PayrollCostSummary` (aggregated cost by org/department for
finance dashboards — never per-employee outside payroll).

### 2.7 Integration points

Consumes: hr (employees, attendance, leave via contracts), payments (payout
batches), gl (posting via events), notifications (payslips). Publishes to:
gl, analytics (aggregated labor cost).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| payroll.* | 10¹–10² (per-run events; per-employee data stays in tables) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `payroll_run` | payroll | approve (SoD run-creator ≠ approver), close |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| payroll | `EmployeeLookup` (hr), payout batches (payments) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| payroll | `payroll:structure:view|configure` (configure sensitive), `payroll:run:view|create`, `payroll:run:approve` (sensitive, SoD), `payroll:run:close` (sensitive), `payroll:adjustment:create`, `payroll:statutory:view|configure` (configure sensitive), `payroll:settlement:create` (sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Payroll determinism**: same inputs twice → byte-identical run lines;
   variance vs previous run > threshold flags but does not block.
2. **Payroll SoD**: run creator attempting `:approve` → 403
   `sod_conflict`; ACR 2 enforced on approve/close.
3. **Payslip immutability**: closed run — payslip regeneration denied;
   file content hash stable.
4. **Tenant isolation + sensitivity**: all tables covered by
    `AssertTenantIsolation`; HR/payroll sensitive fields verified absent
    from list endpoints without `view_sensitive`, from all events, and
    from structured logs.
5. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
