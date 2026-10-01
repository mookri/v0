# Catalog Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commercial core domains §1](./catalog.md); [Shopify parity extensions §7.5–§7.6](./catalog.md)

Products, variants, taxonomies, and merchandising metadata for the commercial core. Module code `catalog`. Phase 2a added digital product setup and delivery ([Shopify parity extensions §7.5–§7.6](./catalog.md)); storefront discovery surfaces (feeds, sitemaps, redirects) live in [Shopify parity extensions §12](./store-checkout.md) under store & checkout.

---

## Module: Catalog (module code `catalog`)

### 1.1 Bounded context

The **product master** for the entire platform: products, variants,
categories, units of measure, brands. Commerce storefront catalogs ([commerce domains](./store-checkout.md))
are *projections* of this master; manufacturing BOMs ([enterprise ops domains](../hr/hr.md)) consume it.

### 1.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `products` | id, sku (UQ per tenant), name, type (`goods|service|bundle|digital`), status (`draft|active|archived`), category_id, brand_id, base_uom_id, barcode, hsn_sac (tax code), weight/volume, meta | soft delete; numbered `PRD-<seq>` |
| `product_variants` | id, product_id, sku (UQ), name, attribute_set JSON, barcode | goods-type products have ≥1 variant; services have an implicit default variant |
| `categories` | id, parent_id, name, path, depth | tree per tenant |
| `brands` | id, name | |
| `units_of_measure` | id, code (`pcs, kg, l, hr, box`), name, kind, conversions JSON | seeded + tenant-extensible |
| `product_bundles` / `product_bundle_items` | bundle variant → component variants + qty | bundle = kit sold as one line; components tracked in inventory |

Cross-module fields: none — other modules hold ids + denormalized display
attributes refreshed via events (§1.5).

### 1.3 Primary flows

1. **Product creation → activation**: draft with SKU/category/uom → variant
   matrix → activation (publishes `catalog.product.activated`; required
   before the product can appear on any document line).
2. **SKU uniqueness & barcode issuance**: tenant-scoped unique constraint;
   optional kernel-generated internal barcode.
3. **Bundle definition**: bundle variant + components; stock availability of
   a bundle = min(component_available / qty) computed via inventory contract.

### 1.4 Event catalog

| Event | Payload highlights |
|-------|--------------------|
| `catalog.product.created` | product_id, sku, type |
| `catalog.product.updated` | product_id, changed fields |
| `catalog.product.activated` | product_id, variant_ids |
| `catalog.product.archived` | product_id |
| `catalog.category.updated` | category_id, path |

### 1.5 API surface (`/api/v1/catalog`)

| Endpoint | Permission | Scope |
|----------|-----------|-------|
| `GET /products`, `GET /products/{id}` | `catalog:product:view` | `catalog:read` |
| `POST /products` | `catalog:product:create` | `catalog:write` |
| `PATCH /products/{id}` | `catalog:product:update` | `catalog:write` |
| `POST /products/{id}:activate` / `:archive` | `catalog:product:update` | `catalog:write` |
| `GET/POST/PATCH /products/{id}/variants...` | `catalog:product:*` as above | |
| `CRUD /categories`, `/brands`, `/units-of-measure` | `catalog:taxonomy:view|create|update` | `catalog:write` |
| `GET /products/{id}/availability` | `catalog:product:view` | `catalog:read` (delegates to inventory contract) |

Contracts published: `Catalog\Contracts\ProductLookup` (summary DTOs by
id/sku), `Catalog\Contracts\AvailabilityQuery` (proxy to inventory).

### 1.6 Integration points

- Consumes: nothing (leaf master data).
- Publishes to: sales, purchasing, inventory (line denormalization), commerce
  (storefront projections, [commerce domains](./store-checkout.md)), finance (HSN for tax, [finance domains](../finance/gl.md)).

---
---

## Digital products & delivery (Phase 2a)

Digital products were specified in Phase 2a alongside barcode and POS operations ([Shopify parity extensions §7](./giftcards-subscriptions-pos.md)); the digital half is owned by catalog because it extends product setup — no shipping step, signed delivery links issued at fulfillment.

### 7.5 Digital products (normative setup)

`products.type = digital` ([commercial core domains §1.2](./catalog.md)) with:
- `digital_assets`: `variant_id`, `file_id` (kernel Files), `name`,
  `download_limit`, `link_ttl_days`.
- Digital variants skip inventory reservation (inventory guard: digital
  lines reserve nothing) and skip shipping (checkout shipping step
  excluded when all lines are digital).

### 7.6 Digital delivery flow (normative)

1. Trigger: `sales.order.fulfilled` (manual fulfillment for digital, or
   auto-fulfill setting per store: `store.digital_auto_fulfill`).
2. Listener generates one **signed download URL** per digital line via
   kernel Files ([kernel services §7](../../07-kernel/kernel-services.md) signed URLs), honoring `download_limit` and
   `link_ttl_days`; URLs are single-use-counted (kernel counter).
3. Delivery: notification to the buyer with links ([kernel services §6](../../07-kernel/kernel-services.md)); links
   also appear in the buyer account order view (§5.4).
4. Exhausted/expired link ⇒ 410 `files_link_expired`; buyer may request
   re-issue (`customer_accounts` self-service, rate-limited, audited).

Events: `catalog.digital.delivery_sent`, `catalog.digital.link_exhausted`.

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| catalog.* | 10² |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| catalog | `catalog:product:view|create|update`, `catalog:taxonomy:view|create|update`, `catalog:product:configure` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Digital delivery**: fulfilled digital order issues signed links
    honoring limit/TTL; exhausted link ⇒ 410; re-issue rate-limited and
    audited; digital carts skip shipping step.
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
