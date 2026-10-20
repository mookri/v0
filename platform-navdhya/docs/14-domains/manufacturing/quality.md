# Quality Management Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [ERP parity domains §7](./quality.md)

Inspection plans with declared gates, NCR (non-conformance reports), and CAPA with the verifier ≠ owner rule ([ERP parity domains §7](./quality.md)). Quality blocks flow only at declared inspection gates via the workflow engine — gates are declared, never hard-coded (planning [LD-09](../../21-adrs/ADR-0010-quality-event-driven-gates.md)). Module code `quality`.

---

## Quality Management (module code `quality`)

### 7.1 Bounded context

Quality gates and non-conformance across inbound (GRN), in-process
(manufacturing), and outbound (returns/customer complaints) flows.
Quality **never blocks writes synchronously except at declared gates**
(planning [LD-09](../../21-adrs/ADR-0010-quality-event-driven-gates.md)); gate evaluation uses workflow approvals ([kernel services §4.3](../../11-workflow/workflow-engine.md)).

### 7.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `quality_inspection_plans` | id, code (UQ per tenant), name, gate (`inbound|in_process|outbound`), trigger JSON (variant_ids/category_ids/supplier_ids, sampling_rule), checks JSON ([{name, kind: pass_fail|measure, spec, uom}]), status | declarative gate definitions |
| `quality_inspections` | id, number (`QC-<seq>`), plan_code, gate, subject_type (`goods_receipt|work_order|return_receipt`), subject_id, status (`pending|in_progress|passed|failed|waived`), inspected_by, decided_at, result JSON (per-check outcomes) | |
| `quality_ncrs` | id, number (`NCR-<seq>`), inspection_id NULL, source (`inbound|in_process|outbound|customer`), subject ref, defect_code, severity (`minor|major|critical`), status machine (`open|under_review|disposed|closed`), disposition (`return_to_supplier|scrap|rework|use_as_is|accept_with_concession`) | non-conformance report |
| `quality_capas` | id, number (`CAPA-<seq>`), ncr_id, kind (`corrective|preventive`), action_plan, owner_membership_id, due_on, status (`open|in_progress|verified|closed`), verification_note | corrective/preventive action |
| `quality_defect_codes` | id, code (UQ per tenant), name, category | seeded + tenant-defined |

### 7.3 Gate mechanics (normative)

1. **Inbound gate**: GRN posting ([commercial core domains §6.4](../procurement/purchasing.md) flow 2) checks active
   `inbound` plans matching (variant/category/supplier). Match ⇒
   received qty goes to a `quarantine` location (inventory location kind
   exists, [commercial core domains §7.2](../inventory/inventory.md)) and an inspection is created. GRN is **not**
   blocked — stock lands in quarantine; the gate controls the move to
   sellable.
2. **Inspection decision**: `:pass` moves quarantine qty to sellable
   (inventory transfer event with `ref_type=quality_inspection`);
   `:fail` routes qty per NCR disposition; `:waive` requires
   `quality:inspection:waive` (sensitive, ACR 2) + reason.
3. **In-process gate**: manufacturing work-order operations flagged
   `quality_gate=true` ([enterprise ops domains §5](./manufacturing.md) routing steps gain the flag) require
   a passed inspection before the next operation may report completion
   (shop-floor reporting §11.2 checks gate state).
4. **Outbound gate**: return receipt inspection ([commerce domains §5.4](../commerce/promotions-shipping-returns.md) flow 2)
   may trigger an inspection; disposition `quarantine` lines flow into
   quality.
5. **NCR → CAPA**: NCRs with severity `major|critical` require at least
   one CAPA before `closed`; CAPA `verified` by a different membership
   than the owner (SoD pattern).
6. Events: `quality.inspection.created` / `.passed` / `.failed` /
   `.waived`, `quality.ncr.created` / `.disposed` / `.closed`,
   `quality.capa.created` / `.verified` / `.closed`.
7. Permissions: `quality:plan:configure` (sensitive),
   `quality:inspection:view|decide|waive` (waive sensitive),
   `quality:ncr:view|create|update|dispose` (dispose sensitive),
   `quality:capa:view|create|update|verify`.

### 7.4 API surface (`/api/v1/quality`)

CRUD plans/defect-codes under `quality:plan:configure`;
inspections list/view + `:pass|:fail|:waive` under
`quality:inspection:*`; NCRs CRUD + `:dispose` + `:close` under
`quality:ncr:*`; CAPAs CRUD + `:verify` under `quality:capa:*`.
Scopes: `quality:read`, `quality:write`, fine-grained sensitive scopes.

### 7.5 Integration points

Consumes: purchasing (GRN events), inventory (quarantine/transfers),
manufacturing (work-order gate flags), returns (receipt events).
Publishes to: inventory (disposition movements), suppliers (scorecard
rejection %, §4.5), analytics (defect rates), notifications.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| quality.* | 10¹–10² |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `quality_inspection` | quality | waive |
| `quality_ncr` / `quality_capa` | quality | dispose, verify |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| quality | GRN events (purchasing), quarantine transfers (inventory), `BomLookup` (manufacturing), return receipts (returns) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| quality | `quality:plan:configure` (sensitive), `quality:inspection:view|decide|waive` (waive sensitive), `quality:ncr:view|create|update|dispose` (dispose sensitive), `quality:capa:view|create|update|verify` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Quality gates**: GRN match routes qty to quarantine (sellable
    unchanged); `:pass` moves to sellable; `:waive` requires ACR 2;
    work-order step blocked until gate passes (422
    `manufacturing_gate_not_cleared`); major NCR cannot close without a
    verified CAPA (verifier ≠ owner).
2. **Tenant isolation + sensitivity**: every new table covered by
    `AssertTenantIsolation`; statutory amounts verified absent from all
    events and structured logs; NCR/contract exports audited.
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
