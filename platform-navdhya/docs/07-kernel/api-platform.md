# API Platform

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 06 §1–6, §8, §9, §11, §12 (webhooks §7 and channel adapters §10 → integration framework)

The API platform defines the /api/v1 conventions every module surface obeys: URL/resource conventions, request/response formats, the error envelope, idempotency, rate limiting, Blade↔API convergence, contract-first OpenAPI versioning, and platform-global surfaces. Webhook delivery and channel adapters are specified in the integration framework.


---

## Design Goals (Normative)

1. **Contract-first.** Every endpoint exists in an OpenAPI 3.1 fragment
   before it ships; CI fails on drift (§9).
2. **Uniform mechanics.** Pagination, errors, idempotency, throttling and
   versioning behave identically across all modules (this doc), so clients
   learn the platform once.
3. **Safe by default.** Least-privilege scopes, audience-checked tokens,
   fail-closed tenant resolution, replay-safe mutating calls.
4. **Channel-agnostic.** Web, POS, mobile and partner channels differ only in
   grant type and adapter mapping — never in business semantics.

---
---

## URL & Resource Conventions

### 2.1 Path shape

```text
/api/v1/{module}/{resource}[/{id}][/{sub-resource}[/{id}]]
```

| Rule | Example |
|------|---------|
| Module segment = module code | `/api/v1/sales/...` |
| Resources are plural nouns | `/api/v1/sales/orders` |
| Identity via ULID path param | `/api/v1/sales/orders/01J8ORDER...` |
| Sub-resources max one level deep; deeper relations promoted to top-level with parent filter | `/api/v1/sales/orders/{id}/lines` ✔; `.../lines/{id}/taxes` ✘ → `/api/v1/sales/order-line-taxes?line_id=` |
| Actions that are not CRUD use a verb sub-path (POST) | `/api/v1/sales/orders/{id}:approve` (colon-verb convention) |
| Kernel/platform surface | `/api/v1/platform/...`, `/api/v1/identity/...` |
| No filters/verbs in query for mutations; query is for reads only | `?status=confirmed` on GET only |

### 2.2 HTTP method semantics

| Method | Semantics | Idempotent | Idempotency-Key honored |
|--------|-----------|------------|--------------------------|
| GET | read / list / search | yes | n/a |
| POST | create or verb action | no | **yes** |
| PUT | full replace | yes | n/a (replay-safe) |
| PATCH | partial update (JSON Merge Patch, RFC 7386) | no | **yes** |
| DELETE | delete / soft-delete per resource policy | yes | n/a |

- POST verb actions (`:approve`, `:cancel`, `:convert`) are the only
  state transitions exposed; raw status fields are never writable.
- Bulk mutations: `POST /api/v1/{module}/{resource}:bulk` with an items array
  (max 100 per call); response reports per-item outcomes; atomicity is
  per-item unless the endpoint declares all-or-nothing.

### 2.3 Standard headers

| Header | Direction | Meaning |
|--------|-----------|---------|
| `Authorization: Bearer <token>` | req | JWT (API); session cookie for web |
| `X-Tenant-Id` | req | REQUIRED on every tenant-scoped API call ([tenancy & data §4.3](../06-tenancy/tenancy-and-data.md)) |
| `X-Org-Id` | req | Optional org override when the token is not org-prebound; validated against actor's org set |
| `Idempotency-Key` | req | Client-generated UUID/ULID for POST/PATCH (§5) |
| `If-Match` | req | ETag for optimistic concurrency (§3.5) |
| `Navdhya-Request-Id` | resp | Server request id (echoed in logs, audit, errors) |
| `ETag` | resp | Resource version (§3.5) |
| `Sunset`, `Deprecation` | resp | Versioning signals (§9.4) |
| `X-RateLimit-Limit/Remaining/Reset` | resp | Throttle state (§6) |

---
---

## Request/Response Formats

### 3.1 JSON rules

- Request and response bodies are UTF-8 JSON only; `Content-Type:
  application/json` required on bodies.
- Field names are `snake_case`.
- Timestamps are ISO 8601 UTC with milliseconds (`2026-08-24T10:15:30.123Z`).
- Money is represented as an object: `{"amount": "15499.00", "currency":
  "INR"}` — amount is a **string** decimal (never float); minor-unit integers
  appear only inside event payloads ([kernel services §2.1](../12-events/events-and-outbox.md)).
- Enums are lowercase snake strings matching the DB values.
- ULIDs are 26-char strings.
- Unknown request fields are rejected (strict validation) — protects the
  contract and catches client typos.
- Nulls: absent field = "do not change" on PATCH; explicit `null` = clear.

### 3.2 Single-resource response

```json
{
  "data": { "id": "01J8ORDER...", "object": "sales_order", "number": "SO-2026-000123", "...": "..." },
  "meta": { "request_id": "req_01J8..." }
}
```

`object` is the stable resource type name used across the platform
(webhooks, search, audit `subject_type`).

### 3.3 List response (cursor pagination)

```json
{
  "data": [ { "id": "...", "object": "sales_order", "...": "..." } ],
  "meta": {
    "request_id": "req_01J8...",
    "page": { "limit": 50, "has_more": true, "next_cursor": "eyJpZCI6..." }
  }
}
```

- Cursor pagination only (offset pagination is banned: stable under writes,
  works on partitions).
- `limit` default 25, max 100; `cursor` is an opaque base64 token.
- Default ordering is `created_at DESC, id DESC` unless the resource declares
  a canonical order.

### 3.4 Filtering & sorting (reads)

- Filtering: `?status=confirmed&customer_id=...&created_at[gte]=...&created_at[lt]=...`
  — operators `eq` (default), `gte`, `gt`, `lte`, `lt`, `in` (comma list),
  `q` (search via kernel Search, [kernel services §8](./kernel-services.md)).
- Each resource's filterable fields are declared in its OpenAPI fragment;
  undeclared filters → 422 `filter_not_allowed`.
- Sorting: `?sort=-created_at,number` (`-` = desc); sortable fields declared
  per resource; undeclared → 422 `sort_not_allowed`.
- Org scoping is automatic ([authorization & policy §4.2](../04-security/authorization-and-policy.md)): list endpoints never return rows
  outside the actor's org set regardless of filters.

### 3.5 Optimistic concurrency

- Every mutable resource exposes `ETag` = weak validator of its row version
  (`updated_at` hash).
- PUT/PATCH on resources with concurrent-writer risk (orders, stock
  adjustments, journal entries, settings) MUST send `If-Match`; mismatch →
  412 `precondition_failed` with the current resource in `details.current`.
- Endpoints that require it are marked in OpenAPI (`x-if-match: required`).

---
---

## Error Envelope & Error Codes

### 4.1 Envelope (normative)

All non-2xx responses use exactly this shape:

```json
{
  "error": {
    "code": "permission_missing",
    "message": "You do not have permission to approve sales orders.",
    "details": { "permission": "sales:order:approve" },
    "request_id": "req_01J8..."
  }
}
```

Rules:
1. `code` is a stable machine-readable snake_case token from the registry
   below — clients branch on `code`, never on `message`.
2. `message` is human-readable English (localized messages are a v2 item).
3. `details` carries structured, safe-to-display context; MUST NOT contain
   stack traces, SQL, or other tenants' data.
4. Validation errors use code `validation_failed` with
   `details.fields = {field: [reasons]}`.
5. Every error response carries `Navdhya-Request-Id` and body `request_id`.

### 4.2 Error code registry (kernel-owned)

| HTTP | Code | Meaning |
|------|------|---------|
| 400 | `bad_request` | Malformed syntax/JSON |
| 400 | `tenant_header_missing` | Tenant-scoped API call without `X-Tenant-Id` |
| 401 | `unauthenticated` | Missing/invalid credentials |
| 401 | `token_expired` / `token_revoked` / `invalid_audience` / `tenant_not_in_token` | Token-level failures |
| 403 | `permission_missing` | Grant check failed ([authorization & policy §5.1](../04-security/authorization-and-policy.md)-3) |
| 403 | `insufficient_scope` | Token scope check failed ([authorization & policy §5.1](../04-security/authorization-and-policy.md)-4) |
| 403 | `membership_inactive` / `tenant_suspended` | Membership/tenant state |
| 403 | `org_out_of_scope` / `state_forbidden` / `sod_conflict` | ABAC / workflow / SoD denials |
| 403 | `step_up_required` | ACR 2 needed; `details.stepup_url` provided ([identity & token platform §6](../04-security/identity-and-token-platform.md)) |
| 404 | `not_found` | Resource absent **or** outside tenant scope (no existence leakage) |
| 409 | `conflict` / `idempotency_in_progress` / `version_conflict` | State/idempotency conflicts |
| 412 | `precondition_failed` | `If-Match` mismatch (§3.5) |
| 422 | `validation_failed` / `filter_not_allowed` / `sort_not_allowed` / `idempotency_key_reused` / `workflow_guard_failed` | Semantic rejections |
| 429 | `rate_limited` | Throttled (§6); `Retry-After` present |
| 500 | `internal_error` | Unexpected failure (request id logged; nothing sensitive exposed) |
| 503 | `service_unavailable` / `tenant_promoting` | Maintenance windows (§2 [tenancy & data §7](../06-tenancy/tenancy-and-data.md)) |

Modules MAY add codes namespaced `<module>_<code>` (e.g.
`inventory_insufficient_stock`) registered in the kernel error registry at
boot; unregistered codes fail CI.

---
---

## Idempotency

(Infrastructure in [kernel services §12](./kernel-services.md); HTTP contract here.)

1. Clients POST/PATCH with `Idempotency-Key: <UUID|ULID>`; keys are scoped
   per (tenant, client, key) — the same key from different clients never
   collides.
2. First request executes normally; the kernel stores the response
   (status + body) keyed by `Idempotency-Key` alongside a hash of the
   request path + body.
3. Replay with identical payload → stored response returned verbatim with
   header `Idempotent-Replay: true` (same status code, same body).
4. Replay with different payload for the same key → 422
   `idempotency_key_reused` (never silently executes).
5. Concurrent identical request while the first is still executing → 409
   `idempotency_in_progress`.
6. Keys expire after 24 h; clients generating new keys for retries defeat the
   mechanism — SDKs document this explicitly.
7. Endpoints: **required** on payment capture, checkout, order creation,
   stock adjustments, journal posting; **optional** elsewhere (declared in
   OpenAPI `x-idempotency: required|optional`).

---
---

## Rate Limiting & Quotas

### 6.1 Throttle dimensions

Every API request is throttled on (in evaluation order):

1. **Per client** (`client_id`): protects the platform from a runaway
   integration.
2. **Per tenant** (aggregate of the tenant's clients): plan-based fairness.
3. **Per user** (interactive tokens): abuse control.
4. **Per IP** (unauthenticated endpoints only): auth endpoints, discovery.

### 6.2 Tiers (plan-driven, [tenancy & data §3](../06-tenancy/tenancy-and-data.md) `plans.rate_tier`)

| Tier | Sustained (req/min) | Burst | Typical |
|------|---------------------|-------|---------|
| `standard` | 600 | 120 | starter plans |
| `professional` | 3,000 | 600 | mid plans |
| `enterprise` | 12,000 | 2,400 | enterprise plans |
| `partner` | per-client profile ([identity & token platform §2.3](../04-security/identity-and-token-platform.md) `rate_tier`) | — | partner clients |

- Token issuance endpoints carry their own aggressive limits ([identity & token platform §11](../04-security/identity-and-token-platform.md)).
- Algorithm: sliding-window counter in Redis; failures closed to deny only
  when Redis is down **and** the route is not health/discovery.
- Responses carry `X-RateLimit-*` headers; 429 includes `Retry-After`.
- Quota exhaustion is observable (usage meters, [kernel services §11.3](./kernel-services.md)) and feeds plan
  upsell surfaces, not silent drops.

---
---

## Blade Admin ↔ API Convergence

Normative: **the Blade admin console is an API client in disguise.**

1. Every admin screen's mutations go through controller actions that share
   the exact FormRequest + Policy + domain service used by `/api/v1`
   endpoints. Blade rendering is a response-format concern only.
2. Controllers implement one action method per operation; the web route and
   the API route both point at it. Response negotiation: web → Blade view or
   redirect; API → JSON resource.
3. Consequence: the admin console continuously exercises the API contract;
   contract tests ([testing strategy §4](../19-testing/testing-strategy.md)) written against API fixtures automatically
   cover console behavior.
4. Velzon integration specifics:
   - Views live under `resources/views/modules/<module>/` ([domain framework §9](../02-architecture/architecture-master.md)).
   - Server-rendered tables use the same list endpoints' query semantics;
     interactive components (charts, calendars) fetch JSON from the same
     module routes with session auth.
   - CSRF: web forms carry the Laravel CSRF token; API token requests are
     CSRF-exempt by bearer design.
   - Navigation comes from manifests ([domain framework §3.2](../09-domain-framework/domain-framework.md)) — installing/enabling a
     module updates the sidebar with zero layout edits.
5. Step-up flows ([identity & token platform §6](../04-security/identity-and-token-platform.md)) are wired into admin forms: sensitive actions
   render a confirmation that triggers `/oauth/stepup` before submission.

---
---

## Contract-First OpenAPI & Versioning

### 9.1 Fragments & composition

- Each module ships `Http/openapi.yaml` (OpenAPI 3.1) covering its
  `/api/v1/<module>` surface: paths, schemas, examples, security
  requirements (scopes), and vendor extensions:
  - `x-permission`: permission code per operation (must exist in manifest).
  - `x-scope`: required scope per operation.
  - `x-idempotency`, `x-if-match`, `x-sensitive` (step-up).
- Kernel ships shared components (error envelope, pagination, money, ULID,
  timestamp schemas) in `docs/api/components/`.
- `php artisan api:compose` merges kernel + module fragments into
  `docs/api/openapi.v1.yaml` (published artifact) and validates:
  no path collisions, all `x-permission` codes exist, all operationIds
  unique. CI runs this and fails on errors.
- Discovery: `GET /api/v1/openapi.json` serves the composed spec
  (authenticated clients only; partner clients see only their scoped subset).

### 9.2 Versioning policy

- **Major version in path** (`/api/v1`); additive changes never bump it:
  new endpoints, new optional fields, new enum values (clients MUST tolerate
  unknown enum values), new event fields.
- Breaking = new major (`/api/v2`) running in parallel; v1 continues during
  the deprecation window.
- **Minor contract evolution inside v1** is governed: any schema change goes
  through PR review with the OpenAPI diff as a required artifact
  (`api:diff` command prints added/removed/changed operations).

### 9.3 Event schema versioning

Event payloads version per [kernel services §2.2](../12-events/events-and-outbox.md) rule 2 (additive; new
`event_version` integer for breaking). Webhook subscribers receive the
`event_version` in the envelope and MUST route on it.

### 9.4 Deprecation

- Deprecated operations respond with `Deprecation: true` and
  `Sunset: <RFC 7231 date>` headers plus a `Link` to the migration note.
- Minimum deprecation window: **6 months** for partner-facing surfaces,
  3 months for first-party-only surfaces.
- Sunset dates are tracked centrally; CI fails builds that ship a sunset
  operation past its date without removal.

---
---

## Platform-Global API Surfaces

Kernel-owned surfaces (module = `platform`/`identity`):

| Surface | Base path | Auth | Notes |
|---------|-----------|------|-------|
| Discovery & health | `/up`, `/ready`, `/.well-known/*` | public | [architecture master §5](../02-architecture/architecture-master.md), [identity & token platform §2.2](../04-security/identity-and-token-platform.md) |
| OAuth/OIDC | `/oauth/*` | per endpoint | [identity & token platform §2.2](../04-security/identity-and-token-platform.md) |
| Tenant self-service | `/api/v1/platform/tenants` (own memberships), `/api/v1/platform/memberships`, orgs, invites | user token | `platform:*` permissions ([authorization & policy §2.1](../04-security/authorization-and-policy.md)) |
| Identity self-service | `/api/v1/identity/profile`, `/mfa/*`, `/sessions` | user token | kernel scopes ([identity & token platform §2.2](../04-security/identity-and-token-platform.md)) |
| SCIM | `/scim/v2/{tenantSlug}/*` | client credentials + `kernel:scim:write` | [identity & token platform §10](../04-security/identity-and-token-platform.md) |
| Webhook management | `/api/v1/platform/webhooks/*` | user token / partner | §7 |
| Usage & limits | `/api/v1/platform/usage` | tenant admin | meters from [kernel services §11.3](./kernel-services.md) |

Platform-operator (cross-tenant) surfaces are NOT on `/api/v1`; they live on
the kernel admin surface per [security baseline §4](../04-security/security-baseline.md).

---
---

## Verification Requirements

- Contract tests ([testing strategy §4](../19-testing/testing-strategy.md)): every operation exercised with a valid and an
  invalid case; responses validated against the composed OpenAPI.
- Error-code tests: every registry code has a producing test; unknown codes
  in responses fail CI.
- Idempotency tests: replay, mismatched reuse, concurrent in-progress.
- Throttle tests: tier tables asserted with fake Redis clock.
- Webhook tests: signature verification reference implementation, retry
  schedule, circuit breaker, SSRF guard cases.
- Convergence test: for each module, the set of web routes' actions equals
  the set of API actions (no console-only mutations).