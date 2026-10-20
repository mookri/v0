# CRM Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [ERP parity domains §2](./crm.md)

Leads, opportunities with per-pipeline state machines, activities on the shared timeline, campaigns & attribution, and the event-built Customer 360 read model ([ERP parity domains §2](./crm.md)). Module code `crm`. Lead conversion creates customers exclusively through the `CustomerCreator` contract — CRM never writes customers tables directly ([ERP parity domains §1](./crm.md), planning [LD-10](../../21-adrs/ADR-0011-crm-read-models-from-events.md)). Quotes are drafted via `QuoteDraftFactory` owned by the sales domain.

---

## Module: CRM (module code `crm`)

### 2.1 Bounded context

The sales-process layer in front of the commercial core: leads,
opportunities, pipelines, activities, campaigns, and the customer-360
read model. The customers module ([commercial core domains §2](./customers.md)) remains the counterparty
master; sales quotes/orders ([commercial core domains §5](../sales/sales.md)) remain the document of record.
CRM owns **process state** (qualification, stage, activity), not
counterparty or document truth.

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `crm_leads` | id, number (`LEAD-<seq>`), name, company_name, email, phone, source (`campaign|web|referral|manual|import`), campaign_id NULL, status machine (`new|contacted|qualified|converted|discarded`), customer_id NULL (post-conversion), opportunity_id NULL, owner_membership_id, score INT, meta | soft delete |
| `crm_opportunities` | id, number (`OPP-<seq>`), name, customer_id NULL, lead_id NULL, pipeline_id, stage_id, amount (Money), probability_pct, expected_close_on, owner_membership_id, status (`open|won|lost`), lost_reason NULL, campaign_id NULL, closed_at NULL | |
| `crm_opportunity_contacts` | id, opportunity_id, contact_id (customers contacts), role (`decision_maker|influencer|buyer|user|coach`), is_primary | UQ (opportunity_id, contact_id); contact roles on the deal |
| `crm_pipelines` | id, code (UQ per tenant), name, status | |
| `crm_stages` | id, pipeline_id, code, name, position, probability_default, entry_action JSON NULL | ordered per pipeline |
| `crm_activities` | id, subject_type (`lead|opportunity|customer|ticket|project`), subject_id, kind (`call|meeting|task|note|email`), body, due_at NULL, completed_at NULL, owner_membership_id | polymorphic timeline (§2.5) |
| `crm_campaigns` | id, code, name, kind (`email|event|ads|referral|other`), starts_at/ends_at, budget (Money) NULL, status | |
| `crm_customer_360_views` | customer_id (UQ), orders_summary JSON, opportunities_summary JSON, tickets_summary JSON, credit_summary JSON, last_activity_at, refreshed_at | **eventual read model** (§2.9) |

### 2.3 Pipeline state machine (workflow engine)

Opportunity progression is a workflow state machine **per pipeline**:
stages are states (ordered by `position`), transitions are
`crm:opportunity:update`-guarded. Terminal states: `won`, `lost`.

- Stage entry actions (`entry_action` JSON, declarative): create activity
  task, notify owner, set probability. Actions run as workflow hooks
  ([kernel services §4.2](../../11-workflow/workflow-engine.md)); a failing hook rolls back the transition.
- `won` requires `customer_id` set and emits `crm.opportunity.won`
  (feeds forecasting, [intelligence §2](../../17-ai/intelligence.md)); `lost` requires `lost_reason`.
- Reopening a lost opportunity (`crm:opportunity:update` + mandatory
  reason) is audited and emits `crm.opportunity.reopened`.

### 2.4 Lead state machine

`new → contacted → qualified → converted`, plus `discarded` from any
non-terminal state (mandatory reason). `:convert` is the only path to
`converted` (§2.7).

### 2.5 Activities (shared timeline, normative)

Activities are the platform's polymorphic collaboration record:

1. Any module with a timeline (CRM leads/opportunities, customers,
   service tickets, projects) is a valid `subject_type`; the registry of
   subject types is kernel-validated against module codes.
2. Activities are append + complete only (no edits after completion;
   corrections = new activity referencing the old id).
3. Every activity write emits `crm.activity.logged` (subject ref, kind,
   actor) — feeds customer 360 and analytics.
4. Activity reads inherit the subject resource's `view` permission —
   the timeline cannot leak a record the actor cannot see.

### 2.6 Event catalog

`crm.lead.created` / `.updated` / `.qualified` / `.converted` /
`.discarded`, `crm.opportunity.created` / `.stage_changed` / `.won` /
`.lost` / `.reopened`, `crm.activity.logged`,
`crm.campaign.created` / `.updated`, `crm.customer_360.refreshed`.

### 2.7 Lead conversion flow (normative)

`POST /api/v1/crm/leads/{id}:convert` (`crm:lead:convert`, idempotent):

1. Guard: lead is `qualified`; duplicate conversion replays the stored
   result (idempotency, [API platform §5](../../07-kernel/api-platform.md)).
2. **Customer resolution**: if a customer matches by email or tax_id
   (via `CustomerLookup`), link it; otherwise create one through the
   customers module's `CustomerCreator` contract (new additive contract
   published by `customers` in this phase). CRM never inserts into
   `customers` tables directly (architecture-tested).
3. **Opportunity**: when `with_opportunity=true`, create an opportunity
   in the requested pipeline/stage carrying `lead_id`, `campaign_id`,
   amount estimate.
4. **Quote**: when `with_quote=true`, create a draft quote through the
   sales module's `QuoteDraftFactory` contract (new additive contract
   published by `sales` in this phase) pre-filled from the lead's item
   list payload.
5. Emit `crm.lead.converted` (customer_id, opportunity_id, quote_id);
   activities on the lead are copied by reference onto the opportunity
   timeline (no row duplication — subject re-pointing).

### 2.8 Campaigns & attribution

- Leads carry `campaign_id`; conversion snapshots attribution onto the
  opportunity; quotes/orders created from that opportunity carry
  `source=crm` + `source_ref=<opportunity_id>` ([commercial core domains §5.2](../sales/sales.md) `source`
  column is the existing seam).
- Campaign effectiveness (spend vs won value) is computed in analytics
  ([intelligence §1](../../16-analytics/analytics.md)) from `crm.*` + `sales.*` events — not in CRM tables.

### 2.9 Customer 360 (normative)

`crm_customer_360_views` is an eventual read model ([LD-10](../../21-adrs/ADR-0011-crm-read-models-from-events.md)):

1. Refresh listeners consume `customers.*`, `sales.order.*`,
   `service.ticket.*`, `crm.opportunity.*`, `crm.activity.logged` and
   contract reads (`ReceivablesAging` from ar, [finance domains §3](../finance/ar-ap-banking.md)).
2. Refresh is debounced (max once per 60 s per customer) and idempotent;
   `refreshed_at` exposes staleness; the API returns `stale: true` when
   older than 15 min.
3. The read model MUST NOT be built with cross-module joins — the
   architecture test asserts the listener's query surface.
4. Read permission: `crm:insight:view` **and** `customers:customer:view`
   (both required).

### 2.10 API surface (`/api/v1/crm`)

| Endpoint | Permission |
|----------|-----------|
| CRUD leads + `:qualify`, `:convert`, `:discard` | `crm:lead:view|create|update`, `crm:lead:convert` |
| CRUD opportunities + `:stage`, `:won`, `:lost`, `:reopen` | `crm:opportunity:view|create|update` |
| Pipelines/stages config | `crm:pipeline:configure` (sensitive: stage changes re-route live deals) |
| Activities CRUD + `:complete` | `crm:activity:view|create|update` |
| Campaigns CRUD | `crm:campaign:view|create|update` |
| `GET /customers/{id}/360` | `crm:insight:view` + `customers:customer:view` |

Scopes: `crm:read`, `crm:write`, fine-grained `crm:pipeline:configure`.

Contracts published: `PipelineSummaryQuery` (analytics/forecasting).
Contracts consumed: `CustomerLookup` + `CustomerCreator` (customers),
`QuoteDraftFactory` (sales), `ReceivablesAging` (ar).

### 2.11 Integration points

Consumes: customers, sales (quotes via contract; order events), service
(ticket events), ar (aging contract). Publishes to: forecasting
(pipeline-weighted forecast, [intelligence §2](../../17-ai/intelligence.md)), analytics, notifications
(stage-entry actions).

---
---

## ERP parity — normative positioning

1. **Customers stay the counterparty master.** CRM converts leads into
   `customers` records through a contract published by the customers
   module — it never writes customers tables directly ([domain framework §5](../../02-architecture/architecture-master.md)).
2. **Statutory is adapters, not core.** The India statutory package is a
   feature-flagged set of channel adapters + report exporters; the tax
   engine ([finance domains §2](../finance/tax.md)) stays jurisdiction-generic (planning [LD-07](../../21-adrs/ADR-0008-statutory-channel-adapters.md)).
3. **Tracking lives in inventory.** Serial and batch state belongs to the
   inventory movement model; other modules consume via contracts
   (planning [LD-08](../../21-adrs/ADR-0009-serial-lot-inventory-contracts.md)).
4. **Quality gates are declared, not hard-coded.** Quality blocks flow
   only at declared inspection gates via the workflow engine
   (planning [LD-09](../../21-adrs/ADR-0010-quality-event-driven-gates.md)).
5. **CRM read-models are event-built.** Customer 360 and pipeline
   analytics refresh from events + contracts, never cross-module joins
   (planning [LD-10](../../21-adrs/ADR-0011-crm-read-models-from-events.md)).
6. **One numbering registry.** All document numbering extends the
   invoicing numbering pattern ([commercial core domains §9.6](../finance/invoicing.md)) into a single registry
   (§12.3).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| crm.* | 10²–10³ (activities dominate) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `crm_lead` | crm | convert |
| `crm_opportunity` (per pipeline) | crm | won/lost/reopen |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| crm | `CustomerLookup` + **`CustomerCreator`** (customers, new additive), **`QuoteDraftFactory`** (sales, new additive), `ReceivablesAging` (ar) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| crm | `crm:lead:view|create|update|convert`, `crm:opportunity:view|create|update`, `crm:pipeline:configure` (sensitive), `crm:activity:view|create|update`, `crm:campaign:view|create|update`, `crm:insight:view` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Lead conversion integrity**: `:convert` replays idempotently;
   customer created via `CustomerCreator` contract (architecture test
   asserts CRM has zero writes to `customers` tables); activities
   re-point without duplication.
2. **Pipeline machine**: stage transitions follow per-pipeline
   definitions; failing entry-action hook rolls back the transition;
   `won` without customer ⇒ 422; lost requires reason; reopen audited.
3. **Customer 360**: refresh debounced + idempotent; stale flag correct;
   listener query surface asserted join-free across module schemas;
   reading 360 without `customers:customer:view` ⇒ 403.
4. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
5. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
