# Customers Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §2](./customers.md); [Shopify parity extensions §5](./customers.md), §15

Customer master data: customers, contacts, addresses, segments ([commercial core domains §2](./customers.md)). Phase 2a added buyer-facing customer accounts with store credit and merge ([Shopify parity extensions §5](./customers.md)) and B2B-lite companies with tax exemption ([Shopify parity extensions §15](./customers.md)). Module codes `customers`, `customer_accounts`, `b2b`. The CRM pipeline (leads, opportunities — [ERP parity domains §2](./crm.md)) lives in the crm file.

---

## Module: Customers (module code `customers`)

### 2.1 Bounded context

All **counterparty-master** data for the sell side: companies, contacts,
addresses, credit terms. Full CRM (pipelines/campaigns) is a later layer
that reuses these entities; v1 ships the commercial subset.

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `customers` | id, code (`CUS-<seq>`), name, legal_name, type (`individual|company`), status (`active|on_hold|blocked|archived`), tax_id, credit_limit (Money), payment_term_id, default_currency, billing/shipping defaults | soft delete |
| `contacts` | id, customer_id, name, email, phone, designation, is_primary | |
| `addresses` | id, customer_id, kind (`billing|shipping|both`), line1..country, lat/lng | one default per kind |
| `payment_terms` | id, code (`net_30`), days, discount_pct, discount_days | seeded + tenant-defined; reused by suppliers |
| `customer_credit_notes` | ledger of credit-limit changes | audit-grade, append-only |

### 2.3 Primary flows

1. **Onboarding**: create customer (+contacts/addresses) → credit limit +
   terms → `customers.customer.created`.
2. **Credit hold**: balance vs credit_limit is evaluated at sales order
   confirmation via contract `CustomerCreditCheck`; breach ⇒ workflow blocks
   confirmation pending approval (credit-exceeded approval, sensitive).
3. **Block/unblock**: status change with reason; blocked customers reject new
   documents (sales/purchasing guards consume status via contract).

### 2.4 Event catalog

| Event | Payload highlights |
|-------|--------------------|
| `customers.customer.created` / `.updated` | customer_id, status |
| `customers.customer.credit_limit_changed` | customer_id, old/new Money |
| `customers.customer.blocked` / `.unblocked` | customer_id, reason |
| `customers.contact.created` / `.updated` | contact_id |

### 2.5 API surface (`/api/v1/customers`)

| Endpoint | Permission | Scope |
|----------|-----------|-------|
| `GET /customers`, `/{id}` | `customers:customer:view` | `customers:read` |
| `POST /customers` | `customers:customer:create` | `customers:write` |
| `PATCH /customers/{id}` | `customers:customer:update` | `customers:write` |
| `POST /customers/{id}:block` / `:unblock` | `customers:customer:update` | `customers:write` |
| `POST /customers/{id}/credit-limit` | `customers:credit:configure` (sensitive) | `customers:credit:configure` |
| CRUD `/customers/{id}/contacts`, `/addresses` | `customers:contact:view|create|update` | `customers:write` |
| `GET /payment-terms` | `customers:customer:view` | `customers:read` |

Contracts published: `CustomerLookup` (DTO by id/code), `CustomerCreditCheck`
(limit, balance exposure, holds), `CustomerStatusQuery`.

### 2.6 Integration points

- Consumes: none (leaf master data).
- Publishes to: sales, invoicing (billing details), finance (AR exposure).

---
---

## Customer accounts (Phase 2a)

### 5.1 Bounded context

Buyer self-service: account registration/login on kernel identity
(planning [LD-04](../../21-adrs/ADR-0005-buyer-accounts-kernel-identity.md)), order history, wishlists, store credit, address book
self-service, and customer merge. Admin surfaces stay in `customers`
([commercial core domains §2](./customers.md)); this module owns the **buyer-facing** experience and its
data (profiles, wishlists, credit ledger).

### 5.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `buyer_profiles` | id, user_id (UQ, kernel users), customer_id NULL (link to customers record), default_address_id NULL, marketing_opt_in, locale | 1 user ↔ 1 buyer profile per tenant |
| `buyer_order_views` | profile_id, order_id, ordered_at, status_snapshot | read-model from `sales.*`/`checkout.order.placed` events ([LD-10](../../21-adrs/ADR-0011-crm-read-models-from-events.md) pattern) |
| `wishlists` / `wishlist_items` | id, profile_id, name / variant_id, position, added_at | promotes [commerce domains §1.2](../commerce/store-checkout.md) optional tables to normative |
| `store_credit_accounts` | id, profile_id, balance (Money), status (`active|frozen`) | cached balance = ledger sum (invariant-tested) |
| `store_credit_ledger` | id, account_id, kind (`issue|consume|refund|adjust|expire`), amount (signed), ref_type/ref_id, occurred_at, expires_at NULL | **append-only** |
| `customer_merge_log` | id, surviving_customer_id, merged_customer_id, merged_by, field_map JSON, occurred_at | audit-grade |

### 5.3 Buyer auth & scopes (normative)

1. Buyers authenticate through the platform authorization server
   ([identity & token platform](../../04-security/identity-and-token-platform.md)): session auth for Blade-rendered account pages in v1; PKCE
   clients reserved for the future storefront SPA.
2. Buyer client profiles ([identity & token platform §2.3](../../04-security/identity-and-token-platform.md)) carry **only** buyer scopes:
   `kernel:profile:read|manage`, `customer_accounts:read|write` (self),
   and the public checkout paths. Admin/module scopes on a buyer client
   are a boot-time validation error (architecture test).
3. All buyer self-service routes enforce `subject == resource owner`;
   there is no buyer route that accepts one user acting on another's
   profile (403 `buyer_scope_violation`).

### 5.4 Self-service flows

1. **Register/verify**: invite-less registration (store setting) or
   invite link → kernel user + `buyer_profiles` → verification email.
2. **Order history & reorder**: history from `buyer_order_views`;
   `POST /me/orders/{id}:reorder` builds a new cart from the order's
   lines (stock + buy-rule re-validated; unavailable lines reported, not
   silently dropped).
3. **Address book**: CRUD own addresses (writes through the customers
   module contract so admin and buyer views stay consistent).
4. **Wishlists**: add/remove/move-to-cart (availability shown live via
   `StockAvailability`).

### 5.5 Store credit (normative)

- **Issue**: staff (`customer_accounts:store_credit:issue`, sensitive,
  ACR 2) or return-flow resolution `store_credit` ([commerce domains §5.2](../commerce/promotions-shipping-returns.md)) credits
  the account; every issue carries a ref and optional expiry.
- **Consume**: at checkout, store credit is a payment method
  (`GiftCardPaymentMethod` pattern, [commerce domains §10.4](../commerce/store-checkout.md)) applied after other
  methods; consumption is captured in the same transaction as the order
  (unique ledger ref = order id → no double spend under concurrency).
- **Refund/adjust/expire**: refunds can route back to credit ([commerce domains §5](../commerce/promotions-shipping-returns.md));
  manual adjust is sensitive + audited; expiry runs as a batch posting
  `expire` lines.
- **Invariant**: `store_credit_accounts.balance` MUST equal
  Σ `store_credit_ledger.amount` (property test; drift ⇒ P2 alert).

### 5.6 Customer merge (normative)

`POST /api/v1/customer-accounts/merges` (`customers:customer:update` +
sensitive flag `customer_accounts:merge:execute`, ACR 2):

1. Validate both customers belong to the tenant; pick surviving record.
2. Move/rewrite references via events + contracts: contacts/addresses
   re-pointed, open orders retain historical customer id but buyer
   profile link follows the survivor, credit balances summed into one
   ledger (merge line kind), wishlists unioned (dedupe by variant).
3. Merged customer is archived with `merged_into` marker;
   `customer_merge_log` records the field map; emits
   `customer_accounts.customer.merged`.
4. Merge is irreversible — corrections are a second merge in the other
   direction (new log rows), never edits of the log.

### 5.7 Event catalog

`customer_accounts.profile.created` / `.updated`,
`customer_accounts.order.reordered`,
`customer_accounts.credit.issued` / `.consumed` / `.refunded` /
`.adjusted` / `.expired`, `customer_accounts.customer.merged`.

### 5.8 API surface (`/api/v1/customer-accounts`)

| Endpoint | Permission / auth |
|----------|-------------------|
| `GET|PATCH /me/profile`, `/me/addresses`, `/me/orders`, `POST /me/orders/{id}:reorder` | buyer self-service (owner-checked) |
| `GET|POST|DELETE /me/wishlists...` | buyer self-service |
| `GET /me/store-credit` (+ ledger) | buyer self-service (masked) |
| Admin: `GET /profiles`, `POST /store-credits` (issue), `POST /store-credits/{id}:adjust` | `customer_accounts:profile:view`, `customer_accounts:store_credit:issue|adjust` (both sensitive) |
| `POST /merges` | `customer_accounts:merge:execute` (sensitive) |

Scopes: `customer_accounts:read`, `customer_accounts:write`,
fine-grained sensitive scopes above. Contracts published:
`StoreCreditBalance` (checkout), `BuyerProfileLookup` (service/POS).

### 5.9 Integration points

Consumes: kernel identity, customers (addresses/records via contract),
sales/checkout events (order views), returns (store-credit refunds),
payments (credit as tender), notifications (verification, credit).
Publishes to: checkout (credit tender), finance (credit liability
posting), analytics.

---
---

## B2B-lite — companies & tax exemption (Phase 2a)

### 15.1 Scope (planning [LD-11](../../21-adrs/ADR-0012-b2b-lite-customer-grouping.md))

Minimal B2B structure on top of `customers`: a **company** groups customer
records with shared payment terms, tax exemption, and buyer users. Full
B2B (company-specific price lists, negotiation flows) is deferred.

### 15.2 Entities (normative)

| Table | Key fields | Notes |
|-------|-----------|-------|
| `b2b_companies` | id, name, tax_id, status, payment_term_id NULL, tax_exempt (bool), exemption_cert_file_id NULL | grouping object only |
| `b2b_company_customers` | company_id, customer_id (UQ) | customer belongs to ≤1 company |
| `b2b_company_buyers` | company_id, buyer_profile_id (customer_accounts §5.2), role (`admin|buyer`) | buyer users acting for the company |

### 15.3 Tax exemption (normative)

1. `tax_exempt=true` + uploaded certificate (kernel Files, audited) makes
   the company's orders skip tax computation at checkout pipeline step 4
   ([commerce domains §2.4](../commerce/store-checkout.md)): the tax engine receives `exempt=true` context and
   returns zero tax with reason recorded on the calculation snapshot.
2. Exemption is **company-scoped**; non-company customers and B2C flows
   are unaffected.
3. Every exempt order records `tax_exemption_ref` for statutory audit
   (feeds [ERP parity domains §9.5](../../15-integrations/connectors/india-statutory.md) return exports).
4. Toggling exemption is sensitive (`b2b:company:configure` + ACR 2) and
   emits `b2b.company.exemption_changed`.

### 15.4 Checkout behavior

Company buyers check out with their company's payment term offered as a
payment option (order_ops §3.5 terms flow) alongside gateway payment;
prices remain base pricing (company price lists deferred).

### 15.5 API & permissions

CRUD companies/members under `b2b:company:view|create|update`,
`b2b:company:configure` (exemption, sensitive). Scopes: `b2b:read|write`.
Events: `b2b.company.created` / `.updated` / `.exemption_changed`.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| customers.* / suppliers.* | 10² |
| customer_accounts.* | 10²–10³ (credit ops dominate) |
| b2b.* | 10⁰–10¹ |

### Workflow machines

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `store_credit_adjust` | customer_accounts | adjust (always) |
| `b2b_exemption` | b2b | exemption change (always, ACR 2) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| customer_accounts | `CustomerLookup` + address writes (customers), `StockAvailability` (inventory), checkout cart build |
| b2b | customers (records), order_ops (terms), tax engine (exemption context) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| customers | `customers:customer:view|create|update`, `customers:contact:view|create|update`, `customers:credit:configure` (sensitive) |
| customer_accounts | `customer_accounts:profile:view`, `customer_accounts:store_credit:issue|adjust` (both sensitive), `customer_accounts:merge:execute` (sensitive); buyer self-service scopes on buyer clients only |
| b2b | `b2b:company:view|create|update`, `b2b:company:configure` (sensitive) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Store credit invariants**: balance = ledger sum after randomized
   operation sequences (property test); checkout consumption is
   transactional with the order; issue/adjust require ACR 2; expired
   credit not spendable.
2. **Buyer scope wall**: buyer client token requesting any admin/module
   scope is rejected at issuance; buyer routes return 403
   `buyer_scope_violation` for cross-profile access (authorization-matrix
   suite).
3. **Merge correctness**: merge re-points addresses/contacts, sums credit
   via merge ledger lines, archives merged customer; merge log immutable.
4. **B2B exemption**: exempt company order records zero tax + exemption
    ref; non-company flows unaffected; exemption toggle requires ACR 2
    and emits the event.
5. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
6. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
