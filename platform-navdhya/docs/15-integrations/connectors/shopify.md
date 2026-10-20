# Connector Dossier — Shopify (and WooCommerce) Sync

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [commerce domains §9](../integration-framework.md); Shopify feature inventory (object mapping); [API platform §10.2](../integration-framework.md)

Dossier for the Shopify channel adapter (the WooCommerce adapter shares the same interface): sync model, object mapping, source-of-truth and conflict rules ([commerce domains §9](../integration-framework.md)). Adapter mechanics (no business rules in adapters, channel matrix) are specified in the [integration framework](../integration-framework.md).

---

## Channel adapters — sync model (module code `channels`)

### 9.1 Bounded context

Synchronization with external commerce platforms (v1: Shopify and
WooCommerce adapters behind one interface). Adapters map external catalogs,
inventory, customers and orders onto platform contracts. Adapter mechanics
per [API platform §10](../integration-framework.md).

### 9.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `channel_connections` | id, store_id, provider (`shopify|woocommerce`), credentials_ciphertext, sync_settings JSON, status | per store |
| `channel_mappings` | provider_entity (`product|variant|customer|order`), provider_id, platform_id, last_synced_hash | conflict detection |
| `channel_sync_runs` | id, connection_id, direction (`in|out`), started_at, finished_at, counts JSON, status | ops visibility |
| `channel_conflicts` | id, mapping ref, field, platform_value, channel_value, resolution status | |

### 9.3 Sync model (normative)

1. **Outbound (platform → channel)**: catalog events (publish/unpublish,
   price change via pricing events, availability via inventory events) are
   pushed through the adapter — eventual consistency, retried via outbox.
2. **Inbound (channel → platform)**: orders arrive via provider webhooks →
   adapter validates signature → maps to CheckoutCapture (orders created as
   `source=channel:<provider>`) → customer records reconciled into
   customers module (match by email; conflicts create `channel_conflicts`).
3. **Conflict policy**: platform is source of truth unless a field is
   flagged channel-owned in `sync_settings` (e.g. merchants who edit
   descriptions in Shopify). Conflicts surface in admin queue, never
   auto-overwrite platform master data.
4. **Full reconcile**: nightly job compares mapping hashes; drift →
   `channels.sync.drift_detected` + repair queue.

### 9.4 Event catalog

`channels.connection.created` / `.disconnected`,
`channels.sync.completed` (run summary), `channels.order.imported`
(order_id, provider_ref), `channels.conflict.detected`,
`channels.sync.drift_detected`.

### 9.5 API surface (`/api/v1/channels`)

CRUD connections under `channels:connection:configure` (sensitive —
credential handling); `POST /connections/{id}:test`, `:sync` (manual
full-sync trigger); sync run history under `channels:sync:view`; conflict
queue `channels:conflict:resolve`. Provider webhook ingestion endpoints
(`POST /api/v1/public/webhooks/channels/{provider}`) are kernel public
markers with provider signature verification (no token auth; HMAC per
provider scheme). Scopes: `channels:read`, `channels:write`.

Contracts: adapter interface `ChannelAdapter` (provider-agnostic; Phase 5
opens it to ecosystem adapters, [ecosystem](../../18-extensions/ecosystem.md)).

### 9.6 Integration points

Consumes: store, catalog, pricing, inventory, customers, checkout.
Publishes to: sales (orders), analytics (channel mix).

---
---

## Dossier summary

| Field | Value |
|---|---|
| Provider | Shopify (Admin API); WooCommerce shares the `ChannelAdapter` interface |
| Auth | Partner client, tenant-bound, scoped (identity client registry); credentials encrypted per connection |
| Sync directions | Outbound: catalog, pricing, availability · Inbound: orders, customers |
| Source of truth | Platform, unless a field is flagged channel-owned in `sync_settings` |
| Conflict handling | `channel_conflicts` queue resolved by admins; never auto-overwrites platform master data |
| Reconciliation | Nightly hash compare; drift ⇒ `channels.sync.drift_detected` + repair queue |
| Public ingestion | `POST /api/v1/public/webhooks/channels/{provider}` — provider HMAC only, on the public allowlist (EXP-006) |
---

## Object mapping (v1)

| Shopify object | Navdhya canonical (owner) | Direction | Notes |
|---|---|---|---|
| Product / variant | `products` / `product_variants` (catalog) | outbound | publish/unpublish; price change via pricing events |
| Collection | `collections` (store) | outbound | manual + automated rule collections |
| Inventory level | `inventory_levels` (inventory) | outbound | availability via inventory events |
| Customer | `customers` (customers) | inbound (reconciled) | matched by email; mismatches ⇒ conflicts |
| Order | `sales_orders` via `CheckoutCapture` (sales) | inbound | provider webhook ⇒ HMAC verify ⇒ created with `source=channel:shopify` |

Shopify-parity **features** (markets, order operations, customer accounts, content, local commerce, selling plans) are platform capabilities specified in the Phase 2a domain files — they are not sync objects. See the [Phase 2a decomposition index](../../14-domains/commerce/shopify-parity-extensions.md).
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| channels | `CheckoutCapture` (checkout), `CustomerLookup` (customers), catalog/pricing/inventory events |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| channels | `channels:connection:configure` (sensitive), `channels:sync:view`, `channels:conflict:resolve` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Channel import**: Shopify order webhook with invalid HMAC rejected;
   valid import creates order with `source=channel:shopify` and customer
   mapping.
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
