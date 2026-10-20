# Pricing Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §4](./pricing.md)

Price lists, overrides, and the normative price resolution algorithm ([commercial core domains §4](./pricing.md)). Module code `pricing`. Market-specific pricing, FX, and duties for Phase 2a live in [Shopify parity extensions §2](../commerce/markets.md) under markets.

---

## Module: Pricing (module code `pricing`)

### 4.1 Bounded context

Deterministic price resolution used by sales (and by commerce carts):
price lists, customer-specific prices, quantity breaks, and the
**base-price** layer (commerce discounts, [commerce domains](../commerce/store-checkout.md), stack on top).

### 4.2 Core entities

| Table | Key fields |
|-------|-----------|
| `price_lists` | id, code, name, currency, priority, valid_from/to, applies_to (`all|customer_group|customer`) |
| `price_list_entries` | price_list_id, variant_id, uom, price (Money), min_qty |
| `customer_groups` | id, name; `customer_group_members` (customer_id) |
| `price_overrides` | customer_id, variant_id, price, valid_from/to (explicit contract prices) |

### 4.3 Price resolution algorithm (normative)

`PricingEngine::resolve(variant_id, customer_id, qty, date, currency)`:

1. Collect candidate sources: explicit override → customer-group price list
   entries → customer price list entries → standard price list.
2. Within a source, pick the highest-priority price list, then the best
   (lowest) qualifying quantity break.
3. Across sources: **override wins; otherwise lowest price wins** (tenant
   setting `pricing.strategy = lowest|priority` may invert).
4. Currency mismatch ⇒ 422 `pricing_currency_mismatch` (no implicit FX in
   v1; finance owns rates, [finance domains](../finance/gl.md)).
5. Result includes `source` provenance for display/audit.

The engine is a contract: `Pricing\Contracts\PriceResolver`.

### 4.4 Event catalog

`pricing.price_list.created`, `.updated`, `.activated`,
`pricing.override.created`, `pricing.override.expired`.

### 4.5 API surface (`/api/v1/pricing`)

CRUD price lists/entries/overrides under `pricing:price_list:view|create|
update`; `POST /pricing/resolve` (dry-run quote price) under
`pricing:price_list:view`. Scopes: `pricing:read`, `pricing:write`.

### 4.6 Integration points

Consumes: catalog (variants), customers (groups). Consumed by: sales
(order lines), commerce (cart pricing), finance (revenue basis).

---
---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| pricing | `pricing:price_list:view|create|update`, `pricing:override:create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
2. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
