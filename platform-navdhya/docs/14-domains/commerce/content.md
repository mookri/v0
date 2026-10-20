# Content Domain

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [Shopify parity extensions §6](./content.md)

Storefront content: metafields and metaobjects on the platform-managed definition registry (the Phase 5 custom-fields engine exposed early), pages & blogs, navigation menus, and translations storage ([Shopify parity extensions §6](./content.md)). Module code `content`. A second content storage engine is banned ([Shopify parity extensions §1](./shopify-parity-extensions.md)); the full custom-objects engine lives in the ecosystem doc.

---

## Module: Content (module code `content`)

### 6.1 Bounded context

Storefront content beyond the product catalog: **metafields**
(structured extension data on standard resources), **pages**,
**metaobjects** (reusable structured content), **blogs/articles**,
**navigation menus**, and **translations** of storefront strings.
Metafields/metaobjects are the v1 restricted surface of the Phase 5
custom-fields engine ([ecosystem §5](../../18-extensions/ecosystem.md), planning [LD-03](../../21-adrs/ADR-0004-metafields-custom-fields-engine.md)); pages/blogs/menus are
content aggregates served to the storefront API.

### 6.2 Metafields (normative)

Storage and semantics follow [ecosystem §5.2–§5.4](../../18-extensions/ecosystem.md) exactly (column slots +
JSON overflow, definition-validated, host-resource authorization
inherited). Phase 2a restrictions:

1. Definitions are **platform-managed** (seeded + operator-extensible);
   tenant self-service definitions arrive with Phase 5.
2. v1 host resources: `catalog:product`, `catalog:variant`,
   `customers:customer`, `sales:order`, `store:store`,
   `content:page`, `content:article`.
3. Metafield values appear additively in API responses under `meta_fields`
   ([API platform §3.1](../../07-kernel/api-platform.md) additive rule) and are writable only with the host
   resource's `update` permission.
4. Definition schema: `namespace`, `key`, `type`
   (`string|integer|decimal|boolean|date|datetime|json|reference`),
   `validations JSON`, `indexed_slot NULL`.

### 6.3 Metaobjects (normative)

A metaobject is a named, definition-bound content record (e.g. "size
guide", "FAQ block", "store banner") referenceable from metafields
(`type=reference`) and from the storefront API:

- `metaobject_definitions`: `code UQ`, `name`, `field_defs JSON` (each
  field = metafield definition), `status`.
- `metaobject_records`: `id`, `definition_code`, `handle UQ per tenant`,
  `fields JSON` (validated against `field_defs`), `published_at NULL`.
- Records are tenant-scoped, audited, searchable (kernel Search), and
  served read-only on the public storefront API when published.

### 6.4 Pages & blogs

| Table | Key fields | Notes |
|-------|-----------|-------|
| `content_pages` | id, store_id, handle (UQ per store), title, body_html (sanitized), template_hint, seo JSON, published_at NULL, status | sanitize allowlist per [ecosystem §7.5](../../18-extensions/ecosystem.md) rule 4 |
| `content_blogs` | id, store_id, handle, title | container |
| `content_articles` | id, blog_id, handle, title, body_html, author, excerpt, seo JSON, published_at NULL, tags | |

Pages/articles support scheduling (`published_at` future = scheduled
publish job) and are included in sitemaps (§12.3) once published.

### 6.5 Navigation menus

`content_menus` (`store_id`, `handle` e.g. `main-menu|footer`, `title`)
+ `content_menu_items` (menu_id, parent_id, position, label, link_type
(`product|collection|page|article|blog|url`), link_ref, visibility).
Menu trees are validated acyclic; the storefront API serves them as
ordered JSON for headless rendering.

### 6.6 Translations (schema v1; tooling deferred)

`content_translations`: `locale`, `namespace` (`storefront|product|page|
article|menu`), `key`, `value`, UQ `(tenant, locale, namespace, key)`.
v1 ships storage + read API + import/export (§14); translation editing
tooling and auto-translation are post-v1 (planning DF-11). Storefront
responses resolve `locale` from the request (`Accept-Language` /
`?locale=`) falling back to the store locale.

### 6.7 Event catalog

`content.metafield_def.updated`, `content.metaobject.published` /
`.unpublished`, `content.page.published` / `.unpublished`,
`content.article.published` / `.unpublished`, `content.menu.updated`,
`content.translation.updated`.

### 6.8 API surface (`/api/v1/content`)

| Endpoint | Permission |
|----------|-----------|
| Metafield definitions CRUD | `content:metafield:configure` (sensitive) |
| Metaobject definitions/records CRUD + `:publish` | `content:metaobject:view|create|update`, publish under `update` |
| Pages/blogs/articles CRUD + `:publish` | `content:page:view|create|update`, `content:article:view|create|update` |
| Menus CRUD | `content:menu:view|create|update` |
| Translations CRUD + bulk import | `content:translation:view|create|update` |

Public (buyer) reads — added to the Phase 2a public allowlist (§16.2):
`GET /api/v1/public/stores/{slug}/pages|articles|menus|metaobjects`.
Scopes: `content:read`, `content:write`, fine `content:metafield:configure`.

Contracts published: `ContentLookup` (storefront rendering),
`MetafieldQuery` (other modules rendering extension data).

### 6.9 Integration points

Consumes: kernel Files (media), Search, store (slugs/channels).
Publishes to: storefront public API, sitemaps (§12), analytics
(content performance, post-v1).

---
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| content.* | 10¹–10² |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| content | kernel Files/Search; store slugs |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| content | `content:metafield:configure` (sensitive), `content:metaobject:view|create|update`, `content:page:view|create|update`, `content:article:view|create|update`, `content:menu:view|create|update`, `content:translation:view|create|update` |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../../04-security/authorization-and-policy.md) register and [ADR-0002](../../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Metafield authorization parity**: writing a metafield on a host
    resource without the host `update` permission ⇒ 403; values validate
    against definition types; reference metaobjects resolve only when
    published.
2. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../../19-testing/testing-strategy.md).
