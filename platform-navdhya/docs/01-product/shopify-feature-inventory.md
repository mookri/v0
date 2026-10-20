# Shopify Feature Inventory

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27 · **Migrated from:** planning/01 (documentation tree migration)

This inventory catalogs the **complete Shopify platform feature surface**
(commerce engine, admin, storefront, checkout, POS, and platform APIs as of
the 2025–2026 surface) and assigns each feature a v1 disposition with the
Navdhya architecture reference that implements or absorbs it.

Disposition codes per [spec methodology §2](../specs/README.md): `COVERED`, `NEW-v1`,
`DEFERRED`, `OUT`.

Architecture target for all `NEW-v1` rows:
**[[Shopify parity extensions](../14-domains/commerce/shopify-parity-extensions.md) — Phase 2a: Shopify Feature Parity](../14-domains/commerce/shopify-parity-extensions.md)**
unless the row says otherwise.

---

## 1. Products & Catalog

| ID | Feature | Shopify behavior (summary) | Disposition | Navdhya implementation |
|----|---------|---------------------------|-------------|------------------------|
| SH-001 | Products CRUD | Title, description (rich text), vendor, status, tags | COVERED | [commercial core domains §1](../14-domains/commerce/catalog.md) Catalog (products); rich description on storefront projection |
| SH-002 | Variants | Up to 3 options (size/color/material), per-variant price, SKU, barcode, weight | COVERED | [commercial core domains §1.2](../14-domains/commerce/catalog.md) `product_variants` attribute_set; ≤3 option axes enforced as validation |
| SH-003 | Multiple images per product/variant | Ordered media gallery, per-variant image assignment | COVERED | [commercial core domains §1](../14-domains/commerce/catalog.md) + kernel Files ([kernel services §7](../07-kernel/kernel-services.md)); `storefront_products.images` JSON ordered list ([commerce domains §1.2](../14-domains/commerce/store-checkout.md)) |
| SH-004 | Product types & tags | Free-form type + tags drive filters/automation | COVERED | [commercial core domains](../14-domains/sales/pricing.md) categories + `storefront_products.tags` ([commerce domains §1.2](../14-domains/commerce/store-checkout.md)) |
| SH-005 | Collections | Manual and automated (rule-based) collections | COVERED | [commerce domains §1.2](../14-domains/commerce/store-checkout.md) `collections` kind `manual|automated` with rules JSON |
| SH-006 | Product status & channels | Published/hidden, per-channel publish state | COVERED | [commerce domains §1.2](../14-domains/commerce/store-checkout.md) `storefront_products.visibility` + `sales_channels` |
| SH-007 | Metafields on products/variants/customers/orders | Structured extension data with definitions | NEW-v1 | [Shopify parity extensions §6](../14-domains/commerce/content.md) (Metafields v1; preview of Phase 5 custom fields, [ecosystem §5](../18-extensions/ecosystem.md)) |
| SH-008 | Metaobjects (structured content) | Reusable content objects referenced from storefront | NEW-v1 | [Shopify parity extensions §6.3](../14-domains/commerce/content.md) (metaobject subset via metafield definitions on a `content` namespace) |
| SH-009 | Bulk import/export (CSV) | Product/customer/order CSV import with mapping | NEW-v1 | [Shopify parity extensions §14](../07-kernel/data-tools.md) (import/export jobs via kernel Jobs + Files) |
| SH-010 | Product feeds (Google/Meta channels) | Generated feed for listing channels | NEW-v1 | [Shopify parity extensions §12](../14-domains/commerce/store-checkout.md) (feeds as channel projection exports) |
| SH-011 | Quantity rules (min/max per order) | Buying rules per product | NEW-v1 | [Shopify parity extensions §4.4](../14-domains/commerce/store-checkout.md) (line-level buy rules validated in checkout pipeline) |
| SH-012 | Digital products/downloads | Fulfill digital items by download link | NEW-v1 | catalog type `digital` exists ([commercial core domains §1.2](../14-domains/commerce/catalog.md)); [Shopify parity extensions §7.6](../14-domains/commerce/catalog.md) adds the signed-URL delivery flow on fulfillment |
| SH-013 | Product bundles/kits | Merchandising bundles | COVERED | [commercial core domains §1.2](../14-domains/commerce/catalog.md) `product_bundles` |
| SH-014 | Preorders | Sell before stock exists | NEW-v1 | [Shopify parity extensions §8](../14-domains/commerce/giftcards-subscriptions-pos.md) selling plans (`kind=preorder`) |
| SH-015 | Combined listings (product grouping) | Group variants across products into one listing | DEFERRED (v2) | seam: storefront projection already supports N variants → 1 storefront_product row |

## 2. Inventory & Fulfillment

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-020 | Multi-location inventory | Stock per location, priority routing | COVERED | [commercial core domains §7](../14-domains/inventory/inventory.md) warehouses/locations |
| SH-021 | Stock quantities & history | Ledger of changes | COVERED | [commercial core domains §7.2](../14-domains/inventory/inventory.md) `inventory_movements` append-only |
| SH-022 | Stock transfers | Move stock between locations | COVERED | [commercial core domains §7.4](../14-domains/inventory/inventory.md) flow 4 transfers |
| SH-023 | Stock adjustments | Manual corrections with reason | COVERED | [commercial core domains §7.4](../14-domains/inventory/inventory.md) flow 5 adjustments |
| SH-024 | Low-stock alerts | Threshold notifications | COVERED | [commerce domains §1.2](../14-domains/commerce/store-checkout.md) `storefront_inventory_views` + `store.availability.changed` event → notifications |
| SH-025 | Fulfillment orders (pick/pack workflow) | Fulfillment requests with statuses | COVERED | [commercial core domains §7.4](../14-domains/inventory/inventory.md) flow 3 + [commerce domains §4](../14-domains/commerce/promotions-shipping-returns.md) shipments (pick/pack/ship) |
| SH-026 | Partial fulfillments | Fulfill subset of order lines | COVERED | [commerce domains §4.4](../14-domains/commerce/promotions-shipping-returns.md) flow 4 partial shipments |
| SH-027 | Fulfillment services (3PL) | Delegate fulfillment to external service | COVERED | [commercial core domains §7.2](../14-domains/inventory/inventory.md) warehouse type `3pl` + channel adapter ([API platform §10](../15-integrations/integration-framework.md)) |
| SH-028 | Shipping labels & carriers | Buy labels via carrier integrations | COVERED | [commerce domains §4](../14-domains/commerce/promotions-shipping-returns.md) carriers + `shipment_packages.label_file_id` |
| SH-029 | Order tracking pages | Customer-facing tracking | COVERED | [commerce domains §4.6](../14-domains/commerce/promotions-shipping-returns.md) public tracking endpoint |
| SH-030 | Barcode workflows (receive/pick) | Scan-based receiving and picking | NEW-v1 | [Shopify parity extensions §7.3](../14-domains/commerce/giftcards-subscriptions-pos.md) (barcode operations on POS/warehouse screens; scan input = sku/barcode resolve API) |
| SH-031 | Backorder/oversell control | Continue-when-out-of-stock flag | COVERED | [commerce domains §1.3](../14-domains/commerce/store-checkout.md) flow 3 backorder setting |
| SH-032 | Serial number tracking | Per-unit tracking | NEW-v1 | [ERP parity domains §8](../14-domains/inventory/inventory.md) (Serial & Lot tracking — ERP parity owns it; commerce reads via inventory contract) |
| SH-033 | Lot/batch & expiry tracking | Perishables/expiry | NEW-v1 | [ERP parity domains §8](../14-domains/inventory/inventory.md) (same module; GRN batch/expiry fields exist in [commercial core domains §6.2](../14-domains/procurement/purchasing.md)) |
| SH-034 | Demand planning/replenishment | Forecast-driven purchasing | COVERED | [enterprise ops domains §6](../14-domains/supply-chain/supply-chain.md) supply chain + [intelligence §2](../17-ai/intelligence.md) forecasting |

## 3. Orders & Checkout

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-040 | Order capture (web checkout) | Convert cart to order | COVERED | [commerce domains §2](../14-domains/commerce/store-checkout.md) checkout |
| SH-041 | Guest checkout | No account required | COVERED | [commerce domains §2.2](../14-domains/commerce/store-checkout.md) guest carts (guest_token) |
| SH-042 | Multiple payment providers | Cards, wallets, manual methods | COVERED | [commercial core domains §8](../14-domains/finance/payments.md) `PaymentGatewayDriver` |
| SH-043 | Payment capture/refund/void | Gateway operations per order | COVERED | [commercial core domains §8](../14-domains/finance/payments.md) flows 1–3 |
| SH-044 | Order statuses & tags | Fulfillment + payment + custom tags | COVERED | [commercial core domains §5.2](../14-domains/sales/sales.md) order statuses + tags column added ([Shopify parity extensions §3.2](../14-domains/sales/sales.md)) |
| SH-045 | Draft orders | Staff-created orders, send invoice link | NEW-v1 | [Shopify parity extensions §3](../14-domains/sales/sales.md) (draft orders module surface) |
| SH-046 | Order editing | Staff edits after placement (add/remove lines, restock) | NEW-v1 | [Shopify parity extensions §3.4](../14-domains/sales/sales.md) (order edit flow with recalculated totals + events) |
| SH-047 | Order cancellation with restock | Cancel + optional restock lines | COVERED | [commercial core domains §5.4](../14-domains/sales/sales.md) flow 4 + returns restock; [Shopify parity extensions §3.4](../14-domains/sales/sales.md) adds explicit restock flag |
| SH-048 | Split fulfillment | Multiple shipments per order | COVERED | [commerce domains §4.4](../14-domains/commerce/promotions-shipping-returns.md) flow 4 |
| SH-049 | Order notes & attributes | Checkout attributes, internal notes | NEW-v1 | [Shopify parity extensions §3.2](../14-domains/sales/sales.md) (`order_notes` internal vs customer-visible; checkout attributes JSON) |
| SH-050 | Buy online pick up in store (BOPIS) | Pickup points at checkout | NEW-v1 | [Shopify parity extensions §10](../14-domains/commerce/promotions-shipping-returns.md) (local pickup) |
| SH-051 | Local delivery | Merchant delivers, fee + zone | NEW-v1 | [Shopify parity extensions §10](../14-domains/commerce/promotions-shipping-returns.md) (local delivery method) |
| SH-052 | Abandoned checkout recovery | Emails + link restore | NEW-v1 | [Shopify parity extensions §11](../14-domains/commerce/store-checkout.md) (recovery pipeline on `checkout.cart.abandoned`) |
| SH-053 | Order risk analysis | Fraud signals on orders | NEW-v1 | [Shopify parity extensions §3.6](../14-domains/sales/sales.md) (risk assessment consumer; feeds [intelligence](../17-ai/intelligence.md) anomaly rules) |
| SH-054 | Duplicate order detection | Prevent double capture | COVERED | idempotency ([API platform §5](../07-kernel/api-platform.md)) + checkout reconciliation ([commerce domains §2.5](../14-domains/commerce/store-checkout.md) flow 6) |
| SH-055 | Customer-facing order status | "Where is my order" page | COVERED | [commerce domains](../14-domains/commerce/store-checkout.md) public tracking + [Shopify parity extensions §5.2](../14-domains/crm/customers.md) customer account order views |
| SH-056 | Order export (CSV) | Reporting exports | NEW-v1 | [Shopify parity extensions §14](../07-kernel/data-tools.md) (exports) |
| SH-057 | Editable customer fields at checkout | Shipping instructions etc. | COVERED | checkout addressing step ([commerce domains §2.3](../14-domains/commerce/store-checkout.md)) + SH-049 attributes |

## 4. Discounts & Promotions

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-060 | Discount codes | Percentage/fixed/free-shipping | COVERED | [commerce domains §3](../14-domains/commerce/promotions-shipping-returns.md) rules + coupons |
| SH-061 | Automatic discounts | Apply without code | COVERED | [commerce domains §3.4](../14-domains/commerce/promotions-shipping-returns.md) flow 2 |
| SH-062 | Buy X get Y | Rule-based freebie | COVERED | [commerce domains §3.3](../14-domains/commerce/promotions-shipping-returns.md) buy_x_get_y |
| SH-063 | Volume/quantity discounts | Tier pricing | COVERED | pricing quantity breaks ([commercial core domains §4.3](../14-domains/sales/pricing.md)) + discounts conditions |
| SH-064 | Customer-segmented discounts | By group/eligibility | COVERED | [commerce domains §3.2](../14-domains/commerce/promotions-shipping-returns.md) conditions (customer groups) |
| SH-065 | Usage limits (total/per-customer) | Redemption caps | COVERED | [commerce domains §3.2](../14-domains/commerce/promotions-shipping-returns.md) limits + §3.4 flow 1 concurrency-safe ledger |
| SH-066 | Discount combinations & priorities | Stacking control | COVERED | [commerce domains §3.3](../14-domains/commerce/promotions-shipping-returns.md) combinable flag + priority + stacking cap |
| SH-067 | Discount on sale items toggle | Include/exclude already-discounted | NEW-v1 | [Shopify parity extensions §9.2](../14-domains/commerce/promotions-shipping-returns.md) condition `applies_on_sale_items` added to rules |
| SH-068 | Fixed amount per unit vs per order | Granular value shapes | COVERED | [commerce domains §3.2](../14-domains/commerce/promotions-shipping-returns.md) target line/order + value JSON |

## 5. Payments

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-070 | Online card payments | Gateway capture | COVERED | [commercial core domains §8](../14-domains/finance/payments.md) gateway driver |
| SH-071 | Digital wallets (Apple/Google Pay) | Express wallets | COVERED | gateway driver capability flag ([commercial core domains §8.2](../14-domains/finance/payments.md)); [Shopify parity extensions §13](../14-domains/commerce/store-checkout.md) lists wallet-capable drivers |
| SH-072 | Manual payment methods | COD, bank deposit, money order | COVERED | [commercial core domains §8.2](../14-domains/finance/payments.md) method `cash|bank_transfer` + manual capture flow |
| SH-073 | Store credit | Issue/consume store credit | NEW-v1 | [Shopify parity extensions §5.5](../14-domains/crm/customers.md) (store credit ledger under customers) |
| SH-074 | Gift cards | Issue/redeem | COVERED | [commerce domains §6](../14-domains/commerce/giftcards-subscriptions-pos.md) gift cards |
| SH-075 | Buy now pay later | Installment providers | DEFERRED (provider adapters) | seam: `PaymentGatewayDriver` interface ([commercial core domains §8](../14-domains/finance/payments.md)); provider adapters post-v1 |
| SH-076 | Partial payments / deposits | Pay a deposit first | NEW-v1 | [Shopify parity extensions §3.5](../14-domains/sales/sales.md) (deposit on draft order + payment schedule) |
| SH-077 | Refund to original method vs store credit | Refund routing choice | COVERED | returns resolution ([commerce domains §5](../14-domains/commerce/promotions-shipping-returns.md)) + payments refund |
| SH-078 | Payment terms (net-30 invoicing at checkout) | B2B invoice-me-later | NEW-v1 | [Shopify parity extensions §3.5](../14-domains/sales/sales.md) (payment terms at checkout → AR invoice via invoicing) |
| SH-079 | PCI scope isolation | No raw PAN storage | COVERED | [security baseline §2](../04-security/security-baseline.md); payments never store raw card data ([commerce domains §7.4](../14-domains/commerce/giftcards-subscriptions-pos.md)) |
| SH-080 | Payout reconciliation feed | Settlement matching | COVERED | [finance domains §5](../14-domains/finance/ar-ap-banking.md) banking reconciliation |

## 6. Customers & Accounts

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-090 | Customer profiles | Name, contact, addresses, tags | COVERED | [commercial core domains §2](../14-domains/crm/customers.md) customers + contacts + addresses |
| SH-091 | Customer segments/groups | Rule-based segmentation | COVERED | pricing customer groups ([commercial core domains §4.2](../14-domains/sales/pricing.md)) + discounts conditions |
| SH-092 | Customer accounts (login) | Buyer self-service | NEW-v1 | [Shopify parity extensions §5](../14-domains/crm/customers.md) (buyer accounts on kernel identity, [gap analysis](./gap-analysis.md)) |
| SH-093 | Order history & reorder | Self-service | NEW-v1 | [Shopify parity extensions §5.2](../14-domains/crm/customers.md) (order history + reorder → new cart) |
| SH-094 | Saved addresses | Address book | COVERED | [commercial core domains §2.2](../14-domains/crm/customers.md) addresses (multiple per kind) |
| SH-095 | Wishlists | Save for later | NEW-v1 | [commerce domains §1.2](../14-domains/commerce/store-checkout.md) wishlists existed as optional; [Shopify parity extensions §5.3](../14-domains/crm/customers.md) makes them normative |
| SH-096 | Customer notes & tags | CRM-lite annotations | NEW-v1 | [Shopify parity extensions §3.2](../14-domains/sales/sales.md)-style notes/tags on customers (audited) |
| SH-097 | Store credit balance | Per-customer balance | NEW-v1 | [Shopify parity extensions §5.5](../14-domains/crm/customers.md) |
| SH-098 | Customer merge/dedupe | Combine records | NEW-v1 | [Shopify parity extensions §5.6](../14-domains/crm/customers.md) (merge flow with audit + event) |
| SH-099 | B2B company profiles | Companies + locations + buyers | NEW-v1 | [Shopify parity extensions §15](../14-domains/crm/customers.md) (B2B-lite: company ↔ customer mapping, payment terms) |
| SH-100 | Loyalty points | Earn/redeem | DEFERRED (P5 app) | seam: metafields + custom objects ([ecosystem §5](../18-extensions/ecosystem.md)) |

## 7. Storefront & Themes

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-110 | Theme template rendering | Liquid templates | DEFERRED (v2 rendering engine) | seam: storefront read API is contract-complete ([commerce domains §1.5](../14-domains/commerce/store-checkout.md)); v1 Blade admin renders merchandising, headless storefront later |
| SH-111 | Theme editor (drag-drop) | Visual sections | DEFERRED (v2) | seam above |
| SH-112 | Pages (static content) | About/contact etc. | NEW-v1 | [Shopify parity extensions §6.2](../14-domains/commerce/content.md) (pages as content objects) |
| SH-113 | Blogs & articles | Content publishing | NEW-v1 | [Shopify parity extensions §6.4](../14-domains/commerce/content.md) (blogs module surface) |
| SH-114 | Navigation menus | Link lists | NEW-v1 | [Shopify parity extensions §6.5](../14-domains/commerce/content.md) (menus) |
| SH-115 | SEO metadata | Per-product/page titles, descriptions, slugs | COVERED | [commerce domains §1.2](../14-domains/commerce/store-checkout.md) `storefront_products.seo` JSON |
| SH-116 | Sitemaps | Auto-generated | NEW-v1 | [Shopify parity extensions §12.3](../14-domains/commerce/store-checkout.md) (sitemap generator) |
| SH-117 | URL redirects/handles | Handle changes redirect | NEW-v1 | [Shopify parity extensions §12.4](../14-domains/commerce/store-checkout.md) (redirect table + 301) |
| SH-118 | Storefront search | Product search | COVERED | kernel Search ([kernel services §8](../07-kernel/kernel-services.md)) behind public browse endpoint ([commerce domains §1.5](../14-domains/commerce/store-checkout.md)) |
| SH-119 | Product filtering/facets | Faceted nav | COVERED | public browse filters via Search ([commerce domains §1.5](../14-domains/commerce/store-checkout.md)) |
| SH-120 | Reviews/ratings | Social proof | DEFERRED (P5 app) | seam: metafields + custom objects |
| SH-121 | Store locator | Physical stores map | NEW-v1 | [Shopify parity extensions §10.2](../14-domains/commerce/promotions-shipping-returns.md) (locations surfaced publicly) |

## 8. Marketing & Communication

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-130 | Email notifications (transactional) | Order/ship/abandon emails | COVERED | kernel Notifications ([kernel services §6](../07-kernel/kernel-services.md)) |
| SH-131 | Email templates | Editable branding | COVERED | [kernel services §6](../07-kernel/kernel-services.md) templates |
| SH-132 | Abandoned cart emails | Recovery automation | NEW-v1 | [Shopify parity extensions §11](../14-domains/commerce/store-checkout.md) |
| SH-133 | Marketing campaigns (email/SMS) | Bulk campaigns | DEFERRED (P5/P6) | seam: notifications service + customer segments |
| SH-134 | Discount-based marketing | See discounts | COVERED | [commerce domains §3](../14-domains/commerce/promotions-shipping-returns.md) |
| SH-135 | Reports: sales/customers/product | Built-in analytics | COVERED | [intelligence §1](../16-analytics/analytics.md) analytics KPIs |
| SH-136 | Conversion pixels/ads | Tracking scripts | DEFERRED | seam: storefront headless layer |
| SH-137 | Push/SMS channels | Multi-channel notify | DEFERRED (P5) | seam: notification channel drivers ([kernel services §6.2](../07-kernel/kernel-services.md)) |

## 9. POS (Point of Sale)

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-140 | POS sell screen | In-store cart/checkout | COVERED | [commerce domains §8](../14-domains/commerce/giftcards-subscriptions-pos.md) POS |
| SH-141 | Offline mode | Queue + sync | COVERED | [commerce domains §8.3](../14-domains/commerce/giftcards-subscriptions-pos.md) flow 4 offline sale |
| SH-142 | Cash management (open/close, drops) | Shift cash | COVERED | [commerce domains §8.3](../14-domains/commerce/giftcards-subscriptions-pos.md) flows 2–3 |
| SH-143 | Receipts | Print/email | COVERED | [commerce domains §8.3](../14-domains/commerce/giftcards-subscriptions-pos.md) flow 3 receipts |
| SH-144 | POS staff PIN/roles | Per-staff auth | COVERED | device flow per terminal ([identity & token platform §3.4](../04-security/identity-and-token-platform.md)) + POS permissions ([commerce domains §8.5](../14-domains/commerce/giftcards-subscriptions-pos.md)) |
| SH-145 | POS returns/exchanges | In-store returns | COVERED | [commerce domains §8.3](../14-domains/commerce/giftcards-subscriptions-pos.md) flow 5 + returns module |
| SH-146 | Customer lookup at POS | Attach customer | COVERED | [commerce domains §8.2](../14-domains/commerce/giftcards-subscriptions-pos.md) pos_sales customer_id + CustomerLookup contract |
| SH-147 | Barcode scanning at POS | SKU lookup | NEW-v1 | [Shopify parity extensions §7.3](../14-domains/commerce/giftcards-subscriptions-pos.md) barcode resolve |
| SH-148 | BOPIS pickup at POS | Fulfill online orders in store | NEW-v1 | [Shopify parity extensions §10](../14-domains/commerce/promotions-shipping-returns.md) (pickup fulfillment at POS) |
| SH-149 | POS hardware (printers, scanners) | Peripheral config | NEW-v1 | [Shopify parity extensions §7.4](../14-domains/commerce/giftcards-subscriptions-pos.md) (terminal peripheral settings; printing stays local per [API platform §10.3](../15-integrations/integration-framework.md)) |

## 10. Markets, Multi-Currency & Duties

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-150 | Markets (region segments) | Per-market catalog/pricing/duty | NEW-v1 | [Shopify parity extensions §2](../14-domains/commerce/markets.md) (markets module) |
| SH-151 | Multi-currency pricing | Market currency + rounding rules | NEW-v1 | [Shopify parity extensions §2.3](../14-domains/commerce/markets.md) (market currency via FX from [finance domains §1.5](../14-domains/finance/gl.md) + rounding rules) |
| SH-152 | Duties & import taxes | Compute landed cost | NEW-v1 | [Shopify parity extensions §2.4](../14-domains/commerce/markets.md) (duty computation at checkout, posted to tax engine) |
| SH-153 | Localized domains/paths | Per-market URLs | DEFERRED | seam: store slug routing ([commerce domains §1.5](../14-domains/commerce/store-checkout.md)) |
| SH-154 | Translation of storefront content | i18n | NEW-v1 | [Shopify parity extensions §6.6](../14-domains/commerce/content.md) translation storage (schema v1); translation tooling is a deferred seam (post-v1) |
| SH-155 | Tax-inclusive pricing toggle | Market tax display | NEW-v1 | [Shopify parity extensions §2.5](../14-domains/commerce/markets.md) (display mode per market; calc via tax engine [finance domains §2](../14-domains/finance/tax.md)) |

## 11. Taxes & Compliance

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-160 | Tax regions & rates | Jurisdiction tax | COVERED | [finance domains §2](../14-domains/finance/tax.md) tax engine |
| SH-161 | Product tax codes | HSN/taxability | COVERED | [commercial core domains §1.2](../14-domains/commerce/catalog.md) `hsn_sac` + tax codes ([finance domains §2.2](../14-domains/finance/tax.md)) |
| SH-162 | Tax-exempt customers | Exemption certs | NEW-v1 | [Shopify parity extensions §15.3](../14-domains/crm/customers.md) (tax exemption flag + cert file; engine skip) |
| SH-163 | GST e-invoicing (India) | IRN generation | NEW-v1 | [ERP parity domains §9](../15-integrations/connectors/india-statutory.md) (India statutory package) |
| SH-164 | Tax returns/filing reports | GSTR exports | NEW-v1 | [ERP parity domains §9](../15-integrations/connectors/india-statutory.md) |
| SH-165 | Invoice generation | Legal invoices | COVERED | [commercial core domains §9](../14-domains/finance/invoicing.md) invoicing |

## 12. Platform APIs & Extensibility

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-170 | Admin API (REST) | Full CRUD surface | COVERED | /api/v1 ([API platform](../07-kernel/api-platform.md)) — every resource API-first |
| SH-171 | GraphQL-style rich queries | Flexible queries | DEFERRED (GraphQL layer) | seam: OpenAPI contracts ([API platform §9](../07-kernel/api-platform.md)); GraphQL later |
| SH-172 | Webhooks | Event push | COVERED | [API platform §7](../15-integrations/integration-framework.md) webhooks + [ecosystem §3](../18-extensions/ecosystem.md) GA |
| SH-173 | Public apps (third-party) | OAuth apps | COVERED | [ecosystem §2](../18-extensions/ecosystem.md) partner OAuth |
| SH-174 | Custom/checkout UI extensions | App embeds | DEFERRED | seam: hooks ([ecosystem §1.2](../18-extensions/ecosystem.md)) |
| SH-175 | Storefront API (headless) | Public buyer queries | COVERED | [commerce domains §1.5](../14-domains/commerce/store-checkout.md) public buyer endpoints |
| SH-176 | Bulk operations API | Large async jobs | NEW-v1 | [Shopify parity extensions §14](../07-kernel/data-tools.md) (bulk import/export job surface) |
| SH-177 | Rate-limited, scoped API | Throttling | COVERED | [API platform §6](../07-kernel/api-platform.md) rate limits + scopes |
| SH-178 | App bridge/embedded admin | Iframe admin | DEFERRED | seam: Blade admin convergence ([API platform §8](../07-kernel/api-platform.md)) |

## 13. Admin & Operations

| ID | Feature | Shopify behavior | Disposition | Navdhya implementation |
|----|---------|------------------|-------------|------------------------|
| SH-180 | Role-based staff permissions | Granular admin ACL | COVERED | [authorization & policy](../04-security/authorization-and-policy.md) RBAC + built-in roles ([authorization & policy §3.2](../04-security/authorization-and-policy.md)) |
| SH-181 | Activity/audit log | Change history | COVERED | kernel Audit ([kernel services §5](../07-kernel/kernel-services.md)) |
| SH-182 | Settings hierarchy | Store-wide settings | COVERED | kernel Settings ([kernel services §9](../07-kernel/kernel-services.md)) |
| SH-183 | Feature flags/rollout | Gradual enable | COVERED | kernel Feature Flags ([kernel services §10](../07-kernel/kernel-services.md)) |
| SH-184 | Data export (customers/orders) | GDPR export | COVERED | [security baseline §5.3](../04-security/security-baseline.md) export/erasure hooks |
| SH-185 | Bulk actions on lists | Mass edit | NEW-v1 | [API platform §2.2](../07-kernel/api-platform.md) `:bulk` mutations + [Shopify parity extensions §14](../07-kernel/data-tools.md) |
| SH-186 | Saved views/filters | Per-user list config | DEFERRED | seam: user-scoped settings ([kernel services §9](../07-kernel/kernel-services.md)) |
| SH-187 | Keyboard shortcuts/search | Admin UX | DEFERRED | Blade admin UX enhancement |

---

## 14. Completeness Checklist (Shopify)

Areas swept: products & catalog, inventory & fulfillment, orders & checkout,
discounts & promotions, payments, customers & accounts, storefront & themes,
marketing & communication, POS, markets/multi-currency/duties, taxes &
compliance, platform APIs & extensibility, admin & operations.

Disposition totals:
- COVERED: 74 (implemented by docs 00–15)
- NEW-v1: 49 (→ [Shopify parity extensions](../14-domains/commerce/shopify-parity-extensions.md), plus SH-032/033/163/164 → [ERP parity domains](../14-domains/crm/crm.md))
- DEFERRED: 15 (seams recorded)
- OUT: 0

Residual-risk note: any Shopify feature discovered after this inventory
MUST enter via a gap-analysis amendment ([gap analysis](./gap-analysis.md)) before task-spec, per
[spec methodology §2.5.](../specs/README.md)
