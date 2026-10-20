# Contracts & Renewals Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [ERP parity domains §10](./contracts.md)

Customer contracts, the renewal orchestration scheduler (exactly one pending renewal per contract), and warranty coverage with the normative coverage probe ([ERP parity domains §10](./contracts.md)). Module code `contracts`. Contracts draft via `QuoteDraftFactory` (sales) and answer warranty queries using inventory `SerialQuery`.

---

## Module: Contracts & Renewals (module code `contracts`)

### 10.1 Bounded context

Contract lifecycle on the sell side: sales contracts, service contracts,
and warranties — with renewal orchestration. Contracts bind customers to
commitments (items, coverage windows, terms) and drive renewals through
the sales funnel. Subscription products stay in `subscriptions`
([commerce domains §7](../commerce/giftcards-subscriptions-pos.md)); this module covers **contract-style** commitments
(non-subscription agreements, SLA coverage, equipment warranties).

### 10.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `contracts` | id, number (`CTR-<yyyy>-<seq>`), customer_id, kind (`sales_contract|service_contract|warranty`), status machine (`draft|active|expired|terminated|renewed`), starts_on, ends_on, auto_renew (bool), renewal_term JSON ({months, price_rule}), currency, totals JSON, serial_ref NULL (warranties), sla_ref NULL | soft delete prohibited (audit) |
| `contract_lines` | id, contract_id, variant_id NULL, description, qty, unit_price (Money), coverage JSON NULL | |
| `contract_renewals` | id, contract_id, renewal_number, due_at, status (`pending|quoted|converted|lapsed`), quote_id NULL, converted_at NULL | one pending renewal per contract (UQ) |

### 10.3 State machine & flows (normative)

1. **Activation**: `draft → active` when signed (staff marks; audited);
   `active` requires `starts_on ≤ ends_on` and ≥1 line.
2. **Renewal orchestration**: scheduler (kernel, lock-based) scans
   contracts with `ends_on` within the tenant's renewal window
   (default 60 days) and no pending renewal → creates
   `contract_renewals` (`pending`) → optional auto-quote via the sales
   `QuoteDraftFactory` contract (§2.7) → status `quoted`.
   - `:convert` (`contracts:renewal:convert`, idempotent): extends the
     contract (`ends_on` += renewal_term; `renewal_number` + 1; price
     per `price_rule`), emits `contracts.contract.renewed`.
   - Lapse: `ends_on` passed without conversion → contract `expired`,
     renewal `lapsed`, emits `contracts.renewal.lapsed`.
3. **Termination**: `:terminate` (`contracts:contract:terminate`,
   sensitive, ACR 2, mandatory reason) → `terminated`; remaining-value
   handling (credit note) via invoicing contract.
4. **Warranties**: `kind=warranty` contracts link `serial_ref` to the
   inventory serial ledger (§8.2). Service tickets ([enterprise ops domains §4](../service/service.md)) resolve
   coverage through the `ContractCoverageQuery` contract published here:
   (serial or customer, date) → covering contract + remaining terms.
   Claim events emit `contracts.warranty.claimed` (ticket ref — no PII).

### 10.4 Event catalog

`contracts.contract.created` / `.activated` / `.expired` /
`.terminated` / `.renewed`, `contracts.renewal.due` / `.quoted` /
`.converted` / `.lapsed`, `contracts.warranty.claimed`.

### 10.5 API surface (`/api/v1/contracts`)

CRUD contracts/lines under `contracts:contract:view|create|update`;
`:activate` / `:terminate` under `contracts:contract:update` /
`contracts:contract:terminate` (sensitive); renewals list +
`:convert` under `contracts:renewal:view|convert`; coverage probe
`GET /contracts/coverage?serial=&customer=&date=` under
`contracts:contract:view` (also the contract implementation for other
modules). Scopes: `contracts:read`, `contracts:write`, fine-grained
sensitive scopes.

Contracts published: `ContractCoverageQuery` (service, POS).
Consumes: `CustomerLookup` (customers), `QuoteDraftFactory` (sales),
`SerialQuery` (inventory §8.2), invoicing (termination credit notes).
Publishes to: finance (revenue recognition postings), analytics
(renewal/churn), notifications (renewal reminders).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| contracts.* | 10¹ |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `contract` | contracts | terminate, renew |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| contracts | `CustomerLookup` (customers), `QuoteDraftFactory` (sales), `SerialQuery` (inventory) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| contracts | `contracts:contract:view|create|update|terminate` (terminate sensitive), `contracts:renewal:view|convert`, `contracts:warranty:view|configure` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Contracts & renewals**: renewal scheduler creates exactly one
    pending renewal per contract (unique constraint); `:convert`
    extends terms per `renewal_term`; lapse path expires both records;
    warranty coverage probe answers correctly for in/out-of-window
    dates; `:terminate` requires ACR 2.
2. **Tenant isolation + sensitivity**: every new table covered by
    `AssertTenantIsolation`; statutory amounts verified absent from all
    events and structured logs; NCR/contract exports audited.
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
