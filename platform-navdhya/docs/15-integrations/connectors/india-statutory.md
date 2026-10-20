# Connector Dossier — India Statutory Package

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [ERP parity domains §9](./india-statutory.md) (planning [LD-07](../../21-adrs/ADR-0008-statutory-channel-adapters.md))

Dossier for the India statutory package: GST e-invoicing (IRN), e-way bills, PF/ESI/PT computation and filing inputs, GSTR exports, and TDS/TCS — implemented as feature-flagged channel adapters and report exporters ([ERP parity domains §9](./india-statutory.md), planning [LD-07](../../21-adrs/ADR-0008-statutory-channel-adapters.md)). The [tax engine](../../14-domains/finance/tax.md) and payroll engine stay jurisdiction-generic; no statutory logic is hard-coded into them. Adapter mechanics live in the [integration framework](../integration-framework.md).

---

## India Statutory Package (module code `india_statutory`)

### 9.1 Scope & positioning

India-specific statutory automation behind the
`module.india_statutory` feature flag (planning [LD-07](../../21-adrs/ADR-0008-statutory-channel-adapters.md)): GST e-invoicing
(IRN), e-way bills, GSTR exports, TDS/TCS, and PF/ESI computation +
filing inputs. Implemented as **channel adapters** ([API platform §10](../integration-framework.md)) and
**report exporters** — the tax engine ([finance domains §2](../../14-domains/finance/tax.md)) and payroll engine
([enterprise ops domains §2](../../14-domains/payroll/payroll.md)) stay jurisdiction-generic.

### 9.2 GST e-invoicing (IRN) adapter (normative)

1. **Trigger**: `invoicing.invoice.issued` for invoices whose tenant tax
   profile requires e-invoicing (tenant setting
   `india_statutory.einvoice_enabled` + turnover threshold config).
2. **Generation**: adapter composes the IRN payload (schema version
   pinned in adapter config) from invoice + customer (GSTIN, address) +
   lines (HSN from catalog, [commercial core domains §1.2](../../14-domains/commerce/catalog.md)) + tax engine output (CGST/SGST/
   IGST split by place-of-supply, [finance domains §2](../../14-domains/finance/tax.md)).
3. **Registration**: submits to the configured IRN endpoint (channel
   adapter with retries + circuit breaker, [API platform §7](../integration-framework.md) mechanics); success
   stores `irn`, `irn_qr` (JSON on the invoice, additive), marks
   `einvoice_status=registered`; failure ⇒ `einvoice_status=failed` +
   ops alert + retry queue (never blocks the invoice's issuance — IRN is
   asynchronous).
4. **Cancellation**: `POST /invoices/{id}/einvoice:cancel` (within the
   statutory window) → adapter cancellation → `einvoice_status=cancelled`
   (sensitive, audited).
5. Events: `india_statutory.einvoice.registered` / `.failed` /
   `.cancelled`. Permissions: `india_statutory:einvoice:view|configure`
   (configure sensitive), cancellation under
   `india_statutory:einvoice:cancel` (sensitive).

### 9.3 E-way bills (normative)

1. Trigger: `shipping.shipment.shipped` for inter-state movement above
   the statutory value threshold (tenant config), or manual generation
   for pre-transport.
2. Adapter composes the e-way bill (parties, goods, HSN, value, distance
   from address geo, transport details) and registers it; stores
   `eway_bill_number`, `eway_valid_to` on the shipment (additive).
3. Expiry job warns before validity lapse (`india_statutory.eway.
   expiring`); extension handled by re-generation (new number).
4. Events: `india_statutory.eway.generated` / `.expired`. Permissions:
   `india_statutory:eway:view|create`.

### 9.4 PF / ESI statutory (normative)

1. Payroll run lines ([enterprise ops domains §2.2](../../14-domains/payroll/payroll.md)) gain statutory components computed by
   the adapter: PF (employee + employer shares per wage ceiling config),
   ESI (wage-basis eligibility), Professional Tax (state-wise slab
   tables in adapter config).
2. Computation is **deterministic** ([enterprise ops domains §2](../../14-domains/payroll/payroll.md) payroll determinism
   applies): same inputs ⇒ byte-identical statutory lines; slab tables
   are versioned config, effective-dated.
3. Filing inputs: monthly export files (ECR for PF, ESI contribution
   file) generated as report artifacts (kernel Files) — filing itself is
   out-of-platform in v1 (portal upload by the tenant).
4. Events: `india_statutory.payroll.components_computed` (run ref only —
   **no amounts in events**, [enterprise ops domains §7.5](../../14-domains/hr/hr.md)). Permissions:
   `india_statutory:statutory:view|configure` (configure sensitive),
   `india_statutory:filing:export` (sensitive).

### 9.5 GSTR exports (normative)

1. Report definitions (finance_reports pattern, [finance domains §8](../../14-domains/finance/assets-budgets-reports.md)): GSTR-1
   (outward supplies from issued invoices + debit/credit notes),
   GSTR-3B (summary tax liability + ITC from inward registers).
2. Export files follow the portal schema (JSON/CSV) pinned per adapter
   version; generation is queued with a reconciliation summary
   (invoice count vs export count, tax totals).
3. ITC reconciliation against portal 2A/2B feeds is **deferred**
   (planning DF-24); the export surface is the v1 commitment.
4. Permissions: `india_statutory:return:view|export` (export sensitive).

### 9.6 TDS / TCS (normative)

1. **TDS on payments**: payout batches ([commercial core domains §8](../../14-domains/finance/payments.md)) consult TDS rules
   (supplier-level section + rate config, `supplier_tds_profiles`);
   applicable payouts compute TDS, reduce the paid amount, and post the
   deducted amount to a TDS-payable account via posting rules; the
   supplier receives a TDS certificate artifact (Files) per period.
2. **TCS on sales** (where applicable, e.g. e-commerce): checkout/invoice
   adds TCS via the tax engine as a computed component (tax engine
   supports additive components, [finance domains §2.2](../../14-domains/finance/tax.md)), posted to TCS-payable.
3. Events: `india_statutory.tds.deducted` (payment ref, section, rate —
   amounts stay out of events), `india_statutory.tcs.collected`.
   Permissions: `india_statutory:tds:view|configure` (configure
   sensitive), `india_statutory:certificate:export`.

### 9.7 Adapter rules (normative)

1. Provider endpoints (NIC IRN, e-way portal) are **configuration** in
   `channel_connections` ([security baseline §3](../../04-security/security-baseline.md) encryption), never hard-coded.
2. All adapter calls carry idempotency keys (invoice/shipment refs) —
   retries never double-register.
3. Sandbox mode: adapter config `mode=sandbox` returns deterministic
   mock registrations for tenant testing (feature-flagged).
4. Statutory schema version bumps are adapter releases, not platform
   releases; the adapter pins the schema version it emits and records it
   on each artifact.
---
---

## Dossier summary

| Adapter | Trigger | Direction | Provider surface |
|---|---|---|---|
| GST e-invoicing (IRN) | `invoicing.invoice.issued` (tenant enabled + turnover threshold) | outbound, async | NIC IRN endpoint (connection config) |
| E-way bills | `shipping.shipment.shipped` inter-state above threshold, or manual | outbound | e-way portal (connection config) |
| PF / ESI / PT | payroll run lines | compute + filing export | portal upload stays out-of-platform in v1 |
| GSTR-1 / GSTR-3B | on demand / period | report export | portal schema pinned per adapter version |
| TDS / TCS | payout batches / checkout tax components | compute + posting + certificates | certificates via kernel Files |

Rules common to all adapters ([ERP parity domains §9.7](./india-statutory.md)): provider endpoints are configuration, never hard-coded; every provider call carries an idempotency key (retries never double-register); sandbox mode (`mode=sandbox`) returns deterministic mock registrations; statutory schema version bumps ship as adapter releases with the emitted version recorded on each artifact.
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| india_statutory.* | 10²–10³ (e-invoice mirrors invoice volume) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| india_statutory | tax engine output (tax), invoice events (invoicing), shipment events (shipping), payroll run lines (payroll) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| india_statutory | `india_statutory:einvoice:view|configure|cancel` (configure/cancel sensitive), `india_statutory:eway:view|create`, `india_statutory:statutory:view|configure` (configure sensitive), `india_statutory:filing:export` (sensitive), `india_statutory:return:view|export` (export sensitive), `india_statutory:tds:view|configure` (configure sensitive), `india_statutory:certificate:export` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **E-invoicing**: issuance never blocked by adapter latency (async
    registration); retry never double-registers (idempotency key);
    cancel outside window ⇒ 422; failure surfaces ops alert + retry
    queue.
2. **E-way bills**: inter-state shipment above threshold generates a
    bill; expiry warning emitted once; below-threshold generates none.
3. **PF/ESI determinism**: same run inputs twice ⇒ byte-identical
    statutory lines; slab-table version effective-dating honored; event
    payloads contain no amounts; export files parse against pinned
    schema fixtures.
4. **TDS/TCS**: applicable payout pays net-of-TDS with certificate
    artifact; TDS posts to payable account; TCS component appears in
    checkout tax computation and invoice totals exactly.
5. **GSTR exports**: exported invoice count/tax totals reconcile with
    the ledger for the period (golden dataset); portal schema version
    pinned on the artifact.
6. **Tenant isolation + sensitivity**: every new table covered by
    `AssertTenantIsolation`; statutory amounts verified absent from all
    events and structured logs; NCR/contract exports audited.
7. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
