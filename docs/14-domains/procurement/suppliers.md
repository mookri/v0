# Suppliers Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §3](./suppliers.md); [ERP parity domains §4.5](./suppliers.md)

Supplier master data, onboarding workflow, and performance ([commercial core domains §3](./suppliers.md)). Phase 4a added supplier scorecards ([ERP parity domains §4.5](./suppliers.md)); the remaining purchasing extensions (requisitions, blanket POs, landed cost, subcontracting) live in the purchasing file. Module code `suppliers`.

---

## Module: Suppliers (module code `suppliers`)

Mirror of Customers for the buy side.

### 3.1 Core entities

`suppliers` (code `SUP-<seq>`, tax_id, payment_term_id, default_currency,
rating, status), `supplier_contacts`, `supplier_addresses`,
`supplier_items` (supplier part numbers + lead time days + last cost — feeds
purchasing suggestions), `supplier_performance` (append-only snapshot rows:
on-time %, price variance).

### 3.2 Primary flows

1. **Supplier onboarding → approval** (workflow): `draft → pending_approval →
   approved`; approval permission `suppliers:supplier:approve` (sensitive;
   SoD rule `sod.vendor_master_payments` keeps the approver distinct from
   anyone holding payment approval — [authorization & policy §7.1](../../04-security/authorization-and-policy.md)).
2. **Preferred-supplier assignment** per product (drives PO suggestions).

### 3.3 Event catalog

`suppliers.supplier.created`, `.updated`, `.approved`, `.blocked`,
`suppliers.item.linked`.

### 3.4 API surface (`/api/v1/suppliers`)

CRUD under `suppliers:supplier:view|create|update`;
`POST /suppliers/{id}:approve` / `:block` under
`suppliers:supplier:approve` (approve sensitive); `/supplier-items` under
`suppliers:supplier:update`. Scopes: `suppliers:read`, `suppliers:write`,
fine-grained `suppliers:supplier:approve`.

Contracts: `SupplierLookup`, `SupplierLeadTimeQuery`.

---
---

## Supplier scorecards (Phase 4a)

Supplier scorecards were specified within the Phase 4a purchasing extensions ([ERP parity domains §4](./purchasing.md)); they are owned by the suppliers module because they read supplier master data and goods-receipt history.

### 4.5 Supplier scorecards (normative)

Extends `supplier_performance` ([commercial core domains §3.1](./suppliers.md)) from snapshot rows to a
computed scoring system:

1. Monthly scoring job per supplier computes: on-time % (GRN date vs PO
   promised date), price variance (PO unit cost vs prior cost trend),
   quality rejection % (GRN rejected qty / received qty — includes
   quality NCR references when doc §6 is enabled), responsiveness
   (RFQ reply rate).
2. Weighted composite → grade (`a|b|c|d`) stored as the period's
   `supplier_performance` row; grade feeds `SupplierLookup` (additive
   fields) and replenishment preference ([enterprise ops domains §6](../supply-chain/supply-chain.md)).
3. Events: `suppliers.scorecard.computed` (supplier_id, period, grade —
   no commercial amounts in payload). Permissions:
   `suppliers:scorecard:view`.
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| customers.* / suppliers.* | 10² |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `supplier_onboarding` | suppliers | approve |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| suppliers | `suppliers:supplier:view|create|update|approve` (approve sensitive), `suppliers:contact:view|create|update` |
| suppliers (extensions) | `suppliers:scorecard:view` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
2. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
