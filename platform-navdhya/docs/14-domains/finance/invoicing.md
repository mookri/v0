# Invoicing Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §9](./invoicing.md); [ERP parity domains §3.4](./invoicing.md)

Invoices, credit notes, bills, and the bill-hold workflow feeding three-way match ([commercial core domains §9](./invoicing.md)). Phase 4a added pro-forma invoices ([ERP parity domains §3.4](./invoicing.md)), which deliberately produce no AR rows and no GL postings. Module code `invoicing`.

---

## Module: Invoicing (module code `invoicing`)

### 9.1 Bounded context

Document engine for money papers: sales invoices, credit notes, purchase
bills, debit notes; numbering, tax summary computation (tax **rates/rules**
come from the finance tax engine contract — Phase 3; v1 computes with the
tenant tax tables seeded by the finance installer), due-date and dunning
state.

### 9.2 Core entities

| Table | Key fields |
|-------|-----------|
| `invoices` | id, number (`INV-<fy>-<seq>` per numbering scheme), customer_id, order_id NULL, status machine, currency, issue_date, due_date, tax_summary JSON, totals JSON, payment_status (`unpaid|partial|paid|overdue`) |
| `invoice_lines` | description, variant_id NULL, qty, unit_price, discount, tax_code, tax_amount, line_total |
| `credit_notes` / `credit_note_lines` | number `CN-...`, invoice_id NULL, reason |
| `bills` / `bill_lines` | number, supplier_id, po_id NULL, grn_id NULL, status incl. `held` (3-way match) |
| `debit_notes` | against bills |
| `numbering_schemes` | per document type + fiscal year, prefix, padding, reset policy |

### 9.3 State machines

- Invoice: `draft → issued → partially_paid → paid`; `issued → cancelled`
  (only via credit note in regulated mode; tenant setting); `overdue` is a
  computed flag, not a state.
- Bill: `draft → held → approved → partially_paid → paid` (held = 3-way
  match failure, released by finance approval).

### 9.4 Primary flows

1. **Invoice from order**: consume `sales.order.fulfilled` (or manual) →
   draft from order lines → issue (number assigned atomically per scheme,
   idempotent per order) → notify customer (email PDF) →
   `invoicing.invoice.issued` (finance posts revenue, [finance domains](./gl.md)).
2. **Payment application**: consume `payments.payment.applied` → update
   `payment_status` → when fully paid emit `invoicing.invoice.paid`.
3. **Credit note**: against invoice (commerce return/refund flows invoke it)
   → `invoicing.credit_note.issued`.
4. **Bill 3-way match**: on bill registration, compare PO price × GRN qty vs
   bill (tolerances from settings); mismatch ⇒ `held` + approval workflow.
5. **Dunning**: nightly job marks overdue, schedules reminder notifications
   per dunning plan (tenant setting), emits `invoicing.invoice.overdue`.

### 9.5 Event catalog

`invoicing.invoice.created` / `.issued` / `.paid` / `.overdue` /
`.cancelled`, `invoicing.credit_note.issued`, `invoicing.bill.registered`
/ `.held` / `.approved` / `.paid`, `invoicing.debit_note.issued`.

### 9.6 API surface (`/api/v1/invoicing`)

CRUD/view + `POST /invoices/{id}:issue` (`invoicing:invoice:issue`,
idempotent), `/invoices/{id}/credit-notes` (`invoicing:credit_note:create`),
bills CRUD + `:approve` (`invoicing:bill:approve`, sensitive), numbering
schemes under `invoicing:numbering:configure`. Scopes: `invoicing:read`,
`invoicing:write`, fine-grained sensitive.

Contracts: `InvoiceLookup`, `ReceivablesAging` (bucketed outstanding —
finance/analytics), `PayablesAging`.

### 9.7 Integration points

Consumes events: sales (fulfilled), payments (applied/refunded).
Publishes to: finance (revenue/expense posting), analytics, notifications
(dunning).

---
---

## Pro-forma invoices (Phase 4a)

Pro-forma invoices were specified within the Phase 4a sales extensions ([ERP parity domains §3](../sales/sales.md)); they are owned by invoicing because they share document-numbering and posting-filter rules.

### 3.4 Pro-forma invoices (normative)

Invoicing ([commercial core domains §9](./invoicing.md)) gains `kind` (`invoice|pro_forma`, additive column,
default `invoice`):

1. Pro-forma documents behave like invoices for composition (lines,
   totals, tax computation) but are **excluded from AR and GL posting**
   (posting rules filter `kind=invoice` only).
2. `POST /invoices/{id}:convert` (`invoicing:invoice:issue` guard)
   converts a pro-forma into a real invoice: copies lines, assigns the
   invoice number from the scheme ([commercial core domains §9.6](./invoicing.md)), emits
   `invoicing.proforma.converted`; conversion is idempotent and
   one-way.
3. Events: `invoicing.proforma.issued`, `invoicing.proforma.converted`.
   No new permissions (existing invoice permissions apply).
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| invoicing.* | 10³ |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `bill` (hold release) | invoicing | approve |
| `invoice` | invoicing | issue (idempotent, not sensitive) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| invoicing | `SalesOrderLookup` (sales), `CustomerLookup` (customers), `SupplierLookup` (suppliers) |
| checkout / invoicing | `TaxEngine` (tax) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| invoicing | `invoicing:invoice:view|create|issue`, `invoicing:credit_note:create`, `invoicing:bill:view|create|approve` (approve sensitive), `invoicing:debit_note:create`, `invoicing:numbering:configure` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **3-way match**: bill with price mismatch lands in `held`; approval
   releases it; SoD blocks self-approval.
2. **Idempotency**: duplicate `POST /orders` with the same key returns the
   same order; duplicate `:issue` on an invoice is a no-op replay.
3. **Pro-forma isolation**: pro-forma documents produce no AR rows and
   no GL postings (posting-rule filter tested); `:convert` is one-way
   and idempotent, assigning a scheme number.
4. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
5. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
