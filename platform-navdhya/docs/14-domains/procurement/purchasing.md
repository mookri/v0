# Purchasing Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §6](./purchasing.md); [ERP parity domains §4](./purchasing.md) (except §4.5)

RFQs, purchase orders, receiving, and three-way match ([commercial core domains §6](./purchasing.md)), plus Phase 4a extensions: purchase requisitions, blanket purchase agreements, landed cost, subcontracting ([ERP parity domains §4](./purchasing.md)). Module code `purchasing`. Supplier scorecards ([ERP parity domains §4.5](./suppliers.md)) live in the suppliers domain.

---

## Module: Purchasing (module code `purchasing`)

### 6.1 Bounded context

RFQ → Purchase Order → Goods Receipt → Bill ingestion. Mirror-image of
sales against suppliers.

### 6.2 Core entities

`purchase_rfqs` (+lines), `purchase_rfq_quotes` (supplier responses),
`purchase_orders` (number `PO-<yyyy>-<seq>`, supplier_id, currency, status
machine, totals), `purchase_order_lines` (variant_id, qty, unit_cost,
received_qty), `goods_receipts` (number `GRN-<seq>`, po_id, warehouse_id,
received_at, status), `goods_receipt_lines` (qty_accepted, qty_rejected,
batch/lot, expiry for perishables). Purchase **bills** live in the
invoicing module (§9), referenced by PO/GRN id.

### 6.3 State machine

`draft → pending_approval → approved → partially_received → received →
billed → closed`, plus `cancelled` from draft/approved. `approve` is
sensitive above threshold (approval policy with SoD `sod.po_vendor_bill`).

### 6.4 Primary flows

1. **Replenishment → PO**: inventory reorder suggestion (contract) or manual
   → draft PO with supplier items' last cost → submit → approve → send to
   supplier (notification/email PDF).
2. **Goods receipt**: against PO, partial receipts allowed; acceptance vs
   rejection split; receipt posts stock-in (event → inventory) and updates
   `received_qty`; over-receipt blocked beyond tolerance setting.
3. **Bill ingestion**: supplier bill registered in invoicing against PO/GRN
   (3-way match: PO price × GRN qty vs bill) — mismatch → `bill.held`
   workflow (finance, [finance domains](../finance/gl.md)).
4. **RFQ**: multi-supplier solicitation → compare table → accept one →
   convert to PO.

### 6.5 Event catalog

`purchasing.rfq.created` / `.sent` / `.awarded`,
`purchasing.order.created` / `.submitted` / `.approved` / `.sent` /
`.cancelled`, `purchasing.receipt.recorded` (po_id, lines with accepted
qty, batch), `purchasing.receipt.rejected`.

### 6.6 API surface (`/api/v1/purchasing`)

CRUD + verbs for rfqs/orders/receipts under `purchasing:rfq:view|create|
update`, `purchasing:order:view|create|update|approve|cancel` (approve
sensitive), `purchasing:receipt:view|create`. Scopes: `purchasing:read`,
`purchasing:write`, fine `purchasing:order:approve`.

Contracts: `PurchaseOrderLookup`, `OpenPurchaseExposure` (cash-flow
analytics feed).

### 6.7 Integration points

Consumes: suppliers, catalog, inventory (stock-in via events). Publishes to:
inventory (receipts), invoicing (bills), finance (accruals).

---
---

## Purchasing extensions (Phase 4a)

Phase 4a purchasing extensions; supplier scorecards ([ERP parity domains §4.5](./suppliers.md)) are owned by the suppliers domain.

### 4.1 Scope

Requisitions, blanket purchase agreements, landed cost, supplier
scorecards, and subcontracting — extending [commercial core domains §6](./purchasing.md) without touching
the PO aggregate's core state machine.

### 4.2 Purchase requisitions (normative)

| Table | Key fields |
|-------|-----------|
| `purchase_requisitions` | id, number (`PR-<yyyy>-<seq>`), requested_by membership_id, org_id, status machine (`draft|pending_approval|approved|converted|cancelled`), needed_by, justification, totals JSON |
| `purchase_requisition_lines` | requisition_id, variant_id, qty, suggested_supplier_id NULL, notes |

Rules:
1. Approval is a workflow transition (`purchasing:requisition:approve`,
   sensitive above tenant amount threshold); approval-chain SoD: the
   approver MUST differ from `requested_by` ([authorization & policy §7.1](../../04-security/authorization-and-policy.md)
   approval_chain pattern).
2. Conversion groups approved lines by suggested supplier and creates
   PO **drafts** via the standard PO creation path (each draft carries
   `requisition_ref`); conversion is idempotent per requisition.
3. Events: `purchasing.requisition.created` / `.submitted` /
   `.approved` / `.rejected` / `.converted`. Permissions:
   `purchasing:requisition:view|create|update|approve|convert`.

### 4.3 Blanket purchase agreements (normative)

Mirror of sales blanket orders (§3.2) against suppliers:

| Table | Key fields |
|-------|-----------|
| `purchase_blanket_orders` | id, number (`BPO-<yyyy>-<seq>`), supplier_id, status (`open|exhausted|expired|closed`), valid_from/to, currency, totals JSON |
| `purchase_blanket_order_lines` | blanket_id, variant_id, committed_qty, released_qty, unit_cost (Money) |

Release creates PO drafts with `blanket_ref`; over-release blocked;
approval of released POs follows the normal PO workflow ([commercial core domains §6.3](./purchasing.md)).
Events: `purchasing.blanket.created` / `.released` / `.expired` /
`.closed`. Permissions: `purchasing:blanket:view|create|update|close`
(close sensitive).

### 4.4 Landed cost (normative)

Landed cost allocates inbound logistics charges to received goods,
adjusting item cost and ledger value:

1. `goods_receipt_landed_costs` (per GRN): `component`
   (`freight|duty|insurance|customs|misc`), `amount` (Money),
   `allocation` (`by_value|by_qty|by_weight`), `supplier_ref NULL`
   (e.g. carrier bill).
2. Allocation runs at GRN posting (same transaction as the receipt
   movements): each accepted line receives its share; per-line landed
   cost ÷ qty raises the item's cost basis →
   `inventory.valuation.changed` carries `landed=true` + component
   breakdown ([commercial core domains §7.5](../inventory/inventory.md) event, additive fields).
3. GL: the posting engine ([finance domains §1.4](../finance/gl.md)) posts landed components to the
   inventory asset account (credit: landed-cost clearing / AP accrual
   per component mapping in `account_mappings`).
4. Editing landed costs after the GRN is bill-matched ([commercial core domains §6.4](./purchasing.md)
   flow 3) is prohibited — late charges go through a revaluation entry
   ([finance domains §1.3](../finance/gl.md) reversal pattern).
5. Events: `purchasing.landed_cost.applied`. Permissions:
   `purchasing:landed_cost:create|update` (sensitive: cost basis
   changes ripple to COGS).

### 4.6 Subcontracting (normative)

1. PO `kind` gains `subcontract` (additive): the PO lines name the
   **finished** variants; `subcontract_components` lines name the
   components to be supplied.
2. Component issue: `POST /purchase-orders/{id}:issue-components`
   creates inventory movements (`subcontract_issue`) from stock to a
   virtual `supplier_floor` location kind (inventory §7 location kinds
   gain `supplier_floor`); components remain tenant stock (valued).
3. Receipt: GRN against the subcontract PO receives finished goods
   (normal receipt movements) and consumes components per BOM ratio
   (manufacturing `BomLookup` contract, [enterprise ops domains §5](../manufacturing/manufacturing.md)); variances post as
   adjustments with reason `subcontract_variance`.
4. Work-order linkage: a subcontract PO may reference a manufacturing
   work order (`work_order_ref`); work-order close reconciles issued vs
   consumed components.
5. Events: `purchasing.subcontract.components_issued` /
   `.received`. Permissions: component issue under
   `purchasing:order:update`; receipt under existing
   `purchasing:receipt:create`.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| purchasing.* | 10³ |
| sales/purchasing extensions | 10¹–10² (blankets/backorders) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `purchase_order` | purchasing | approve (threshold) |
| `sales_blanket_order` / `purchase_blanket_order` | sales / purchasing | close |
| `purchase_requisition` | purchasing | approve |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| purchasing | `SupplierLookup` (suppliers), `ProductLookup` (catalog), `ReorderSuggestions` (inventory) |
| purchasing | `BudgetCheck` (budgets) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| purchasing | `purchasing:rfq:view|create|update`, `purchasing:order:view|create|update|approve|cancel` (approve sensitive), `purchasing:receipt:view|create` |
| purchasing (extensions) | `purchasing:requisition:view|create|update|approve|convert` (approve sensitive above threshold), `purchasing:blanket:view|create|update|close` (close sensitive), `purchasing:landed_cost:create|update` (sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Blanket orders (both directions)**: over-release ⇒ 422; expiry job
   flips state once; close releases commitment with audit; released POs
   inherit blanket pricing.
2. **Requisition SoD**: requester self-approval ⇒ 403 `sod_conflict`;
   conversion groups by supplier and is idempotent per requisition.
3. **Landed cost**: allocation raises per-unit cost basis exactly
   (by_value/by_qty/by_weight fixtures); GL entry balanced; post-bill-
   match edits ⇒ 422.
4. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
5. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
