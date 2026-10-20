# Service Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [enterprise ops domains §4](./service.md)

Support tickets, queues, SLA clocks with business-hours calendars, and the ticket state machine ([enterprise ops domains §4](./service.md)). Module code `service`. Warranty coverage lookups ([ERP parity domains §10](../sales/contracts.md)) consume inventory `SerialQuery`; the warranty coverage contract itself lives in the contracts domain.

---

## Module: Service (module code `service`)

### 4.1 Bounded context

Customer support/helpdesk: tickets, queues, SLA policies, escalation, and
knowledge-base stubs. Tickets may reference orders/shipments (via
contracts) and may spawn returns (RMA hand-off).

### 4.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `service_queues` | id, code, name, org_id, default_assignee_group, notification_channel | |
| `sla_policies` | id, name, conditions JSON (priority, category, customer tier), first_response_minutes, resolution_minutes, business_hours_calendar_id, status | evaluated at ticket creation |
| `business_hours_calendars` | id, name, weekly_windows JSON, holiday_calendar_id | SLA clock runs only inside windows |
| `tickets` | id, number (`TKT-<seq>`), subject, description, customer_id NULL (shadow), contact_id NULL, queue_id, priority (`low|normal|high|urgent`), category_id, status machine, source (`email|web|api|pos`), sla_policy_id NULL, first_responded_at NULL, resolved_at NULL, due_first_response_at NULL, due_resolution_at NULL, satisfaction_score NULL | |
| `ticket_messages` | id, ticket_id, author_type (`agent|customer|system`), body, attachments JSON (file ids), visibility (`public|internal`) | |
| `ticket_assignments` | id, ticket_id, membership_id NULL (unassign allowed), assigned_by, assigned_at | history |
| `ticket_categories` | id, name, parent_id, default_queue_id | tree |
| `sla_breaches` | id, ticket_id, kind (`first_response|resolution`), breached_at | append-only |
| `knowledge_articles` | id, title, body, category_id, status, visibility (`internal|public`) | v1 stub |

### 4.3 State machine (ticket)

`new → open → (pending_customer ↔ open) → resolved → closed`, plus
`open → escalated` (returns to open after escalation action), and
reopen: `closed → open` (within reopen window, tenant setting).

- On `new`: SLA policy matched by conditions → due timestamps computed
  from business-hours calendar → `service.ticket.created`.
- SLA clock: scheduler job (per-minute granularity) evaluates due timestamps
  against business hours; breach → `sla_breaches` row +
  `service.sla.breached` + notification to queue + priority auto-bump
  (optional setting).
- First agent reply (public message) sets `first_responded_at` →
  `service.ticket.first_responded`.

### 4.4 Primary flows

1. **Intake**: email adapter (Phase 5) / web form / API → ticket created in
   queue → auto-assignment rules (round-robin or skill match; v1 ships
   round-robin) → `service.ticket.created`.
2. **Agent handling**: assign → internal notes + public replies → resolve
   (resolution note mandatory) → `service.ticket.resolved` → customer
   satisfaction request (notification) → auto-close after N days unless
   reopened.
3. **Escalation**: manual or SLA-driven → escalation target (queue or
   membership) notified → `service.ticket.escalated`.
4. **Order-linked tickets**: agent links order/shipment id (lookup via
   `SalesOrderLookup`/`ShipmentLookup` contracts); if the
   resolution is a return, agent initiates an RMA via returns contract
   (`ReturnEligibility`, [commerce domains §5](../commerce/promotions-shipping-returns.md)).

### 4.5 Event catalog

`service.ticket.created` / `.assigned` / `.first_responded` /
`.escalated` / `.resolved` / `.closed` / `.reopened`,
`service.sla.breached`, `service.satisfaction.received`.

### 4.6 API surface (/api/v1/service)

| Endpoint | Permission |
|----------|-----------|
| GET /tickets (filters), GET /tickets/{id} (+messages) | `service:ticket:view` |
| POST /tickets | `service:ticket:create` |
| PATCH /tickets/{id}, assign/unassign | `service:ticket:update` |
| POST /tickets/{id}/messages | `service:ticket:reply` |
| Verbs :resolve, :reopen, :escalate, :close | `service:ticket:update` |
| CRUD queues, categories, SLA policies, business hours | `service:queue:configure`, `service:sla:configure` (both sensitive for SLA) |
| Knowledge articles CRUD | `service:kb:view|create|update` |
| Public: POST /api/v1/public/support/tickets, GET /tickets/{number}?email= | kernel public marker, throttled, verified by customer email |

Scopes: `service:read`, `service:write`, fine sensitive scopes.

Contracts: `TicketStatusQuery` (customers module "recent tickets" view).

### 4.7 Integration points

Consumes: customers (shadow), sales/shipping lookups, returns
(`ReturnEligibility`), notifications. Publishes to: analytics (CSAT,
SLA adherence), notifications.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| service.* | 10³ (tickets + messages) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `ticket` | service | escalate (operational) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| service | `SalesOrderLookup` (sales), `ShipmentLookup` (shipping), `ReturnEligibility` (returns), `CustomerLookup` (customers) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| service | `service:ticket:view|create|update|reply`, `service:queue:configure`, `service:sla:configure` (sensitive), `service:kb:view|create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **SLA clock**: ticket with a business-hours calendar spanning weekends
   computes correct due timestamps; breach fires exactly once per SLA kind.
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
