# AR, AP & Banking Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [finance domains §3](./ar-ap-banking.md)–5; [ERP parity domains §12.5](./ar-ap-banking.md)

Accounts receivable (aging, collections, write-offs — [finance domains §3](./ar-ap-banking.md)), accounts payable with the payment-run state machine ([finance domains §4](./ar-ap-banking.md)), and banking & reconciliation with the normative matching engine ([finance domains §5](./ar-ap-banking.md)). Phase 4a added dunning ([ERP parity domains §12.5](./ar-ap-banking.md)). Module codes `ar`, `ap`, `banking`.

---

## Module: AR — Accounts Receivable (module code `ar`)

### 3.1 Bounded context

The receivables sub-ledger: customer balances, aging, collections,
write-offs, and customer statement generation. AR **mirrors** invoicing +
payments events into its own sub-ledger tables (no cross-module joins) and
feeds GL via the posting engine.

### 3.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `ar_customer_accounts` | id, customer_id (shadow), balance (cached), credit_limit (shadowed from customers events), status | rebuilt from ledger; verified nightly |
| `ar_entries` | id, customer_id, entry_type (`invoice|credit_note|payment|write_off|adjustment`), ref_type/ref_id, amount (+/−), currency, due_date NULL, open_amount, occurred_at | **append-only** sub-ledger |
| `ar_allocations` | id, payment_entry_id, invoice_entry_id, allocated_amount | many-to-many payment↔invoice |
| `ar_aging_snapshots` | id, customer_id NULL (NULL = tenant rollup), as_of_date, buckets JSON (current, 1-30, 31-60, 61-90, 90+) | daily job |
| `collection_activities` | id, customer_id, kind (`call|email|visit|note`), notes, follow_up_at, membership_id | dunning CRM-lite |
| `write_offs` | id, customer_id, amount, reason, entry_id (AR), gl posted via engine | sensitive |

### 3.3 Primary flows

1. **Ledger sync**: consume `invoicing.invoice.issued` (+ AR entry, open),
   `invoicing.credit_note.issued` (− entry), `payments.payment.applied`
   (allocation + open_amount reduction), `invoicing.invoice.paid` (close).
2. **Aging**: nightly job computes `ar_aging_snapshots` per customer +
   tenant rollup; contract `ReceivablesAging` serves finance/analyticss
   reads ([commercial core domains §9.6](./invoicing.md)).
3. **Collections**: overdue accounts surface in a work queue (dunning plan
   from invoicing); staff log activities; follow-ups create notifications.
4. **Write-off**: create write-off (reason mandatory, `ar:writeoff:create`
   sensitive, approval workflow above threshold) → AR entry closes residual
   open_amount → `ar.writeoff.posted` → GL posts bad-debt entry.
5. **Statements**: per-customer statement PDF (period, open items) via Files
   + notification; `ar.statement.generated` (audit).

### 3.4 Event catalog

`ar.entry.recorded`, `ar.allocation.made`, `ar.writeoff.posted`,
`ar.aging.computed` (ops), `ar.statement.generated`,
`ar.limit.breached` (feeds sales credit holds).

### 3.5 API surface (/api/v1/ar)

| Endpoint | Permission |
|----------|-----------|
| GET customer accounts, ledger, allocations | `ar:account:view` |
| GET aging (list + rollup) | `ar:aging:view` |
| CRUD collection activities | `ar:collection:view|create|update` |
| POST /write-offs, POST /write-offs/{id}:approve | `ar:writeoff:create` / `ar:writeoff:approve` (sensitive) |
| POST /customers/{id}/statements | `ar:statement:generate` |
| POST /allocations (manual re-allocation) | `ar:allocation:manage` (sensitive) |

Scopes: `ar:read`, `ar:write`, fine sensitive scopes above.

Contracts: `ReceivablesAging` (bucketed outstanding by customer/date),
`CustomerBalanceQuery` (live balance for sales credit checks — sales
module consumes via customers' `CustomerCreditCheck`, which calls this).

### 3.6 Integration points

Consumes events: invoicing, payments, customers (credit limit shadows).
Publishes to: gl (posting), sales (credit exposure via contract chain),
analytics.

---
---

## Module: AP — Accounts Payable (module code `ap`)

### 4.1 Bounded context

The payables sub-ledger: supplier balances, bill schedules, payment runs
(orchestrates payouts in the payments module), accruals, and 1099-style
supplier payment summaries.

### 4.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `ap_supplier_accounts` | id, supplier_id (shadow), balance (cached), status | rebuilt from ledger |
| `ap_entries` | id, supplier_id, entry_type (`bill|debit_note|payment|accrual|adjustment`), ref_type/ref_id, amount, currency, due_date, open_amount, occurred_at | append-only |
| `ap_payment_proposals` | id, run_id, bill_entry_id, proposed_amount, priority, status | payment-run selection |
| `ap_payment_runs` | id, number (`RUN-<seq>`), status machine, scheduled_at, account_id (payout source), approved_by | batches into payments payout_batches |
| `ap_accruals` | id, supplier_id NULL, description, amount, period_id, reversed_entry_id NULL | period-end accrual journal companion |
| `supplier_payment_summaries` | id, supplier_id, fiscal_year, total_paid JSON by month | export for statutory reporting |

### 4.3 State machine (payment run)

`draft → pending_approval → approved → executing → executed`, plus
`cancelled` before executing. Approval is sensitive and subject to SoD
`sod.vendor_master_payments` ([authorization & policy §7.1](../../04-security/authorization-and-policy.md)) — the run approver cannot be a
membership that created/edited the underlying supplier master records in
scope.

### 4.4 Primary flows

1. **Ledger sync**: consume `invoicing.bill.registered` (approved → open
   AP entry), `invoicing.bill.paid`, `payments.payout.executed`
   (allocation), `invoicing.debit_note.issued`.
2. **Payment run**: select due bills (filter: due date, supplier, amount) →
   proposals → approval (workflow) → execute: creates payments payout batch
   ([commercial core domains §8](./payments.md)) → on `payments.payout.executed` mark run executed →
   `ap.run.executed`.
3. **Accruals**: period-end job creates accrual entries for received-not-
   invoiced (GRNI via inventory/purchasing events) → GL posts accrual
   entries; auto-reversal entry on first day of next period
   (`ap.accrual.reversed`).
4. **Supplier summary**: annual aggregation for statutory export
   (`ap.summary.generated`).

### 4.5 Event catalog

`ap.entry.recorded`, `ap.run.created` / `.approved` / `.executed`,
`ap.accrual.created` / `.reversed`, `ap.summary.generated`.

### 4.6 API surface (/api/v1/ap)

| Endpoint | Permission |
|----------|-----------|
| GET supplier accounts, ledger | `ap:account:view` |
| GET/POST payment runs, POST /runs/{id}:approve, :execute | `ap:run:view|create`, `ap:run:approve` (sensitive), `ap:run:execute` (sensitive) |
| GET/POST accruals, POST /accruals/{id}:reverse | `ap:accrual:view|create`, `ap:accrual:reverse` |
| GET supplier payment summaries + export | `ap:summary:view` (export sensitive) |

Scopes: `ap:read`, `ap:write`, fine sensitive scopes.

Contracts: `PayablesAging` (bucketed payables), `CashOutflowForecast`
(due bills by week — consumed by banking cash-flow view + analytics).

### 4.7 Integration points

Consumes events: invoicing, payments, purchasing (GRNI via receipts).
Publishes to: payments (payout batches via contract), gl, analytics.

---
---

## Module: Banking & Reconciliation (module code `banking`)

### 5.1 Bounded context

Bank account registers, statement ingestion (manual upload + file formats),
matching engine (statement line ↔ payment), cash positioning, and
cheque/instrument tracking.

### 5.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `bank_accounts` | id, payment_account_id (shadow of payments account), bank_name, account_number_last4, iban/ifsc NULL, currency, opening_balance, gl_account_id | masked numbers everywhere in API/UI |
| `bank_statements` | id, bank_account_id, period_from/to, source (`upload|feed`), file_id, status (`uploaded|processing|reconciling|reconciled`), balance_open/close | |
| `statement_lines` | id, statement_id, occurred_on, description, ref (bank reference), amount, direction, match_status (`unmatched|matched|excluded`), match_ref (payment_id) | append-only once imported |
| `match_rules` | id, name, conditions JSON (amount ± tolerance, ref pattern, description keywords), action (`auto_match|suggest`), priority | matching automation |
| `reconciliation_batches` | id, statement_id, matched_count, unmatched_count, completed_at, completed_by | |
| `instruments` | id, kind (`cheque|dd`), number, payment_id NULL, status (`issued|deposited|cleared|bounced|cancelled`), instrument_date | cheque lifecycle |

### 5.3 Matching engine (normative)

For each imported statement line:

1. Run `match_rules` in priority order:
   - exact amount + exact reference match → `auto_match` links the payment,
   - amount within tolerance + keyword → `suggest` (candidate list for
     manual confirmation).
2. Unmatched lines remain in the reconciliation workbench; manual match
   creates the link (audited), or `exclude` with reason (bank charges →
   auto-create a payment record of method `bank_charge` and post via GL).
3. A statement reaches `reconciled` when every line is matched/excluded
   AND statement closing balance equals (opening + Σ lines) — invariant
   checked before close; mismatch blocks close with the delta reported.
4. Reconciliation is a **workflow** (`bank_statement` machine) with
   `banking:statement:reconcile`; closing a statement above a balance
   threshold is sensitive.

### 5.4 Primary flows

1. **Statement upload**: CSV/CAMT.053/MT940 file (driver-parsed) →
   `bank_statements` + lines → matching run → workbench → reconcile.
2. **Cash position**: daily job aggregates bank account balances + payment
   account balances → `banking.position.computed` (dashboard + analytics).
3. **Cheque lifecycle**: payment by cheque creates an instrument →
   deposited → cleared (statement match confirms) or bounced → re-open AR/AP
   entry via event (`banking.instrument.bounced` → payments creates
   reversal entry).
4. **Bank feed (Phase 5)**: provider API driver interface
   `BankFeedDriver` ships in v1 as manual-upload-only; the interface is
   stable for later providers.

### 5.5 Event catalog

`banking.statement.uploaded` / `.reconciled`,
`banking.line.matched` / `.excluded`,
`banking.instrument.issued` / `.cleared` / `.bounced`,
`banking.position.computed`.

### 5.6 API surface (/api/v1/banking)

| Endpoint | Permission |
|----------|-----------|
| CRUD bank accounts (masked responses) | `banking:account:view|configure` |
| POST /statements (upload), GET statements/lines | `banking:statement:view|upload` |
| POST /lines/{id}:match, :exclude; POST /statements/{id}:reconcile | `banking:statement:reconcile` (sensitive above threshold) |
| CRUD match rules | `banking:rule:configure` |
| GET cash position | `banking:position:view` |
| Instruments lifecycle verbs | `banking:instrument:view|update` |

Scopes: `banking:read`, `banking:write`, fine sensitive scopes.

Contracts: `CashPositionQuery` (dashboards), consumes
`PaymentStatusQuery` (payments) for matching candidates.

### 5.7 Integration points

Consumes events: payments (captured/payout/settled), ap. Publishes to: gl
(bank charges, bounce reversals), analytics.

---
---

## Dunning (Phase 4a)

Dunning was specified within the Phase 4a finance extensions ([ERP parity domains §12](./gl.md)); it is owned by the AR module.

### 12.5 Dunning (normative)

1. `ar_dunning_levels` (tenant config): level number, days-past-due
   threshold, notice template, fee (Money) NULL, channel.
   `ar_dunning_runs`: run_date, status. `ar_dunning_notices`:
   customer_id, level, invoice refs JSON, amount_due (Money), sent_at,
   run_id.
2. Run (scheduled or manual, `ar:dunning:execute`, sensitive): scans AR
   aging ([finance domains §3](./ar-ap-banking.md)) for overdue open invoices; excludes invoices with
   active disputes or payment-in-flight (payments contract); selects the
   customer's next level (max prior level + 1, capped).
3. Notices are sent via notifications (email with statement PDF, kernel
   Files); optional level fee posts via posting engine (debit AR
   dunning-fee receivable, credit fee income) — fee posting is
   idempotent per (invoice, level).
4. Events: `ar.dunning.run_completed`, `ar.dunning.notice_sent`,
   `ar.dunning.fee_charged`. Permissions:
   `ar:dunning:view|execute|configure` (execute + configure sensitive).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| ar.* / ap.* | 10³–10⁴ (mirror of invoicing/payments volume) |
| banking.* | 10²–10³ (statement lines dominate) |
| gl/ar finance extensions | 10¹–10² (recurring + dunning runs) |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `write_off` | ar | approve |
| `ap_payment_run` | ap | approve, execute (SoD vendor-master) |
| `bank_statement` | banking | reconcile (threshold) |
| `dunning_run` | ar | execute |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| banking | `PaymentStatusQuery` (payments) |
| finance extensions | posting engine (gl), aging (ar), report definitions (finance_reports) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| ar | `ar:account:view`, `ar:aging:view`, `ar:collection:view|create|update`, `ar:writeoff:create|approve` (both sensitive), `ar:statement:generate`, `ar:allocation:manage` (sensitive) |
| ap | `ap:account:view`, `ap:run:view|create`, `ap:run:approve|execute` (both sensitive), `ap:accrual:view|create|reverse`, `ap:summary:view` |
| banking | `banking:account:view|configure`, `banking:statement:view|upload|reconcile` (reconcile conditionally sensitive), `banking:rule:configure`, `banking:position:view`, `banking:instrument:view|update` |
| ar (extensions) | `ar:dunning:view|execute|configure` (execute/configure sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **AR/AP ledger invariants**: open_amount ≥ 0 always; Σ allocations ≤
   payment amount; nightly verification job detects injected drift in tests.
2. **Reconciliation close guard**: statement with balance mismatch cannot
   close; matched + excluded lines must equal total lines.
3. **SoD in finance**: journal creator ≠ approver enforced; AP run approver
   with vendor-master edit history in scope is blocked.
4. **Dunning**: level progression max+1; disputed/in-flight invoices
    excluded; fee posted once per (invoice, level); notices logged with
    statement artifact.
5. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
6. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
