# Workflow Engine

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 05 §4; doc 04 §15 (delegation); pasted plan (delegation/escalation/compensation)

The workflow engine provides declarative state machines and approval policies reused by every module with governed lifecycles (sales approval, purchasing, journal posting, payroll runs, returns, expense claims). This register migrates the engine definition (formerly kernel-services §4) and adds the delegation, escalation, and compensation rules required by the parity phases.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| WF-001 | [WF-001](#wf-001-purpose--scope) | Declarative state machines + approval policies |
| WF-002 | [WF-002](#wf-002-state-machine-definition) | PHP-defined machines registered with engine |
| WF-003 | [WF-003](#wf-003-transition-exclusivity) | Transitions are the only status-change path |
| WF-004 | [WF-004](#wf-004-guards) | Module-provided guard callables |
| WF-005 | [WF-005](#wf-005-hooks--transaction-semantics) | Hooks run inside the transition transaction |
| WF-006 | [WF-006](#wf-006-workflow-instance-bookkeeping) | workflow_instances ledger, bounded history |
| WF-007 | [WF-007](#wf-007-approval-policies) | Predicates decide who must approve |
| WF-008 | [WF-008](#wf-008-approver-resolution--sod) | Role/permission predicate + SoD at decision time |
| WF-009 | [WF-009](#wf-009-multi-step-approval-chains) | Ordered levels, each must be satisfied |
| WF-010 | [WF-010](#wf-010-timeouts--expiry-no-silent-auto-approve) | expires_at → expired + notification |
| WF-011 | [WF-011](#wf-011-delegation-of-approvals) | Time-boxed, SoD-checked delegation (SEC-023) |
| WF-012 | [WF-012](#wf-012-escalation) | Unhandled/expired requests escalate |
| WF-013 | [WF-013](#wf-013-compensation) | Saga compensation for T3/T4 flows |
| WF-014 | [WF-014](#wf-014-engine-event-catalog) | platform.workflow.* events |
| WF-015 | [WF-015](#wf-015-workflow-authorization) | Transition capability checks |
| WF-016 | [WF-016](#wf-016-workflow-verification) | Standing workflow suites |

---

## WF-001 Purpose & scope

- id: WF-001
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.1
- depends_on: CON-005
- affects: DF-013

### Purpose

Declarative state machines + approval policies, reused by every module that
has governed lifecycles (sales approval, purchasing, journal posting,
payroll runs, commerce returns, expense claims).

## WF-002 State machine definition

- id: WF-002
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.2
- depends_on: WF-001
- affects: WF-003

### State machine definition

Defined in PHP (module `Domain/StateMachines/`), registered with the engine:

```text
StateMachineDefinition::for('sales_order')
  ->state('draft')            // initial
  ->state('pending_approval')
  ->state('confirmed')
  ->state('fulfilled')
  ->state('cancelled')
  ->transition('submit',   'draft',            'pending_approval', guard: 'has_lines', hook: 'reserve_inventory')
  ->transition('approve',  'pending_approval', 'confirmed',        guard: 'approval_granted', sensitive: true)
  ->transition('reject',   'pending_approval', 'draft')
  ->transition('fulfill',  'confirmed',        'fulfilled')
  ->transition('cancel',   ['draft','confirmed'], 'cancelled',     guard: 'cancellable')
```

Rules:
1. Transitions are the **only** way to change a workflow-managed status
   column; direct status writes are forbidden (architecture test scans for
   raw updates on `status` columns outside engine calls).
2. Guards are module-provided callables evaluated by the engine; a failed
   guard returns `workflow_guard_failed` (403/422 with guard code).
3. Hooks run inside the same transaction as the status change + outbox
   write; hook failures roll back the whole transition.
4. Every successful transition emits a module event
   (`sales.order.approved` etc.) and an audit entry with from/to states.
5. `workflow_instances` (tenant): `id`, `subject_type`, `subject_id`,
   `machine`, `current_state`, `history JSON` (bounded, last 50 steps),
   `updated_at` — the engine's bookkeeping; modules keep their own status
   column mirrored for queryability.

## WF-003 Transition exclusivity

- id: WF-003
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.2 rule 1
- depends_on: WF-002
- affects: ARCH-018

Transitions are the only way to change a workflow-managed status column; direct status writes are forbidden. An architecture test scans for raw updates on `status` columns outside engine calls. This makes lifecycle state machine-readable and auditable end to end.

## WF-004 Guards

- id: WF-004
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.2 rule 2
- depends_on: WF-002
- affects: WF-003

Guards are module-provided callables evaluated by the engine before a transition fires. A failed guard returns `workflow_guard_failed` (403/422 with the guard code) and the state does not change. Guards are pure checks; they MUST NOT mutate state.

## WF-005 Hooks & transaction semantics

- id: WF-005
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.2 rules 3–4
- depends_on: WF-002
- affects: EV-007

Hooks run inside the same transaction as the status change + outbox write; hook failures roll back the whole transition. Every successful transition emits a module event and an audit entry with from/to states. This gives workflow transitions T1 transaction character (DF-013).

## WF-006 Workflow instance bookkeeping

- id: WF-006
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.2 rule 5
- depends_on: WF-002
- affects: DATA-009

`workflow_instances` (tenant) records `subject_type`, `subject_id`, `machine`, `current_state`, and a bounded `history JSON` (last 50 steps). It is the engine's bookkeeping; modules keep their own status column mirrored for queryability.

## WF-007 Approval policies

- id: WF-007
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.3
- depends_on: WF-002, SEC-007
- affects: WF-008

### Approval policies

`ApprovalPolicy` objects decide *who* must approve a transition flagged
`requires_approval`:

- Predicates over the subject: amount thresholds, org, module, record type
  (e.g. `amount_minor > 50000000 AND currency=INR → approvers with
  payments:payout:approve, level 2`).
- Approver resolution: role/permission predicate + SoD rule ([authorization & policy §7.1](../04-security/authorization-and-policy.md)):
  approver ≠ submitter, evaluated at decision time.
- Multi-step chains: policy may return ordered levels; each level must be
  satisfied before the transition fires.
- `approval_requests` (tenant): `id`, `workflow_instance_id`, `level`,
  `subject summary`, `requested_by`, `status` (`pending|approved|rejected|
  expired`), `decided_by`, `decided_at`, `comment`, `expires_at`.
- Approvals are sensitive actions (ACR 2 where the underlying permission is
  sensitive) and produce notifications to pending approvers + audit entries.
- Timeouts: policies may set `expires_at`; expiry → `expired` + notification
  + workflow stays in waiting state (no silent auto-approve ever).

## WF-008 Approver resolution & SoD

- id: WF-008
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.3
- depends_on: WF-007, SEC-011
- affects: SEC-022

Approver resolution uses a role/permission predicate plus an SoD rule (approver ≠ submitter), evaluated at decision time. Approvals are sensitive actions (ACR 2 where the underlying permission is sensitive) and produce notifications to pending approvers plus audit entries.

## WF-009 Multi-step approval chains

- id: WF-009
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.3
- depends_on: WF-007
- affects: WF-010

An approval policy may return ordered levels; each level must be satisfied before the transition fires. `approval_requests` (tenant) tracks `workflow_instance_id`, `level`, `requested_by`, `status` (pending|approved|rejected|expired), `decided_by`, `decided_at`, `comment`, and `expires_at`.

## WF-010 Timeouts & expiry (no silent auto-approve)

- id: WF-010
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.3
- depends_on: WF-009
- affects: WF-012

Policies may set `expires_at`; expiry marks the request `expired`, notifies, and leaves the workflow in its waiting state. There is never a silent auto-approve. Expiry emits `platform.workflow.approval_expired`.

## WF-011 Delegation of approvals

- id: WF-011
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §15; LD-16
- depends_on: SEC-023, WF-008
- affects: WF-008

A pending approval may be handled by a delegatee holding a valid delegation grant (SEC-023). Delegations are time-boxed, revocable, carry the grantor's scope restrictions, and are SoD-checked at assignment and at use — including the approver≠submitter constraint. A delegated approval records both the delegatee (actor) and the delegation id in the audit entry.

## WF-012 Escalation

- id: WF-012
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted plan (delegation/escalation/compensation)
- depends_on: WF-010
- affects: WF-010

When an approval request is unhandled past a configured threshold or expires, the engine escalates it to a fallback approver set declared by the policy (e.g. the next management-hierarchy ancestor, or a named role). Escalation re-issues the request at a new level, notifies, and is audited; escalation never widens authority beyond the fallback approvers' existing grants.

## WF-013 Compensation

- id: WF-013
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: pasted plan; DF-013 T3/T4
- depends_on: DF-013, EV-008
- affects: DF-015

Long-running and cross-module flows (T3/T4) declare compensation paths: for each forward step, a named compensating action that restores the prior state on failure. The workflow engine orchestrates compensation ordering; compensation steps are idempotent, audited, and emit events. A flow without a declared compensation path cannot be classified T3/T4.

## WF-014 Engine event catalog

- id: WF-014
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §4.4
- depends_on: EV-013
- affects: EV-013

### Engine event catalog

`platform.workflow.transitioned`, `platform.workflow.approval_requested`,
`platform.workflow.approved`, `platform.workflow.rejected`,
`platform.workflow.approval_expired` (all carry subject + machine + states).

---

## WF-015 Workflow authorization

- id: WF-015
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §8; SEC-009
- depends_on: SEC-009, WF-002
- affects: EXP-010

Every transition is authorized as a capability check before the engine evaluates guards: the actor must hold the capability for the transition's action on the subject resource, within their working context. Sensitive transitions require step-up. Denials are logged in the authorization decision log (SEC-024).

## WF-016 Workflow verification

- id: WF-016
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 14 §8
- depends_on: WF-001
- affects: 19-testing

Standing suites: (a) transition exclusivity (no raw status writes); (b) guard failure leaves state unchanged; (c) approval SoD (submitter cannot approve); (d) expiry never auto-approves; (e) delegation re-check at use; (f) compensation restores prior state for a failed T3 flow. Each maps to a named test in the testing strategy.
