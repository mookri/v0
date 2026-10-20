# Ecosystem — SDK, Partners, Marketplace, Custom Objects, SCIM GA

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** [ecosystem §1](./ecosystem.md)–2, §4–7 (webhooks GA §3 → integration framework)

Phase 5 ecosystem: the module SDK & extension points (hooks, [ecosystem §1](./ecosystem.md)), partner OAuth management ([ecosystem §2](./ecosystem.md)), the marketplace ([ecosystem §4](./ecosystem.md)), custom fields / custom objects / custom reports ([ecosystem §5](./ecosystem.md)), and SCIM GA provisioning ([ecosystem §6](./ecosystem.md)). Module codes `sdk`, `partners`, `marketplace`, `custom_objects`. The metafield/metaobject surface shipped early in Phase 2a is the same custom-fields engine ([content domain](../14-domains/commerce/content.md)).

---

## Module SDK & Extension Points (module code `sdk`)

### 1.1 Bounded context

The SDK is the **documented, versioned contract surface** external developers
build against: API conventions, event schemas, hook endpoints, client
libraries, and a reference integration. It also defines the **hook system**
the kernel exposes for extension logic (field computation, validations,
enrichments) without running third-party code in-process.

### 1.2 Extension points (hooks)

Hooks are HTTP callbacks the kernel invokes at defined points, with the same
delivery + signing mechanics as webhooks ([API platform §7](../15-integrations/integration-framework.md)) but **request/response
semantics** (the hook can influence the operation):

| Hook type | Point | Contract | Failure policy |
|-----------|-------|----------|----------------|
| `validate` | before a create/update commits | POST payload = candidate resource; response = ok or error list | error → operation rejected with `hook_validation_failed` + hook's error detail (sanitized) |
| `enrich` | before commit, after validate | POST payload; response = partial JSON merged into `meta` only | error/timeout → operation proceeds without enrichment (log + event) |
| `compute` | named computed fields on resources | POST payload; response = field value | error → field null + flag |
| `notify` | after commit (fire-and-forget) | identical to webhooks | retries per webhook policy |

Normative hook rules:

1. Registration: per tenant, per hook type + event/resource selector, at
   `/api/v1/platform/hooks` (permission `platform:hooks:configure`,
   sensitive). Max 10 hooks per selector; ordering by priority.
2. Latency budget: `validate`/`enrich`/`compute` hooks MUST
   respond within **3 s** (timeout = skip per failure policy); kernel waits
   synchronously inside the request — hooks are excluded from the
   checkout/payment hot paths by allowlist (only declared resources may
   register validate/enrich hooks; v1 allowlist: customers, suppliers,
   products, orders).
3. Security: HMAC signatures identical to webhooks (`Navdhya-Hook-Signature`);
   replay protection via timestamp window; response bodies capped at 64 KB
   and schema-validated; SSRF guards identical to webhooks ([API platform §7.1](../15-integrations/integration-framework.md)).
4. Observability: every hook invocation logged (latency, status, bytes) in
   `hook_invocations`; tenant-visible in admin; circuit breaker mirrors
   webhooks (>90% failures/24 h → disabled + `platform.hook.disabled`).
5. **No data exfiltration guarantee**: `validate`/`enrich`/
   `compute` payloads contain only the resource fields the hook's scope
   permits — field-level scope filtering (doc 4.4 v2 path implemented here
   for hooks: a hook client's scope set filters payload fields).

### 1.3 SDK deliverables

| Artifact | Content |
|----------|---------|
| OpenAPI spec | composed `docs/api/openapi.v1.yaml` served at `/api/v1/openapi.json` (scoped subset for partners, [API platform §9.1](../07-kernel/api-platform.md)) |
| Event schema registry | JSON Schemas of the entire event catalog ([kernel services §2.2](../12-events/events-and-outbox.md)), published per tenant scope |
| Client libraries | TypeScript + PHP reference clients: auth helpers (PKCE, client credentials, refresh handling), typed resources, webhook/hook signature verification, idempotency helpers, retry policies |
| CLI | `navdhya dev`: sandbox tenant provisioning, event tail (long-poll), hook local tunnel |
| Reference integration | sample partner: subscribes to `sales.order.confirmed`, enriches customer `meta` via hook, calls back with client credentials — the canonical example |
| Sandbox | dedicated sandbox environment per partner tenant (separate tenant in the same cluster, flag `tenant.kind=sandbox`); data resets weekly |

### 1.4 Versioning & compatibility (normative)

- SDK follows platform API major versions ([API platform §9.2](../07-kernel/api-platform.md)); the SDK's semver
  major tracks the API major.
- Deprecation windows (6 months partner-facing, [API platform §9.4](../07-kernel/api-platform.md)) apply to
  hooks and event schemas equally; the `api:diff` artifact is published
  in the changelog.
- Breaking changes to event payload schemas bump `event_version`
  ([kernel services §2.2](../12-events/events-and-outbox.md)); the platform delivers **both** versions during the
  deprecation window when a subscriber pins the old version (delivery
  envelope carries the pinned version).

### 1.5 Event catalog

`platform.hook.registered` / `.updated` / `.disabled`,
`platform.hook.invocation_failed` (aggregate),
`platform.sdk.sandbox_created`.

### 1.6 API surface (/api/v1/platform/hooks, /api/v1/sdk)

| Endpoint | Permission |
|----------|-----------|
| CRUD hooks | `platform:hooks:configure` (sensitive) |
| GET hook invocations, POST /hooks/{id}:test | `platform:hooks:view` |
| GET /sdk/events (schema registry), GET /sdk/changelog | `kernel:profile:read` (any authenticated) |
| POST /sdk/sandbox:provision | `partners:sandbox:provision` |

Scopes: kernel `kernel:hooks:manage` (admin), partner clients cannot
self-register hooks — tenant admins register them on the partner's behalf
(governance by design).

---
---

## Partner OAuth Management (module code `partners`)

### 2.1 Bounded context

The lifecycle of third-party integrators: partner registration, their OAuth
clients ([identity & token platform §2.3](../04-security/identity-and-token-platform.md) profiles), scope grants per tenant, consent, and usage
visibility. This is the administrative product around the token platform.

### 2.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `partner_profiles` | id, name, legal_name, contact_email, website, status (`pending_review|approved|suspended|rejected`), reviewed_by | platform-global record of the integrator |
| `partner_apps` | id, partner_profile_id, name, description, privacy_policy_url, logo_file_id, status | an app = one integration product; maps to ≥1 oauth client profile |
| `partner_app_grants` | id, app_id, tenant_id, granted_scopes JSON, status (`active|suspended|revoked`), granted_by, expires_at NULL | per-tenant authorization for the app |
| `partner_consent_records` | id, app_id, tenant_id, membership_id, scopes JSON, consented_at | consent audit trail ([identity & token platform §3.1](../04-security/identity-and-token-platform.md) consent screen) |
| `partner_usage_snapshots` | id, app_id, tenant_id, day, api_calls, events_delivered, errors | from usage meters ([kernel services §11.3](../07-kernel/kernel-services.md)) |

### 2.3 Primary flows

1. **Onboarding**: integrator applies (platform-operator surface, [security baseline §4](../04-security/security-baseline.md))
   → `pending_review` → operator approves → partner creates apps →
   app client profiles issued (client credentials + optional PKCE web app)
   → `partners.app.created`.
2. **Tenant installation**: tenant admin installs an app → consent screen
   lists requested scopes in human-readable form (from permission catalog
   metadata) → grant record created with the intersection of requested ∩
   app-allowed ∩ tenant-policy-allowed scopes →
   `partners.grant.installed` → tenant may suspend/revoke at any time
   (revocation kills the app's tokens for that tenant immediately via [identity & token platform](../04-security/identity-and-token-platform.md)
   §8 bulk revocation).
3. **Scope governance**: tenant setting `partners.max_sensitive_scopes`
   (default 0 for marketplace apps) caps sensitive-scope grants; raising it
   requires ACR 2 + is audited.
4. **Usage & fairness**: daily snapshots feed the partner usage dashboard;
   per-client rate tiers ([API platform §6.2](../07-kernel/api-platform.md)) enforced continuously; sustained
   abuse → operator suspension (`partners.app.suspended`).

### 2.4 Event catalog

`partners.profile.approved` / `.suspended`,
`partners.app.created` / `.submitted` / `.suspended`,
`partners.grant.installed` / `.scopes_changed` / `.revoked`,
`partners.usage.quota_warning_raised`.

### 2.5 API surface (/api/v1/partners)

| Endpoint | Permission |
|----------|-----------|
| GET/POST /apps (partner self-service with partner token) | `partners:app:manage` (partner scope) |
| POST /apps/{id}/clients:rotate_secret | `platform:client:rotate_secret` (sensitive, [authorization & policy §2.1](../04-security/authorization-and-policy.md)) |
| Tenant admin: GET /marketplace/apps, POST /grants (install), PATCH /grants/{id} (scopes), DELETE /grants/{id} (revoke) | `partners:grant:manage` (sensitive for sensitive-scope changes) |
| GET /apps/{id}/usage | `partners:usage:view` |

Scopes: `partners:read`, `partners:write`, fine sensitive scopes.

Contracts: consumes the kernel client registry ([identity & token platform §2.3](../04-security/identity-and-token-platform.md)) and usage
meters ([kernel services §11.3](../07-kernel/kernel-services.md)). Publishes to: notifications (quota warnings),
analytics.

### 2.6 Integration points

Consumes: kernel token platform, usage meters. Consumed by: marketplace
(install flow), sdk (sandbox provisioning creates partner-scoped tenants).

---
---

## Marketplace (module code `marketplace`)

### 4.1 Bounded context

The discovery + distribution layer for partner apps: listings, categories,
reviews, installation (delegates to partners' grant flow, §2.3), and
(optional, v1-stub) billing metadata. The marketplace is a **catalog of
partner apps**, not a code store — installation means scope grants +
webhook/hook registration, per the Phase 5 safety envelope.

### 4.2 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `marketplace_listings` | id, app_id (partner app ref), slug (UQ), title, tagline, description, category_id, pricing_model (`free|subscription|usage`), price_hint JSON NULL, support_url, documentation_url, status (`draft|in_review|published|delisted`), published_at | partner-authored |
| `marketplace_categories` | id, code, name | platform-curated |
| `listing_assets` | id, listing_id, kind (`screenshot|logo|video`), file_id, position | kernel Files |
| `listing_reviews` | id, listing_id, tenant_id, membership_id, rating (1–5), body NULL, status (`visible|flagged|removed`) | one per tenant; edit replaces |
| `listing_review_flags` | id, review_id, reason, flagged_by, status | moderation queue |
| `installations` | id, listing_id, tenant_id, grant_id (partner_app_grants ref), installed_at, status | mirror of grant lifecycle |

### 4.3 Primary flows

1. **Publishing pipeline**: partner submits listing → `in_review` →
   platform operator reviews (security questionnaire: scopes requested,
   data handling, hook endpoints) → `published` →
   `marketplace.listing.published`. Delisting keeps existing grants
   running but blocks new installs (tenant choice to revoke).
2. **Discovery & install**: tenant browses (public-ish catalog behind
   tenant auth) → install → consent screen (§2.3 flow 2) →
   `installations` row linked to the grant →
   `marketplace.app.installed`.
3. **Reviews**: tenant members with an active installation can review
   (one per tenant); moderation flags → operator queue →
   `marketplace.review.flagged`.
4. **Billing stub**: `pricing_model` + `price_hint` are
   display-only in v1; actual charge-through billing is a v2 item
   (documented as deferred, not silently absent).

### 4.4 Event catalog

`marketplace.listing.submitted` / `.published` / `.delisted`,
`marketplace.app.installed` / `.uninstalled`,
`marketplace.review.created` / `.flagged` / `.removed`.

### 4.5 API surface (/api/v1/marketplace)

| Endpoint | Permission |
|----------|-----------|
| GET /listings (+filters/categories), GET /listings/{slug} | `marketplace:listing:view` |
| Partner: POST /listings, PATCH, POST /listings/{id}:submit | `marketplace:listing:manage` (partner scope) |
| Operator: POST /listings/{id}:publish, :delist (operator surface, [security baseline §4](../04-security/security-baseline.md)) | platform-operator permission |
| POST /listings/{id}/install, DELETE /installations/{id} | `partners:grant:manage` (reuses §2 grant permission — install = grant) |
| POST /listings/{id}/reviews, PATCH own review | `marketplace:review:create` |
| Moderation queue (operator surface) | platform-operator permission |

Scopes: `marketplace:read`, `marketplace:write`.

### 4.6 Integration points

Consumes: partners (apps, grants), kernel Files (assets). Publishes to:
notifications (install confirmations), analytics (install funnel).

---
---

## Custom Fields, Custom Objects & Custom Reports (module code `custom_objects`)

### 5.1 Bounded context

Tenant extensibility without code: additional typed fields on standard
resources, fully tenant-defined objects with their own CRUD + API surface,
and report definitions over those data sets. This is the **metadata
engine** — schema-on-read over a generic storage layer, never per-tenant
DDL.

### 5.2 Storage model (normative)

- **No per-tenant schema migrations.** Custom data lives in generic tables:
  `custom_records` (object instances) and `custom_field_values`
  (typed value rows), both tenant-scoped on the tenant data connection.
- `custom_field_values` stores the value in a **typed column** per
  primitive (`value_text`, `value_number DECIMAL(18,4)`,
  `value_date`, `value_datetime`, `value_bool`,
  `value_option_id`, `value_file_id`, `value_record_ref`) —
  one column set per row, never serialized blobs for queryable data
  ([tenancy & data §9](../13-data/data-architecture.md) JSON rule).
- Indexing: up to **3 indexed fields per object definition** (tenant
  choice), materialized into `custom_index_slots` (slot_1..slot_3
  generic columns with per-definition typed interpretation) — keeps list
  filtering fast without DDL.

### 5.3 Core entities

| Table | Key fields | Notes |
|-------|-----------|-------|
| `custom_object_definitions` | id, code (UQ per tenant, `[a-z_]+`), name, description, label_plural, icon, status, title_field_id, ownership (`tenant|org`) | defines an object |
| `custom_field_definitions` | id, object_code OR target (`<module>:<resource>` for custom fields on standard resources), code, name, type (`text|textarea|number|decimal|date|datetime|boolean|option_set|file|record_ref|email|phone|url|money`), required, default_value, option_set_id NULL, validation JSON (regex/min/max), indexed_slot NULL, status | metadata registry |
| `custom_option_sets` / `custom_options` | id, code; options: value, label, position, color | picklists |
| `custom_records` | id, object_code, org_id NULL, title (denormalized from title field), status (`active|archived`), created_by, meta JSON | instance rows |
| `custom_field_values` | id, target_type (`custom_record|<module_resource>`), target_id, field_id, typed value columns | UQ (tenant, target, field) |
| `custom_report_definitions` | id, code, name, source (`custom_object|standard_module`), columns JSON, filters JSON, grouping JSON, chart_hint NULL, sharing (`private|org|tenant`) | report builder output |

### 5.4 Custom fields on standard resources

1. A tenant defines a custom field targeting e.g. `customers:customer`
   or `sales:order`; allowed targets are an **allowlist** maintained by
   each module (declared in `module.json` section
   `custom_fields_targets`) — modules opt in; v1 allowlist:
   customers, suppliers, products, sales orders, tickets, projects.
2. Values are stored in `custom_field_values` keyed by the standard
   resource id — standard module tables are never altered.
3. API exposure: standard resource JSON resources gain a `custom_fields`
   object in responses (additive, [API platform §9.2](../07-kernel/api-platform.md) — safe); writes accept
   `custom_fields` on create/update, validated against field
   definitions.
4. Blade admin: field rendering is metadata-driven (Velzon form partials per
   type) on the resource detail/edit screens of allowlisted modules.
5. Permission model: defining fields requires
   `custom_objects:field:configure` (sensitive); reading/writing values
   follows the **host resource's** permission (no new permission bypass).

### 5.5 Custom objects

1. Object definition + fields → kernel auto-registers:
   - API surface `/api/v1/custom/{object_code}` with standard CRUD
     semantics ([API platform](../07-kernel/api-platform.md) conventions: cursor pagination, filtering on indexed
     fields + `q` search, error envelope);
   - permissions `custom:<object_code>:view|create|update|delete`
     (registered into the permission catalog at sync time — [authorization & policy §2.1](../04-security/authorization-and-policy.md));
   - events `custom.<object_code>.created|.updated|.deleted` with
     generic payload envelope (record id + field map) — subscribers get the
     field map as JSON (schema published per tenant);
   - navigation entry (manifest-style, rendered by the kernel).
2. Record reference fields (`record_ref`) may point at other custom
   objects or allowlisted standard resources — integrity enforced by the
   engine (soft: dangling refs surface as validation errors on read).
3. Limits (plan-gated): objects per tenant (default 20), fields per object
   (default 40), records per object (default 100k), indexed fields (3).

### 5.6 Custom reports

1. Report definition selects a source (custom object or an allowlisted
   standard resource with its filterable fields), columns (standard +
   custom fields), grouping (≤ 2 levels), and aggregation for numeric
   columns (sum/avg/count/min/max).
2. Execution: the report engine compiles the definition into a bounded
   query (limit 10k rows inline; larger → queued artifact via Files like
   finance reports, [finance domains §8.3](../14-domains/finance/assets-budgets-reports.md)) honoring org scoping + host-resource
   permissions.
3. Sharing: private/org/tenant visibility; scheduled delivery (email CSV)
   via notifications for tenant-shared reports.
4. `custom_objects:report:configure` to define; view follows the
   sharing scope + underlying data permissions (a report never elevates
   data access).

### 5.7 Event catalog

`custom_objects.object.created` / `.updated` / `.archived`,
`custom_objects.field.created` / `.updated` / `.archived`,
`custom.<object_code>.created` / `.updated` / `.deleted`
(per-record dynamic events), `custom_objects.report.created` /
`.executed`.

### 5.8 API surface (/api/v1/custom-objects + /api/v1/custom)

| Endpoint | Permission |
|----------|-----------|
| CRUD object/field definitions, option sets | `custom_objects:object:configure`, `custom_objects:field:configure` (both sensitive) |
| CRUD reports | `custom_objects:report:configure` / view per sharing |
| `/api/v1/custom/{object_code}` CRUD | `custom:<object_code>:view|create|update|delete` (auto-registered) |

Scopes: `custom_objects:read`, `custom_objects:write`, plus
per-object scopes `custom:<object_code>:read|write` auto-expanded.

### 5.9 Integration points

Consumes: kernel permission registry, event catalog, Files, Search (custom
records are indexed via the kernel search driver), notifications (scheduled
reports). Publishes to: webhooks (dynamic events are subscribable), audit
(all definition changes).

---
---

## SCIM GA (kernel feature promotion)

SCIM 2.0 endpoints and semantics are fully specified in [identity & token platform §10](../04-security/identity-and-token-platform.md) and ship
in Phase 0 behind the `module.platform_scim` feature flag. Phase 5 GA
adds:

### 6.1 GA additions (delta over [identity & token platform §10](../04-security/identity-and-token-platform.md))

1. **Admin configuration surface**: per-tenant SCIM setup wizard at
   `/api/v1/platform/scim` — enable, issue the provisioning client
   (client-credentials client with `kernel:scim:write`, tenant-bound),
   configure `scim_group_map`, test-connection endpoint
   (`POST /scim/v2/{tenantSlug}/ServiceProviderConfig:test` simulated
   round-trip) — permission `platform:scim:configure` (sensitive).
2. **Group mapping GA**: full group→role mapping with preview ("this sync
   would assign role X to N users") and conflict detection against SoD
   rules (a group mapping that would create a blocked conflict is rejected
   at save time, not at sync time).
3. **Sync observability**: per-tenant sync dashboard — last full reconcile,
   drift count, per-operation outcomes, error queue with retry; daily full
   reconciliation job ([identity & token platform §10.6](../04-security/identity-and-token-platform.md)) enabled by default.
4. **Provider quick-start guides** (docs, not code): Entra ID, Okta, Google
   Workspace (via their SCIM bridges), OneLogin.
5. **Rate limiting**: SCIM endpoints get a dedicated tier (burst-tolerant
   for initial bulk imports: 600 req/min sustained per tenant client,
   import-mode flag raises to 3,000/min for 24 h).

### 6.2 Acceptance for GA

- Full RFC 7644 conformance suite passes (Users/Groups CRUD, filter,
  pagination, error schema, `Me`, `ServiceProviderConfig`,
  `Schemas`).
- Deactivation path proven: SCIM `active=false` suspends membership,
  revokes all tokens/sessions within one request cycle (live re-check, doc
  04 §5.3), and emits the provisioning event.
- Drift reconciliation catches manually-created users missing upstream and
  reports them without auto-deleting (human decision).

### 6.3 Event catalog (GA additions)

`platform.scim.enabled` / `.disabled`,
`platform.scim.import_mode_activated` (bulk import mode entered),
`platform.scim.reconcile_completed` (summary).

---
---

> **Webhooks GA** ([ecosystem §3](./ecosystem.md)) is specified in the [integration framework](../15-integrations/integration-framework.md) — webhooks are a kernel product surface, not an ecosystem module. SCIM GA below is referenced from the [identity & token platform](../04-security/identity-and-token-platform.md), which keeps the pointer.
---

## Governance surfaces (platform-operator)

| Action | Operator permission | Notes |
|--------|--------------------|-------|
| Approve/suspend partner profiles & apps | `operator:partners:review` | security questionnaire output attached |
| Publish/delist marketplace listings | `operator:marketplace:curate` | |
| Moderate reviews | `operator:marketplace:moderate` | |
| Raise tenant sensitive-scope caps | `operator:partners:scope_exception` | ACR 2, dual approval |
| SCIM import-mode activation | tenant self-service (audited) | operator notified |

Platform-operator surface rules (break-glass, dual approval, audit) live in the [security baseline](../04-security/security-baseline.md).
---

## Security constraints (feed into security baseline)

1. Hook/webhook endpoints: SSRF guards, HMAC verification, replay windows —
   shared implementation, single test suite.
2. Partner tokens: never granted sensitive scopes by default; tenant
   sensitive-scope cap = 0 default.
3. Custom object values inherit host-resource authorization — the metadata
   engine cannot become a permission bypass (architecture test: custom-field
   reads on a resource denied without the host `view` permission).
4. Marketplace listing content is sanitized HTML (allowlist tags) — no
   script injection vectors in descriptions.
5. SCIM provisioning client secrets rotate on demand; revocation kills all
   active SCIM sessions/tokens for the tenant immediately.

---

These constraints are enforced by the standing suites in the [testing strategy](../19-testing/testing-strategy.md) and recorded in the [security baseline](../04-security/security-baseline.md).
---

## Phase summary inputs

Capacity, workflow, and contract inputs extracted from the phase-level summary tables. Workflow machine semantics live in the [workflow engine](../11-workflow/workflow-engine.md); event envelope and volume handling in [events & outbox](../12-events/events-and-outbox.md).

### Event volume expectations

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| platform.hook.* | 10¹ (config) + invocations logged separately |
| partners.* / marketplace.* | 10¹ |
| custom.<object>.* | 10²–10⁴ (depends on tenant usage) |
| platform.scim.* | 10² (enterprise tenants with IdP sync; bulk imports burst) |

### Cross-module contracts (this domain as consumer)

| Consumer | Contract (owner) |
|----------|------------------|
| sdk hooks | kernel event envelope ([kernel services §2.1](../12-events/events-and-outbox.md)), webhook signing ([API platform §7.2](../15-integrations/integration-framework.md)) |
| partners | kernel client registry ([identity & token platform §2.3](../04-security/identity-and-token-platform.md)), usage meters ([kernel services §11.3](../07-kernel/kernel-services.md)) |
| marketplace | partners (apps/grants), kernel Files |
| custom_objects | kernel permission registry ([authorization & policy §2](../04-security/authorization-and-policy.md)), event catalog ([kernel services §2.2](../12-events/events-and-outbox.md)), Search ([kernel services §8](../07-kernel/kernel-services.md)), Files ([kernel services §7](../07-kernel/kernel-services.md)) |
| SCIM GA | kernel identity ([identity & token platform §1](../04-security/identity-and-token-platform.md)), authorization ([authorization & policy §3](../04-security/authorization-and-policy.md)) |

---

## Permission summary (manifest-ready)

| Module | Permissions |
|--------|-------------|
| sdk | `platform:hooks:configure` (sensitive), `platform:hooks:view`, `partners:sandbox:provision` |
| partners | `partners:app:manage` (partner scope), `partners:grant:manage` (sensitive for sensitive-scope changes), `partners:usage:view` |
| marketplace | `marketplace:listing:view`, `marketplace:listing:manage` (partner scope), `marketplace:review:create` |
| custom_objects | `custom_objects:object:configure` (sensitive), `custom_objects:field:configure` (sensitive), `custom_objects:report:configure`, `custom:<object_code>:view|create|update|delete` (auto-registered) |
| SCIM GA | `platform:scim:configure` (sensitive), `kernel:scim:write` (client scope — unchanged from [identity & token platform §10](../04-security/identity-and-token-platform.md)) |

Capability syntax is `<module>:<resource>:<action>` (colon form retained per the capability model in the [authorization & policy](../04-security/authorization-and-policy.md) register and [ADR-0002](../21-adrs/ADR-0002-capability-colon-syntax.md)). Actions flagged sensitive require step-up authentication per the [experience architecture](../10-experience/experience-architecture.md); module manifests register these rows per the [domain framework](../09-domain-framework/domain-framework.md).

---

## Verification requirements

1. **Hook semantics**: validate-hook rejection fails the operation with
   `hook_validation_failed` and sanitized detail; enrich timeout
   proceeds without enrichment + logs; compute error nulls the field with a
   flag; notify hooks retry per webhook policy.
2. **Hook latency budget**: simulated 4 s hook response → kernel times out
   at 3 s and applies the failure policy (never blocks the request longer).
3. **Partner grant revocation**: revoke → all app tokens for that tenant
   rejected within one request cycle; consent record retained for audit.
4. **Sensitive-scope cap**: install flow requesting a sensitive scope with
   tenant cap 0 → consent screen refuses the scope (down-scoped) and records
   the intersection.
5. **Marketplace lifecycle**: unpublished listing not installable; delisted
   listing keeps existing grants until tenant revokes; review uniqueness per
   tenant enforced under concurrency.
6. **Custom fields**: field on `customers:customer` appears in API
   responses additively; write without host `update` permission →
   403; validation rules (regex/min/max) enforced; indexed slot filtering
   verified with EXPLAIN (uses the slot index).
7. **Custom objects**: CRUD + events + permissions auto-registered at
   definition time; record_ref integrity error surfaced on dangling read;
   plan limits enforced (21st object → 422 `plan_limit_exceeded`).
8. **Custom reports**: org scoping honored (user sees only own org rows);
   report over a resource the viewer cannot read returns 403; 10k row cap
   and queued artifact path tested.
9. **SCIM GA**: RFC 7644 conformance suite; deactivation revocation latency
   test; group-map SoD pre-check rejects conflict-creating mappings;
   reconcile drift report matches fixture.
10. **Tenant isolation**: every table in this domain is covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../06-tenancy/tenancy-and-data.md)).
11. **Contract tests**: every permission in the summary above appears on its routes and OpenAPI fragments validate — see the [testing strategy](../19-testing/testing-strategy.md).
