# HR Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [enterprise ops domains §1](./hr.md); [ERP parity domains §5](./hr.md)

Employee master data, leave management with the balance-adjustment ledger, attendance, and lifecycle state machines ([enterprise ops domains §1](./hr.md)). Phase 4a added onboarding & offboarding checklists and the self-service surface ([ERP parity domains §5](./hr.md)). Module code `hr`. Payroll runs live in the payroll domain; expenses & reimbursements live in the expenses domain.

---

## Module: HR (module code `hr`)

### 1.1 Bounded context

Employee master (distinct from platform users — an employee is an HR record
that MAY be linked to a user), organizational positions, leave management,
attendance, and document custody. Recruitment/ATS is out of scope for v1.

### 1.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `employees` | id, employee_code (UQ per tenant, `EMP-<seq>`), user_id NULL (link to platform user), first/last name, status (`onboarding|active|on_leave|suspended|terminated`), joined_on, terminated_on NULL, org_id, designation_id, employment_type (`full_time|part_time|contract|intern`), email, phone, personal_data JSON (encrypted at rest — [security baseline §3](../../04-security/security-baseline.md)), bank_details_ciphertext, statutory_ids_ciphertext (PAN/UA etc.), reporting_manager_id NULL | soft delete prohibited (audit) ; status machine |
| `designations` | id, name, department_id, level | tenant-defined |
| `departments` | id, name, org_id, head_membership_id | maps onto org tree |
| `employee_documents` | id, employee_id, file_id (kernel Files), kind (`contract|id_proof|certificate|letter`), expires_at NULL, visibility (`hr_only|employee_visible`) | encrypted file area |
| `leave_types` | id, code (`annual|sick|casual|unpaid|maternity|...`), name, accrual_rule JSON (per month/year, carry-forward max), is_paid, requires_document, status | seeded + tenant-extensible |
| `leave_balances` | id, employee_id, leave_type_id, year, accrued, used, carried_forward, as_of | rebuilt by accrual job; adjustments ledger below |
| `leave_balance_adjustments` | id, balance_id, kind (`accrual|usage|carry|manual|reversal`), amount, ref (request id), occurred_at | append-only |
| `leave_requests` | id, employee_id, leave_type_id, from_date, to_date, half_day (bool), reason, status machine, approver_membership_id, document_file_id NULL | workflow-managed |
| `attendance_policies` | id, name, work_hours JSON, grace_minutes, overtime rules, geo_fence NULL | per org |
| `attendance_records` | id, employee_id, date, clock_in_at, clock_out_at, source (`web|pos_terminal|api|import`), status (`present|absent|half_day|holiday|leave`), hours_worked, meta | UQ (tenant, employee, date) |
| `holidays` | id, date, name, kind (`national|company|optional`) | per tenant calendar |

### 1.3 State machines

**Employee**: `onboarding → active → (on_leave ↔ active) → suspended ↔
active → terminated` (terminal). Every transition emits an event and is
audited; termination triggers payroll stop (consumed by payroll module) and
optionally membership suspension (kernel action, separate consent step —
HR record ≠ platform user).

**Leave request**: `draft → pending_approval → approved → cancelled?`,
`pending_approval → rejected`. Approval workflow with the employee's
reporting manager as default approver predicate (configurable approval
policy, [kernel services §4.3](../../11-workflow/workflow-engine.md)); multi-day requests above N days escalate (tenant
setting).

### 1.4 Primary flows

1. **Onboarding**: create employee (personal data encrypted on write) →
   documents upload → designation/org assignment → activate →
   `hr.employee.activated` → optional platform user invite (kernel invite
   API, recorded link).
2. **Leave lifecycle**: accrual job (monthly) posts `accrual` adjustments
   per leave_type rules → employee requests leave → workflow approval →
   `usage` adjustment in the same transaction as approval →
   `hr.leave.approved` → attendance calendar marks leave days.
3. **Attendance capture**: clock-in/out via web admin, POS terminals (doc
   08), or API (mobile later) → daily close job computes hours vs policy →
   `hr.attendance.closed`; anomalies (missing punch) surface in HR queue.
4. **Employee self-service**: employees linked to users see their own
   records, balances, and requests via `/api/v1/me/hr/*` (scope
   `kernel:profile:read`); no access to other employees' data.
5. **Termination**: workflow (notice period dates, full-and-final flag) →
   status terminated → `hr.employee.terminated` → payroll final settlement
   run ([enterprise ops domains §2](../payroll/payroll.md)), membership suspended by tenant admin action.

### 1.5 Event catalog

| Event | Payload highlights |
|-------|--------------------|
| `hr.employee.created` / `.activated` / `.suspended` / `.terminated` | employee_id, org_id, status |
| `hr.employee.updated` | employee_id, changed field names only (never values) |
| `hr.leave.requested` / `.approved` / `.rejected` / `.cancelled` | request_id, employee_id, type, dates |
| `hr.leave.accrued` | employee_id, type, amount, period |
| `hr.attendance.closed` | employee_id, date, status, hours |
| `hr.document.uploaded` / `.expired` | document id, kind (no file contents) |

Event payloads MUST NOT contain personal data values (names/ids of people
allowed; salary/health/bank data never).

### 1.6 API surface (/api/v1/hr)

| Endpoint | Permission |
|----------|-----------|
| GET /employees (filters: org, status, department), GET /employees/{id} | `hr:employee:view` (field filtering: bank/statutory fields only with `hr:employee:view_sensitive`) |
| POST /employees, PATCH /employees/{id} | `hr:employee:create` / `hr:employee:update` |
| POST /employees/{id}:activate, :suspend, :terminate | `hr:employee:lifecycle` (terminate sensitive, ACR 2) |
| CRUD documents | `hr:document:view|upload` (view_sensitive for hr_only docs) |
| GET/POST leave types, balances, adjustments | `hr:leave:view|configure` (configure sensitive) |
| POST /leave-requests, :approve, :reject, :cancel | `hr:leave:request` / `hr:leave:approve` |
| POST /attendance/clock-in, /clock-out; GET records | `hr:attendance:clock` / `hr:attendance:view` |
| GET /holidays, CRUD | `hr:holiday:view|configure` |
| Self-service: GET /me/hr/profile, balances, requests; POST /me/hr/leave-requests | `kernel:profile:read` (user scope) |

Scopes: `hr:read`, `hr:write`, fine `hr:employee:view_sensitive`,
`hr:leave:approve`, `hr:employee:lifecycle`.

Contracts: `EmployeeLookup` (id/code → summary DTO, no personal fields),
`EmployeeCostQuery` (designation-level cost center data for projects,
aggregated — never individual salary), `LeaveBalanceQuery` (self or HR).

### 1.7 Integration points

Consumes: kernel identity (user link), orgs (departments). Publishes to:
payroll (employee lifecycle + attendance), projects (timesheet validation),
notifications (leave approvals), analytics (headcount).

---
---

## HR extensions — onboarding/offboarding checklists (Phase 4a)

### 5.1 Scope

Onboarding/offboarding process automation layered on the existing
employee aggregate ([enterprise ops domains §1](./hr.md)). Recruitment/ATS, performance management,
scheduling, and loans remain deferred (planning DF-14…DF-17).

### 5.2 Onboarding & offboarding checklists (normative)

| Table | Key fields | Notes |
|-------|-----------|-------|
| `hr_checklist_templates` | id, code (UQ per tenant), name, kind (`onboarding|offboarding`), items JSON (ordered [{title, role, due_offset_days}]), status | reusable templates |
| `hr_checklist_instances` | id, template_code, employee_id, kind, status (`in_progress|completed|cancelled`), started_at, completed_at | one active instance per employee per kind |
| `hr_checklist_items` | id, instance_id, title, assigned_role (`hr|it|manager|employee`), due_on, status (`pending|done|skipped`), completed_by membership_id NULL, completed_at NULL, notes | |

Rules:
1. **Trigger**: `hr.employee.activated` ([enterprise ops domains §1.5](./hr.md)) auto-instantiates
   the tenant's active onboarding template; `hr.employee.terminated`
   instantiates offboarding. Instantiation is idempotent per
   (employee, kind, trigger event).
2. Items are workflow-managed tasks; `due_on` = trigger date +
   `due_offset_days`; overdue items surface in the HR queue and emit
   `hr.checklist.item_overdue` (debounced daily).
3. Completion: all items `done|skipped` ⇒ instance `completed` →
   `hr.checklist.completed`. Skip requires a note and the assigned
   role's permission.
4. Sensitive data: checklist items MUST NOT contain personal data
   ([enterprise ops domains §7.5](./hr.md)) — titles are role-action descriptions only.
5. Events: `hr.checklist.instantiated`, `hr.checklist.item_completed`,
   `hr.checklist.item_overdue`, `hr.checklist.completed`. Permissions:
   `hr:checklist:view|configure` (configure sensitive: template edits
   affect live processes), `hr:checklist:item_update`.

### 5.3 Self-service surface

Employees see their own checklist items via the existing
`/api/v1/me/hr/*` surface ([enterprise ops domains §1.6](./hr.md)) — `GET /me/hr/checklist` and
`POST /me/hr/checklist/items/{id}:complete` for items assigned to the
`employee` role.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| hr.* | 10²–10³ (attendance closure dominates: employees × days) |
| hr.checklist.* | 10¹–10² |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `employee` | hr | terminate |
| `leave_request` | hr | approve (multi-day escalation) |
| `hr_checklist` | hr | item skip |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| hr | `hr:employee:view|view_sensitive|create|update|lifecycle` (lifecycle terminate sensitive), `hr:document:view|upload`, `hr:leave:view|configure|request|approve` (configure sensitive), `hr:attendance:view|clock`, `hr:holiday:view|configure` |
| hr (extensions) | `hr:checklist:view|configure` (configure sensitive), `hr:checklist:item_update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Leave double-booking**: overlapping leave requests for the same
   employee — second approval blocked; balance cannot go negative
   (adjustment ledger invariant).
2. **Tenant isolation + sensitivity**: all tables covered by
    `AssertTenantIsolation`; HR/payroll sensitive fields verified absent
    from list endpoints without `view_sensitive`, from all events, and
    from structured logs.
3. **Checklists**: trigger-event double delivery instantiates once;
    item completion respects assigned role; overdue event debounced
    daily; item titles contain no personal data (fixture assertion).
4. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
