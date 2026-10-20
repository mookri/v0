# Gap Analysis & Locked Decisions

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27 · **Migrated from:** planning/03 (documentation tree migration)

This document closes the gap between the existing architecture
(docs 00–15) and the parity scope accepted in the inventories
([01 — Shopify](./shopify-feature-inventory.md),
[02 — ERP](./erp-feature-inventory.md)). It lists the new architecture
artifacts, records every locked decision (LD-xx) with rationale, and
registers all deferrals with their preservation seams.

---

## 1. Gap Summary

### 1.1 What docs 00–15 already cover

The v1 architecture specification already implements the majority of both
surfaces: the commercial core (catalog, customers, suppliers, pricing,
sales, purchasing, inventory, payments, invoicing), the commerce engine
(store, checkout, discounts, shipping, returns, gift cards, subscriptions,
POS, channels), finance (GL, tax, AR, AP, banking, assets, budgets,
reports), enterprise ops (HR, payroll, projects, service, manufacturing,
supply chain), the ecosystem (SDK, partners, webhooks, marketplace, custom
objects, SCIM) and intelligence (analytics, forecasting, anomaly,
recommendations, copilot, agents). Inventory rows marked `COVERED` need no
new specification work.

### 1.2 Gaps requiring new architecture (NEW-v1)

| Gap area | Source rows | Resolution artifact |
|----------|-------------|---------------------|
| Markets, multi-currency display, duties | SH-150…155 | [Shopify parity extensions §2](../14-domains/commerce/markets.md) |
| Draft orders, order editing, deposits, payment terms, order risk | SH-045…053, 076, 078 | [Shopify parity extensions §3](../14-domains/sales/sales.md) |
| Checkout buy rules | SH-011 | [Shopify parity extensions §4](../14-domains/commerce/store-checkout.md) |
| Buyer accounts, wishlists, store credit, customer merge | SH-092…098 | [Shopify parity extensions §5](../14-domains/crm/customers.md) |
| Metafields/metaobjects, pages, blogs, menus, translations | SH-007/008, 112–114, 154 | [Shopify parity extensions §6](../14-domains/commerce/content.md) |
| Barcode operations, POS peripherals, digital delivery | SH-012, 030, 147–149 | [Shopify parity extensions §7](../14-domains/commerce/giftcards-subscriptions-pos.md) |
| Selling plans (preorders, subscription extensions) | SH-014 | [Shopify parity extensions §8](../14-domains/commerce/giftcards-subscriptions-pos.md) |
| Discount condition extension (sale-item toggle) | SH-067 | [Shopify parity extensions §9](../14-domains/commerce/promotions-shipping-returns.md) |
| BOPIS, local delivery, store locator | SH-050/051, 121, 148 | [Shopify parity extensions §10](../14-domains/commerce/promotions-shipping-returns.md) |
| Abandoned-checkout recovery | SH-052, 132 | [Shopify parity extensions §11](../14-domains/commerce/store-checkout.md) |
| Feeds, sitemaps, redirects | SH-010, 116/117 | [Shopify parity extensions §12](../14-domains/commerce/store-checkout.md) |
| Payment capability flags (wallets) | SH-071 | [Shopify parity extensions §13](../14-domains/commerce/store-checkout.md) |
| Import/export & bulk operations | SH-009, 056, 176, 185, 238 | [Shopify parity extensions §14](../07-kernel/data-tools.md) |
| B2B-lite (companies, tax exemption) | SH-099, 162 | [Shopify parity extensions §15](../14-domains/crm/customers.md) |
| CRM (leads, opportunities, pipelines, activities, campaigns) | ERP-001…009 | [ERP parity domains §2](../14-domains/crm/crm.md) |
| Blanket orders, backorders, pro-forma, drop-ship | ERP-022…029, 194 | [ERP parity domains §3](../14-domains/sales/sales.md) |
| Requisitions, blanket POs, landed cost, scorecards, subcontracting | ERP-040…048 | [ERP parity domains §4](../14-domains/procurement/purchasing.md) |
| Onboarding/offboarding checklists | ERP-118 | [ERP parity domains §5](../14-domains/hr/hr.md) |
| Expenses & reimbursements | ERP-094, 120 | [ERP parity domains §6](../14-domains/hr/expenses.md) |
| Quality management (inspections, NCR, CAPA) | ERP-044, 175, 244 | [ERP parity domains §7](../14-domains/manufacturing/quality.md) |
| Serial & lot tracking, valuation, aging, pick/put-away | ERP-061…067 | [ERP parity domains §8](../14-domains/inventory/inventory.md) |
| India statutory package (e-invoice, e-way, GSTR, TDS, PF/ESI) | ERP-114, 211–215, SH-163/164 | [ERP parity domains §9](../15-integrations/connectors/india-statutory.md) |
| Cost centers, profitability, recurring journals, dunning | ERP-086/087/092/093/135 | [ERP parity domains §12](../14-domains/finance/gl.md) |
| Contracts, renewals, service contracts & warranties | ERP-154, 245 | [ERP parity domains §10](../14-domains/sales/contracts.md) |
| Shop-floor control, by-products/scrap | ERP-174, 176 | [ERP parity domains §11](../14-domains/manufacturing/manufacturing.md) |
| Scheduled reports, document numbering registry | ERP-237, 242 | [ERP parity domains §13](../16-analytics/analytics.md) |
| Authorization capability model (policies, DENY, delegation, decision log) | pasted authz analysis | [authorization & policy §12–§16](../04-security/authorization-and-policy.md) |

### 1.3 New modules introduced by parity scope

| Module code | Phase | Owner doc | Consumes |
|-------------|-------|-----------|----------|
| `markets` | 2a | [Shopify parity extensions §2](../14-domains/commerce/markets.md) | store, pricing, tax, finance (FX) |
| `order_ops` | 2a | [Shopify parity extensions §3](../14-domains/sales/sales.md) | sales, invoicing, payments |
| `customer_accounts` | 2a | [Shopify parity extensions §5](../14-domains/crm/customers.md) | customers, kernel identity |
| `content` | 2a | [Shopify parity extensions §6](../14-domains/commerce/content.md) | store, kernel Files/Search |
| `selling_plans` (extension of `subscriptions`) | 2a | [Shopify parity extensions §8](../14-domains/commerce/giftcards-subscriptions-pos.md) | subscriptions, checkout |
| `crm` | 4a | [ERP parity domains §2](../14-domains/crm/crm.md) | customers, sales |
| `expenses` | 4a | [ERP parity domains §6](../14-domains/hr/expenses.md) | hr, payments, invoicing, gl |
| `quality` | 4a | [ERP parity domains §7](../14-domains/manufacturing/quality.md) | inventory, purchasing, manufacturing |
| `contracts` | 4a | [ERP parity domains §10](../14-domains/sales/contracts.md) | customers, invoicing, service |

Everything else in the parity scope is specified as **extensions** to
existing modules (checkout, discounts, pos, shipping, sales, purchasing,
inventory, hr, payroll, gl, ar, manufacturing, finance_reports), keeping
module boundaries and extraction rules intact.

---

## 2. Locked Decisions (normative for docs 16/17 and docs/specs)

### [LD-01](../21-adrs/ADR-0002-capability-colon-syntax.md) — Capability identifier syntax stays `<module>:<resource>:<action>`

The reviewed authorization analysis recommends dot notation
(`sales.order.approve`). Navdhya **retains the colon form** as the
canonical capability identifier because: (a) every manifest, route
middleware, scope string, SoD rule, and OpenAPI `x-permission` in
docs 01–15 already uses it; (b) OAuth scopes mirror permission codes
1:1 ([gap analysis](./gap-analysis.md), [authorization & policy §6](../04-security/authorization-and-policy.md)) and changing separators would force an API
version bump. The *substance* of the recommendation — structured
capability metadata, business actions over CRUD, capability/policy
separation, scopes and conditions as separate objects, explicit DENY,
delegation, decision logging — is adopted in full ([authorization & policy §12–§16](../04-security/authorization-and-policy.md)).

### [LD-02](../21-adrs/ADR-0003-additive-capability-model.md) — Capability model is additive, never breaking

Doc 04 §1–§10 behavior is unchanged: roles grant capabilities; scopes cap
clients; effective authority stays
`scopes ∩ grants ∩ membership` constrained by ABAC + SoD. The capability
upgrade adds: parsed capability metadata (§12), declarative policies
(§13), deterministic DENY precedence (§14), delegations (§15), and a
decision log (§16). No existing permission code, scope, or route
middleware changes.

### [LD-03](../21-adrs/ADR-0004-metafields-custom-fields-engine.md) — Metafields are the v1 preview of custom fields

Shopify-style metafields ([Shopify parity extensions §6](../14-domains/commerce/content.md)) are implemented on the same storage
model as Phase 5 custom fields ([ecosystem §5.2](../18-extensions/ecosystem.md)) with a fixed definition
registry; Phase 5 promotes the engine to tenant-defined custom
fields/objects. Two implementations are banned: [Shopify parity extensions §6](../14-domains/commerce/content.md) MUST be the
[ecosystem §5](../18-extensions/ecosystem.md) engine with a restricted surface.

### [LD-04](../21-adrs/ADR-0005-buyer-accounts-kernel-identity.md) — Buyer accounts reuse kernel identity

Customer/buyer accounts ([Shopify parity extensions §5](../14-domains/crm/customers.md)) are platform users ([gap analysis](./gap-analysis.md)) with a
buyer profile attached; no second user system. Buyer auth uses the same
OAuth authorization server (PKCE for the future storefront SPA, session
for Blade-rendered account pages in v1). Buyer tokens MUST NOT carry
admin scopes (client profile restriction, [identity & token platform §2.3](../04-security/identity-and-token-platform.md)).

### [LD-05](../21-adrs/ADR-0006-draft-orders-sales-projection.md) — Draft orders are a sales-module projection, not a new order model

`order_ops` ([Shopify parity extensions §3](../14-domains/sales/sales.md)) creates `sales_orders` with `status=draft`
(source `draft_order`); there is exactly one order aggregate in the
platform. Draft-specific behavior (invoice link, deposit, payment terms)
lives in `order_ops` tables referencing the sales order id.

### [LD-06](../21-adrs/ADR-0007-markets-pricing-overlay.md) — Markets are a pricing/display overlay, never a stock or money truth

Markets ([Shopify parity extensions §2](../14-domains/commerce/markets.md)) compose store + currency + duty + tax-display rules.
They read FX rates from finance ([finance domains §1.5](../14-domains/finance/gl.md)) and never store their own
prices of record; market prices are derived (base price × FX × rounding
rule) and cached as projections.

### [LD-07](../21-adrs/ADR-0008-statutory-channel-adapters.md) — Statutory adapters are channel adapters behind feature flags

GST e-invoicing, e-way bills, GSTR exports, PF/ESI filing ([ERP parity domains §9](../15-integrations/connectors/india-statutory.md)) are
implemented as **channel adapters** ([API platform §10](../15-integrations/integration-framework.md)) + report exporters,
enabled per tenant by the `module.india_statutory` feature flag. No
statutory logic is hard-coded into the tax engine core; the tax engine
([finance domains §2](../14-domains/finance/tax.md)) stays jurisdiction-generic. Provider endpoints (NIC IRN,
portal APIs) are configuration, not code dependencies.

### [LD-08](../21-adrs/ADR-0009-serial-lot-inventory-contracts.md) — Serial/lot tracking lives in inventory, exposed by contract

Serial & batch ledgers ([ERP parity domains §8](../14-domains/inventory/inventory.md)) extend the inventory module's
movement model (every movement may carry serial/batch refs); commerce and
service consume via `StockAvailability`/new `TraceQuery` contracts. No
other module stores serial state.

### [LD-09](../21-adrs/ADR-0010-quality-event-driven-gates.md) — Quality is an event-driven gate module

Quality ([ERP parity domains §7](../14-domains/manufacturing/quality.md)) never blocks writes synchronously except at declared
inspection gates (GRN inspection flag, work-order gate operations). Gate
evaluation consumes the same workflow engine approval policies ([kernel services](../07-kernel/kernel-services.md)
§4.3); NCR/CAPA are workflow-managed records.

### [LD-10](../21-adrs/ADR-0011-crm-read-models-from-events.md) — CRM read-models are built from events, not cross-module queries

Customer 360 and pipeline forecasting ([ERP parity domains §2.8](../14-domains/crm/crm.md)) are read-models
refreshed from `customers.*`, `sales.*`, `service.*` events and contract
lookups, honoring the no-cross-module-join rule ([domain framework §5](../02-architecture/architecture-master.md)).

### [LD-11](../21-adrs/ADR-0012-b2b-lite-customer-grouping.md) — B2B-lite reuses customers; companies are grouping objects

B2B ([Shopify parity extensions §15](../14-domains/crm/customers.md)) does not create a parallel counterparty model. A
`company` groups customer records with shared payment terms, tax
exemption, and buyer users. Full B2B (price lists per company, net terms
workflows beyond v1) is deferred with the seam recorded (§3.3).

### [LD-12](../21-adrs/ADR-0013-order-risk-post-capture.md) — Order risk is a consumer, not a gateway

Order risk assessment ([Shopify parity extensions §3.6](../14-domains/sales/sales.md)) annotates orders post-capture and
feeds anomaly detection ([intelligence §3](../17-ai/intelligence.md)); it MUST NOT block payment capture
synchronously in v1 (fraud-blocking rules are a post-v1 policy layer).

### [LD-13](../21-adrs/ADR-0014-single-import-export-surface.md) — Import/export is one kernel-backed job surface

CSV/XLSX import and export ([Shopify parity extensions §14](../07-kernel/data-tools.md)) is a single shared job
infrastructure (kernel Jobs + Files + idempotency) used by every module;
per-module importers declare column mappings declaratively. No module
ships its own ad-hoc import UI/API mechanics.

### [LD-14](../21-adrs/ADR-0015-headless-storefront-contract.md) — Theme rendering stays deferred; the storefront API is the contract

Native theme/template rendering (Liquid-class) is DEFERRED to v2. The
v1 storefront is contract-complete headless ([commerce domains §1.5](../14-domains/commerce/store-checkout.md) public buyer
API + [Shopify parity extensions §12](../14-domains/commerce/store-checkout.md) feeds/sitemaps); any v1 rendered storefront consumes the
same public API.

### [LD-15](../21-adrs/ADR-0016-policies-tenant-data.md) — Authorization policies are tenant data with platform defaults

Policies ([authorization & policy §13](../04-security/authorization-and-policy.md)) follow the SoD pattern: platform-default policies
(`tenant_id NULL`) are overridable but not silently removable; tenant
policies are audited, versioned, and evaluated at decision time with a
bounded condition grammar. Free-form expression languages are banned.

### [LD-16](../21-adrs/ADR-0017-delegation-time-boxed.md) — Delegations are time-boxed, revocable, and SoD-checked

Delegation ([authorization & policy §15](../04-security/authorization-and-policy.md)) grants are always bounded by an expiry, carry the
grantor's scope restrictions, are re-checked against SoD at assignment
and at use, and emit dedicated events. Self-delegation and upward
delegation (to a membership with broader grants) are rejected.

---

## 3. Deferral Register

| ID | Feature | Target | Preservation seam |
|----|---------|--------|-------------------|
| DF-01 | Combined listings | v2 | storefront projection N:1 variant mapping ([commerce domains §1.2](../14-domains/commerce/store-checkout.md)) |
| DF-02 | BNPL providers | provider adapters | `PaymentGatewayDriver` interface ([commercial core domains §8](../14-domains/finance/payments.md)) |
| DF-03 | Loyalty | P5 app | metafields + custom objects ([ecosystem §5](../18-extensions/ecosystem.md)) |
| DF-04 | Theme rendering + visual editor | v2 | headless storefront API ([commerce domains §1.5](../14-domains/commerce/store-checkout.md)) |
| DF-05 | Reviews/ratings | P5 app | metafields + custom objects |
| DF-06 | Campaign email/SMS, pixels | P5/P6 | notifications channels + segments |
| DF-07 | GraphQL layer | post-v1 | OpenAPI contracts ([API platform §9](../07-kernel/api-platform.md)) |
| DF-08 | Checkout UI extensions | post-v1 | hooks ([ecosystem §1.2](../18-extensions/ecosystem.md)) |
| DF-09 | App bridge/embedded admin | post-v1 | Blade↔API convergence ([API platform §8](../07-kernel/api-platform.md)) |
| DF-10 | Localized domains | post-v1 | store slug routing |
| DF-11 | Translation tooling | post-v1 (schema ships in v1) | [Shopify parity extensions §6.6](../14-domains/commerce/content.md) storage |
| DF-12 | Consignment (sales & vendor) | post-v1 | inventory ownership/location kinds |
| DF-13 | Quotas & commissions | P4a+ | payroll components + analytics |
| DF-14 | Recruitment/ATS | post-v1 | HR module structure |
| DF-15 | Performance management | post-v1 | HR + workflow |
| DF-16 | Shift scheduling | post-v1 | attendance policies |
| DF-17 | Employee loans/advances | post-v1 | payroll line components |
| DF-18 | Resource planning | post-v1 | projects + HR org |
| DF-19 | Field service | post-v1 | service module + scheduling |
| DF-20 | Customer support portal | post-v1 | public API pattern |
| DF-21 | Full MRP II / capacity planning | post-v1 | routings + work-center calendars |
| DF-22 | EDI | partner adapters | channel adapters ([API platform §10](../15-integrations/integration-framework.md)) |
| DF-23 | Multi-company & consolidation | post-v1 | org tree + dimensions + FX translation |
| DF-24 | ITC reconciliation (2A/2B) | v1.1 | tax ledger + import jobs |
| DF-25 | Fraud-blocking rules | post-v1 | order-risk annotations ([LD-12](../21-adrs/ADR-0013-order-risk-post-capture.md)) |

---

## 4. Authorization Model Upgrade (decision record)

The reviewed analysis concluded that `domain:resource:action` is a good
primitive but not a complete enterprise authorization model, and
recommended: capabilities with structured metadata; separation of
permission from policy; business actions over CRUD; scopes and conditions
as separate dimensions (never encoded into the identifier); explicit DENY
with deterministic precedence; delegation; separation of duties; an
authorization decision log; and AI-safe authorization for agents.

**Decision: adopted in full, adapted per [LD-01](../21-adrs/ADR-0002-capability-colon-syntax.md)/[LD-02](../21-adrs/ADR-0003-additive-capability-model.md).** The normative
specification lives in [authorization & policy §12–§16](../04-security/authorization-and-policy.md):

- **§12 Capability Model** — capabilities are parsed
  (`domain`, `resource`, `action`) with version + sensitive metadata;
  action vocabulary is open per module (declared in manifests) with the
  closed kernel set as floor; capability registry API additions.
- **§13 Policies** — declarative `policies` (effect allow/deny,
  capability selector, scope bindings, bounded condition grammar);
  role-assignment scoping (`scope_type`/`scope_id`); evaluation order
  integration at §5.1 step 5.
- **§14 Explicit Deny & Precedence** — deterministic chain:
  explicit DENY → security constraints (SoD, tenant/membership state) →
  policy constraints → explicit ALLOW (grants ∩ scopes) → default DENY.
- **§15 Delegation** — time-boxed, revocable, SoD-checked delegation
  grants with dedicated events and audit.
- **§16 Decision Log & AI-Agent Safety** — sampled allow / full deny
  decision log feeding anomaly detection; normative rules for agent
  authorization (per-call re-evaluation, machine-token sensitive-scope
  ban, budget-guarded tool calls).

Supporting schema additions land in [tenancy & data §2.1](../06-tenancy/tenancy-and-data.md) (central tables:
`policies`, `delegations`, `authorization_decisions`) and [kernel services §2.4](../12-events/events-and-outbox.md)
(kernel events: `platform.policy.*`, `platform.delegation.*`).

---

## 5. Verification (this document)

Accepted when:
1. Every `NEW-v1` inventory row maps to exactly one artifact in §1.2.
2. Every locked decision is reflected verbatim in the owning architecture
   doc (docs 04, 16, 17).
3. Every deferral names a concrete seam that exists in docs 00–15 or in
   [Shopify parity extensions](../14-domains/commerce/shopify-parity-extensions.md)/17.
4. No decision contradicts an existing [architecture master](../02-architecture/architecture-master.md)–15 normative statement
   (checked in the consistency pass).
