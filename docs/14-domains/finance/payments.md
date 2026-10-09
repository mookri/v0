# Payments Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §8](./payments.md)

> **Implementation status (B3, 2026-10-10):** the checkout slice has
> landed as module code `payment` (singular — see
> `app/Modules/Payment/`): `payment_intents` (the amount snapshot,
> unique per tenant+booking), `POST /api/v1/booking/reservations/{id}
> :confirm` (DRAFT → PENDING_PAYMENT, flag-gated on `payments.enabled`),
> and a signature-verified Razorpay webhook driving capture → CONFIRMED,
> failure → FAILED, full refund → REFUNDED. The invoicing-linked
> payments/payouts surface below (`payments` plural, capture-apply,
> payout batches, partial refunds) is still target-state — payouts and
> the refund UI are explicit non-goals of the current roadmap cycle.

Payment gateways, captures, refunds, and payout batches ([commercial core domains §8](./payments.md)). Module code `payments`. Checkout-side payment capability flags were added in [Shopify parity extensions §13](../commerce/store-checkout.md) under store & checkout.

---

## Module: Payments (module code `payments`)

### 8.1 Bounded context

Cash truth for receivables & payables: payment accounts, captures, payouts,
refunds, application to invoices/bills, gateway abstraction.

### 8.2 Core entities

| Table | Key fields |
|-------|-----------|
| `payment_accounts` | id, kind (`bank|cash|gateway`), name, currency, gateway_config_ciphertext, status |
| `payments` | id, number (`PAY-<seq>`), direction (`inbound|outbound`), method (`cash|bank_transfer|cheque|card|upi|gateway`), amount Money, status machine, account_id, ref_type/ref_id, received_at |
| `payment_applications` | payment_id → invoice_id/bill_id, applied_amount |
| `payout_batches` | id, status machine (supplier payment runs) |
| `gateway_transactions` | id, payment_id, provider, provider_ref, raw_response JSON, status |
| `refunds` | id, payment_id, amount, reason, status |

### 8.3 State machine (payment)

`pending → authorized → captured → settled`, plus `failed`, `voided`,
`refunded (partial|full)`. Inbound capture and payout approval are the two
sensitive paths; payout approval enforces SoD `sod.vendor_master_payments`.

### 8.4 Primary flows

1. **Inbound capture**: from sales order/invoice → create payment →
   gateway provider driver (interface `PaymentGatewayDriver` with `cash`
   and `bank` built-in; Stripe/Razorpay drivers land with Phase 5
   ecosystem) → captured → auto-apply to invoice → emit
   `payments.payment.captured` → invoicing updates `payment_status`.
2. **Outbound payout**: select unpaid bills → batch → approve (sensitive,
   SoD) → execute → emit `payments.payout.executed`.
3. **Refund**: against captured payment; requires reason; emits
   `payments.refund.issued` (commerce return flows consume it).
4. **Reconciliation feed**: `payments.payment.settled` events carry bank
   reference data for finance reconciliation ([finance domains §5](./ar-ap-banking.md)).

### 8.5 Event catalog

`payments.payment.created` / `.captured` / `.failed` / `.settled` /
`.voided`, `payments.payment.applied` (payment_id, invoice_id, amount),
`payments.payout.batch_approved` / `.executed`, `payments.refund.issued`.

### 8.6 API surface (`/api/v1/payments`)

CRUD/view under `payments:payment:view|create`; `POST /payments/{id}:capture`
(`payments:payment:capture`, sensitive, idempotency required);
`POST /payout-batches`, `:approve`, `:execute` (`payments:payout:create|
approve|execute`, approve/execute sensitive); `POST /payments/{id}/refunds`
(`payments:refund:create`, sensitive). Scopes: `payments:read`,
`payments:write`, fine-grained sensitive scopes.

Contracts: `PaymentStatusQuery` (by reference), `UnpaidBalanceQuery`.

### 8.7 Integration points

Consumes: invoicing (application targets), suppliers (bank details).
Publishes to: invoicing (status), sales (`payment_status`), finance (bank
ledger feed), commerce (refund completions).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| payments.* | 10³ |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `payment` / `payout_batch` | payments | capture, approve, execute |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| payments | `InvoiceLookup` / `ReceivablesAging` (invoicing) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| payments | `payments:payment:view|create|capture` (capture sensitive), `payments:payout:create|approve|execute` (approve/execute sensitive), `payments:refund:create` (sensitive), `payments:account:configure` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
2. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
