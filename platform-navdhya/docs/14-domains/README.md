# Domain Documentation (14-domains)

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27

One file per bounded context (or tight cluster of modules), composed from the old phase documents (07, 08, 09, 10, 16, 17). Every file follows the domain template from the [domain framework](../09-domain-framework/domain-framework.md): bounded context, core entities, state machines, primary flows, event catalog, API surface, integration points — followed by the migrated phase-summary inputs, the manifest-ready permission summary, and verification requirements.

Phase-level summary tables were **split per module** rather than duplicated: each domain file carries only the permission rows, event-volume rows, workflow machines, contract-map rows, and verification items that belong to its modules. Cross-cutting rules live in the platform registers:

- capability syntax `<module>:<resource>:<action>` and sensitivity/step-up — [authorization & policy](../04-security/authorization-and-policy.md)
- module shape, manifests, transaction classes T0–T4 — [domain framework](../09-domain-framework/domain-framework.md)
- event envelope, outbox, ordering — [events & outbox](../12-events/events-and-outbox.md)
- workflow machines — [workflow engine](../11-workflow/workflow-engine.md)
- tenant isolation mechanics — [tenancy & data](../06-tenancy/tenancy-and-data.md)
- Delta (isolation) boundaries between modules — [delta isolation](../03-delta/delta-isolation.md)

## Domain index

| Domain file | Module codes | Derived from |
|---|---|---|
| [commerce/catalog.md](./commerce/catalog.md) | `catalog` | doc 07 §1; doc 16 §7.5–7.6 |
| [commerce/store-checkout.md](./commerce/store-checkout.md) | `store`, `checkout` | doc 08 §1–2; doc 16 §4, §10–13 |
| [commerce/promotions-shipping-returns.md](./commerce/promotions-shipping-returns.md) | `discounts`, `shipping`, `returns` | doc 08 §3–5; doc 16 §9 |
| [commerce/giftcards-subscriptions-pos.md](./commerce/giftcards-subscriptions-pos.md) | `giftcards`, `subscriptions`, `pos` | doc 08 §6–8; doc 16 §7.1–7.4, §8 |
| [commerce/markets.md](./commerce/markets.md) | `markets` | doc 16 §2 |
| [commerce/content.md](./commerce/content.md) | `content` | doc 16 §6 |
| [commerce/shopify-parity-extensions.md](./commerce/shopify-parity-extensions.md) | — (phase overview) | doc 16 §1, §16–17 residue |
| [sales/pricing.md](./sales/pricing.md) | `pricing` | doc 07 §4 |
| [sales/sales.md](./sales/sales.md) | `sales`, `order_ops` | doc 07 §5; doc 16 §3; doc 17 §3 (−§3.4) |
| [sales/contracts.md](./sales/contracts.md) | `contracts` | doc 17 §10 |
| [crm/customers.md](./crm/customers.md) | `customers`, `customer_accounts`, `b2b` | doc 07 §2; doc 16 §5, §15 |
| [crm/crm.md](./crm/crm.md) | `crm` | doc 17 §2 |
| [procurement/suppliers.md](./procurement/suppliers.md) | `suppliers` | doc 07 §3; doc 17 §4.5 |
| [procurement/purchasing.md](./procurement/purchasing.md) | `purchasing` | doc 07 §6; doc 17 §4 (−§4.5) |
| [inventory/inventory.md](./inventory/inventory.md) | `inventory` | doc 07 §7; doc 17 §8 |
| [finance/payments.md](./finance/payments.md) | `payments` | doc 07 §8 |
| [finance/invoicing.md](./finance/invoicing.md) | `invoicing` | doc 07 §9; doc 17 §3.4 |
| [finance/gl.md](./finance/gl.md) | `gl` | doc 09 §1; doc 17 §12.2, §12.4 |
| [finance/tax.md](./finance/tax.md) | `tax` | doc 09 §2 |
| [finance/ar-ap-banking.md](./finance/ar-ap-banking.md) | `ar`, `ap`, `banking` | doc 09 §3–5; doc 17 §12.5 |
| [finance/assets-budgets-reports.md](./finance/assets-budgets-reports.md) | `assets`, `budgets`, `finance_reports` | doc 09 §6–8; doc 17 §12.3 |
| [hr/hr.md](./hr/hr.md) | `hr` | doc 10 §1; doc 17 §5 |
| [hr/expenses.md](./hr/expenses.md) | `expenses` | doc 17 §6 |
| [payroll/payroll.md](./payroll/payroll.md) | `payroll` | doc 10 §2 |
| [projects/projects.md](./projects/projects.md) | `projects` | doc 10 §3 |
| [service/service.md](./service/service.md) | `service` | doc 10 §4 |
| [manufacturing/manufacturing.md](./manufacturing/manufacturing.md) | `manufacturing` | doc 10 §5; doc 17 §11 |
| [manufacturing/quality.md](./manufacturing/quality.md) | `quality` | doc 17 §7 |
| [supply-chain/supply-chain.md](./supply-chain/supply-chain.md) | `supply_chain` | doc 10 §6 |

## Old → new mapping (phase documents)

| Old section | New home |
|---|---|
| doc 07 §1–9 (catalog, customers, suppliers, pricing, sales, purchasing, inventory, payments, invoicing) | one domain file per module (see index above) |
| doc 07 §10 summary tables (permissions, volumes, workflows, contract map) | split per module into each file's *Permission summary* and *Phase summary inputs* |
| doc 07 §11 verification | split per module into each file's *Verification requirements* |
| doc 08 §1–2 store, cart & checkout | [store-checkout.md](./commerce/store-checkout.md) |
| doc 08 §3–5 discounts, shipping, returns | [promotions-shipping-returns.md](./commerce/promotions-shipping-returns.md) |
| doc 08 §6–8 gift cards, subscriptions, POS | [giftcards-subscriptions-pos.md](./commerce/giftcards-subscriptions-pos.md) |
| doc 08 §9 channel adapters | [15-integrations/connectors/shopify.md](../15-integrations/connectors/shopify.md) seed + [integration framework](../15-integrations/integration-framework.md) |
| doc 08 §10.2 public endpoint allowlist | [experience architecture](../10-experience/experience-architecture.md) (EXP-006), pinned by the [testing strategy](../19-testing/testing-strategy.md) |
| doc 08 §10 (rest), §11 | split per module into store-checkout / promotions / giftcards-subscriptions-pos files |
| doc 09 §1 GL | [gl.md](./finance/gl.md) |
| doc 09 §2 tax | [tax.md](./finance/tax.md) |
| doc 09 §3–5 AR, AP, banking | [ar-ap-banking.md](./finance/ar-ap-banking.md) |
| doc 09 §6–8 assets, budgets, reports | [assets-budgets-reports.md](./finance/assets-budgets-reports.md) |
| doc 09 §9.5 tenancy mode | [gl.md](./finance/gl.md) (tenancy mode section) |
| doc 10 §1–6 (HR, payroll, projects, service, manufacturing, supply chain) | one domain file per module |
| doc 10 §7.5 data sensitivity classes | [security baseline](../04-security/security-baseline.md) data classification |
| doc 16 §1 positioning + §16–17 residue | [shopify-parity-extensions.md](./commerce/shopify-parity-extensions.md) (with decomposition index) |
| doc 16 §2–15 module sections | per the [Phase 2a decomposition index](./commerce/shopify-parity-extensions.md) |
| doc 17 §1 positioning + §2 CRM | [crm.md](./crm/crm.md) |
| doc 17 §3 sales extensions (−§3.4) | [sales.md](./sales/sales.md) |
| doc 17 §3.4 pro-forma invoices | [invoicing.md](./finance/invoicing.md) |
| doc 17 §4 purchasing extensions (−§4.5) | [purchasing.md](./procurement/purchasing.md) |
| doc 17 §4.5 supplier scorecards | [suppliers.md](./procurement/suppliers.md) |
| doc 17 §5 HR extensions | [hr.md](./hr/hr.md) |
| doc 17 §6 expenses | [expenses.md](./hr/expenses.md) |
| doc 17 §7 quality | [quality.md](./manufacturing/quality.md) |
| doc 17 §8 inventory extensions | [inventory.md](./inventory/inventory.md) |
| doc 17 §9 India statutory package | [15-integrations/connectors/india-statutory.md](../15-integrations/connectors/india-statutory.md) |
| doc 17 §10 contracts & renewals | [contracts.md](./sales/contracts.md) |
| doc 17 §11 manufacturing extensions | [manufacturing.md](./manufacturing/manufacturing.md) |
| doc 17 §12.2/§12.4 cost centers, recurring journals | [gl.md](./finance/gl.md) |
| doc 17 §12.3 profitability | [assets-budgets-reports.md](./finance/assets-budgets-reports.md) |
| doc 17 §12.5 dunning | [ar-ap-banking.md](./finance/ar-ap-banking.md) |
| doc 17 §13.2 scheduled reports | [analytics](../16-analytics/analytics.md) |
| doc 17 §13.3 numbering registry | [numbering registry](../07-kernel/numbering.md) |
| doc 17 §14–15 summary/verification | split per module into each file's *Phase summary inputs* / *Verification requirements* |

## Cross-cutting verification (applies to every domain file)

Each domain file's *Verification requirements* section lists the module-specific checks migrated from the phase documents' verification sections. In addition, every domain is covered by the standing platform suites in the [testing strategy](../19-testing/testing-strategy.md):

1. **Tenant isolation** — every table is in the `AssertTenantIsolation` matrix (tenancy register, [tenancy & data](../06-tenancy/tenancy-and-data.md)); public endpoints never leak cross-store/cross-tenant data.
2. **Authorization matrix** — every permission in a file's *Permission summary* appears on its routes; negative cases (missing/revoked capability ⇒ 403) are asserted; sensitive actions require step-up (ACR 2).
3. **Contract tests** — OpenAPI fragments validate; consumer/owner contract pairs from the *Phase summary inputs* tables have architecture tests asserting no direct cross-module table access (Delta enforcement).
4. **Event ordering & idempotency** — lifecycle events fire in order with correct causation ids; duplicate commands and replayed events are no-ops.
5. **SoD** — creator ≠ approver enforced on every sensitive transition listed in the *Workflow machines* tables.

## Reading order

1. [Domain framework](../09-domain-framework/domain-framework.md) (module shape + transaction classes)
2. The domain file for the module you are implementing
3. [Delta isolation](../03-delta/delta-isolation.md) for boundary rules touching that module
4. The relevant gate in [gates & milestones](../23-evidence/gates-and-milestones.md) before marking the module done