# Shopify Parity Extensions — Phase 2a Overview

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [Shopify parity extensions §1](./shopify-parity-extensions.md), §16–§17 (phase-level residue)

Phase 2a (Shopify parity) was decomposed into the owning domain files; this file keeps the phase's normative positioning, the decomposition index, and the phase-level residue. Every Phase 2a module section lives in its owning domain file together with its permission, volume, workflow, and verification rows.

---

## Normative positioning (Phase 2a)

1. **One order aggregate.** Order editing and draft orders operate on the
   single `sales_orders` aggregate ([commercial core domains §5.2](../sales/sales.md)); `order_ops` adds
   draft-specific tables referencing `sales_orders.id` (planning [LD-05](../../21-adrs/ADR-0006-draft-orders-sales-projection.md)).
2. **Metafields = custom-fields engine, restricted surface.** The
   metafield/metaobject storage model is the Phase 5 custom-fields engine
   ([ecosystem §5.2](../../18-extensions/ecosystem.md)) exposed early with a platform-managed definition registry
   (planning [LD-03](../../21-adrs/ADR-0004-metafields-custom-fields-engine.md)). A second storage engine is banned.
3. **Buyer accounts = kernel identity.** Buyer login reuses platform users
   ([identity & token platform §1](../../04-security/identity-and-token-platform.md)) with a buyer profile; buyer OAuth clients MUST NOT carry
   admin scopes (planning [LD-04](../../21-adrs/ADR-0005-buyer-accounts-kernel-identity.md)).
4. **Markets are a projection.** Markets derive prices from base price ×
   FX × rounding and never persist a price of record (planning [LD-06](../../21-adrs/ADR-0007-markets-pricing-overlay.md)).
5. **Order risk is post-capture annotation.** Risk scoring never blocks
   payment capture synchronously in v1 (planning [LD-12](../../21-adrs/ADR-0013-order-risk-post-capture.md)).
6. **One import/export surface.** All CSV/XLSX bulk work uses the shared
   job infrastructure in §14 (planning [LD-13](../../21-adrs/ADR-0014-single-import-export-surface.md)).

---
---

## Decomposition index — where Phase 2a content lives

| Doc 16 section | New home |
|---|---|
| §1 normative positioning | this file |
| §2 markets | [Markets](./markets.md) |
| §3 order operations | [Sales](../sales/sales.md) |
| §4 checkout buy rules | [Store & Checkout](./store-checkout.md) |
| §5 customer accounts | [Customers](../crm/customers.md) |
| §6 content | [Content](./content.md) |
| §7.1–§7.4 barcode ops & POS peripherals | [Gift Cards, Subscriptions & POS](./giftcards-subscriptions-pos.md) |
| §7.5–§7.6 digital products & delivery | [Catalog](./catalog.md) |
| §8 selling plans | [Gift Cards, Subscriptions & POS](./giftcards-subscriptions-pos.md) |
| §9 discount condition extension | [Promotions, Shipping & Returns](./promotions-shipping-returns.md) |
| §10 local commerce | [Store & Checkout](./store-checkout.md) |
| §11 abandoned checkout recovery | [Store & Checkout](./store-checkout.md) |
| §12 storefront discovery | [Store & Checkout](./store-checkout.md) |
| §13 payment capability flags | [Store & Checkout](./store-checkout.md) |
| §14 import/export & bulk operations | [Data tools](../../07-kernel/data-tools.md) |
| §15 B2B-lite | [Customers](../crm/customers.md) |
| §16.1 permission inventory | split per module into each domain file's permission summary |
| §16.2 public endpoint allowlist | [Experience architecture](../../10-experience/experience-architecture.md) + pinned in the [testing strategy](../../19-testing/testing-strategy.md) |
| §16.3–§16.5 volume/workflow/contract tables | split per module into each domain file's phase summary inputs |
| §17 verification | split per domain file; phase-wide items below |

---

## Phase-level residue (owned outside 14-domains)

| Residue | Home |
|---|---|
| `data_tools` module (§14: permissions, volume, workflow machine, verification) | [Data tools](../../07-kernel/data-tools.md) |
| Public endpoint allowlist additions (§16.2) | [Experience architecture](../../10-experience/experience-architecture.md), pinned by the [testing strategy](../../19-testing/testing-strategy.md) |

---

## Verification requirements

1. **Tenant isolation**: every new table in this phase covered by the
    `AssertTenantIsolation` matrix; all new public endpoints tested for
    cross-store/cross-tenant leakage (store slug → tenant binding).
2. **Contract tests**: every permission of this domain's modules appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
