# Tax Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [finance domains §2](./tax.md)

Jurisdiction-generic tax engine with the normative computation contract and deterministic snapshots ([finance domains §2](./tax.md)). Module code `tax`. India statutory adapters (GST e-invoicing, e-way bills, GSTR, TDS/TCS, PF/ESI) live under integrations connectors and never touch the engine core ([ERP parity domains §9](../../15-integrations/connectors/india-statutory.md)).

---

## Module: Tax (module code `tax`)

### 2.1 Bounded context

Tax determination: registrations, tax codes, rates, jurisdiction rules, and
the **computation contract** used by checkout ([commerce domains §2.4](../commerce/store-checkout.md) step 4),
invoicing ([commercial core domains §9](./invoicing.md)), and the GL posting templates. The module computes;
**filing/remittance is out of scope** (reports only).

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `tax_registrations` | id, jurisdiction_code (country/state), registration_number, taxpayer_type, status | tenant's legal registrations (e.g. GSTIN) |
| `tax_codes` | id, code (UQ), name, kind (`sales|purchase|exempt|zero_rated|reverse_charge`), hsn_sac NULL | linked from catalog products and document lines |
| `tax_rates` | id, tax_code_id, jurisdiction_code, rate DECIMAL(10,6), effective_from/to | time-versioned |
| `tax_rules` | id, name, priority, conditions JSON (place of supply: ship-from, ship-to, registration presence), rate resolution order | determination logic |
| `tax_computations` | id, target_type/id (cart session, invoice, bill), input JSON, result JSON (per-line tax, summary), computed_at | snapshot for audit/dispute |
| `tax_return_data` | id, period_id, jurisdiction, aggregated JSON (taxable, tax by rate, ITC) | report input for filings |

### 2.3 Computation contract (normative)

`TaxEngine.compute(lines[], context)` where context = store/warehouse
jurisdiction, ship-to address, customer tax profile, date, currency:

1. For each line: resolve tax_code (line → product → default) →
   jurisdiction determination via `tax_rules` (place-of-supply logic:
   intra-state vs inter-state splits where applicable, e.g. CGST/SGST vs
   IGST for India; single rate otherwise).
2. Apply rate(s) effective at `date`; compute per-line tax with line-level
   rounding (half-up); record tax amounts per rate component.
3. Reverse-charge / exempt / zero-rated handling per kind (exempt lines
   reported with zero tax but flagged for return data).
4. Return: per-line tax amounts + summary by rate + tax_summary JSON in the
   shape invoicing persists ([commercial core domains §9.2](./invoicing.md)).
5. **Determinism**: same input ⇒ same output; every computation stored in
   `tax_computations` for dispute reconstruction.

v1 ships: India GST package (CGST/SGST/IGST/UTGST with HSN, composition
flag) + a generic single-rate VAT package; other locales are added as
configuration, not code.

### 2.4 Primary flows

1. **Registration setup**: finance admin enters registrations →
   `tax.registration.created`.
2. **Rate management**: effective-dated rate changes; future-dated rates
   allowed; changes never mutate history (time-versioned).
3. **Computation**: checkout/invoicing call the contract; results
   snapshotted on documents.
4. **Return data aggregation**: monthly job aggregates posted invoices/bills
   into `tax_return_data` per jurisdiction (output tax, input tax credit)
   → downloadable export (CSV/JSON) for the tenant's accountant;
   `tax.return_data.generated`.

### 2.5 Event catalog

`tax.registration.created` / `.updated`, `tax.rate.changed` (effective
date), `tax.rule.updated`, `tax.return_data.generated`.

### 2.6 API surface (/api/v1/tax)

CRUD registrations/codes/rates/rules under `tax:registration:view|configure`,
`tax:code:view|configure`, `tax:rate:view|configure` (configure sensitive);
POST /tax/compute (dry run) under `tax:code:view`; return data GET/export
under `tax:return:view` + `tax:return:export` (export sensitive).
Scopes: `tax:read`, `tax:write`, fine sensitive scopes.

Contracts: `TaxEngine` (checkout, invoicing, GL templates),
`TaxSummaryQuery` (reports).

### 2.7 Integration points

Consumes: catalog (HSN), customers/suppliers (tax profiles/registrations),
store (jurisdiction). Consumed by: checkout, invoicing, gl, finance_reports.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| tax.* | 10¹ (config) + computations are synchronous calls |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| tax | `tax:registration:view|configure` (configure sensitive), `tax:code:view|configure` (configure sensitive), `tax:rate:view|configure` (configure sensitive), `tax:rule:view|configure` (configure sensitive), `tax:return:view|export` (export sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Tax determinism**: same computation input twice → byte-identical
   result; snapshot stored; GST inter-state vs intra-state split verified
   against a fixture address set.
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
