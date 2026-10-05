# Vendor Contract Parity — Partner BFF ↔ Laravel vendor module

**Status:** P12 complete (backend P12b/P12e, partner BFF P12c, partner
pages P12d; backend suite 532 passed, frontend 332 tests passed).

Both surfaces implement the same flow with their platform's envelope and
code registry. This table is the contract: either side changing a row
updates this doc and both test suites in the same change.

## Flow parity (P12: partner self-serve)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| vendors list | `GET /api/vendors` → `{data, meta: {page}}` | `GET /api/v1/vendor/partner/vendors` → 200 kernel envelope + cursor page | **wired behind flag** (owner-scoped both sides) |
| vendor create | `POST /api/vendors` → 201 `{data}` | `POST /api/v1/vendor/partner/vendors` → 201 (APPROVED partner-vendor application gate) | **wired behind flag** — create answers **201** in mock and Laravel alike |
| vendor read | `GET /api/vendors/:vendorId` → 200 | `GET …/{id}` → 200 | equal (403 foreign same-tenant, 404 cross-tenant) |
| vendor update | `PATCH /api/vendors/:vendorId` → 200 | `PATCH …/{id}` → 200 | equal (name min 2 / description 2000 / city, state 100 — same strict fields) |
| products list | `GET …/products` → `{data}` | `GET …/products` → 200 (limit list; `meta` = `request_id` only, no cursor page) | equal — bounded catalog, deliberate limit list |
| product create | `POST …/products` → 201 | `POST …/products` → 201 (DRAFT start via the Catalog P12a ports) | equal (409 `conflict` duplicate SKU) |
| product update | `PATCH …/products/:itemId` → 200 | `PATCH …/products/{itemId}` → 200 | equal (409 `state_forbidden` when archived) |
| inventory list | `GET …/inventory` → `{data, meta: {page}}` | `GET …/inventory` → 200 + cursor page | equal |
| inventory adjust | `POST …/inventory/adjust` → 200 | `POST …/inventory:adjust` → 200 (signed delta; row created on first adjustment) | **path style differs** — BFF REST sub-path vs backend `:adjust` RPC verb; semantics equal (409 `conflict`, stock never negative) |
| orders list | `GET …/orders` → `{data, meta: {page}}` (rows omit items) | `GET …/orders` → 200 + cursor page (`?status=`) | equal — detail carries the lines |
| order place | `POST …/orders` → 201 (lines inlined) | `POST …/orders` → 201 (snapshotted lines, computed `total_minor`) | equal (409 `conflict` duplicate reference per vendor) |
| order read | `GET …/orders/:orderId` → 200 + items | `GET …/orders/{orderId}` → 200 + inlined items | equal — resolved inside the vendor (foreign order is 404) |
| order advance | `POST …/orders/:orderId/advance` → 200 | `POST …/orders/{orderId}:advance` → 200 (append-only transition row) | **path style differs** — `:advance` RPC verb; semantics equal (409 `state_forbidden`/`conflict` on terminal states) |
| order cancel | `POST …/orders/:orderId/cancel` → 200 | `POST …/orders/{orderId}:cancel` → 200 | equal — only NEW/PROCESSING cancellable (409 otherwise) |
| payouts list | `GET …/payouts` → `{data, meta: {page}}` | `GET …/payouts` → 200 + cursor page (`?status=`) | equal — empty stub until settlement |

The admin surface (`GET /api/v1/vendor/admin/vendors`, `:verify`,
`:suspend`) is backend-only for now: capability `vendor:profile:view` for
the directory, `vendor:profile:manage` + step-up level 2 on the sensitive
verbs (409 `state_forbidden` on repeat verify/suspend).

## Code correspondence (stable)

| Situation | Next.js code | Laravel code |
|---|---|---|
| missing/expired credential | `UNAUTHENTICATED` (401) | `unauthenticated` (401) |
| inactive membership | `UNAUTHENTICATED` (401, collapsed) | `membership_inactive` (403) |
| suspended tenant | `FORBIDDEN` (403) | `tenant_suspended` (403) |
| foreign vendor (same tenant) | `FORBIDDEN` (403) | `permission_missing` (403) |
| unknown / cross-tenant resource | `NOT_FOUND` (404) | `not_found` (404) |
| no/unapproved application; bad order state; archived product | `CONFLICT` (409) | `state_forbidden` (409) |
| duplicate application / SKU / order reference; negative stock | `CONFLICT` (409) | `conflict` (409) |
| bad fields | `VALIDATION_ERROR` (422) | `validation_failed` (422 + `details.fields`) |
| flood | `RATE_LIMITED` (429) | `rate_limited` (429) |
| unexpected failure | `INTERNAL_ERROR` (500) | `internal_error` (500) |
| backend dependency down | `DEPENDENCY_UNAVAILABLE` (503) | `service_unavailable` (503) |

Codes differ by registry (BFF domain codes vs kernel snake codes) — clients
MUST branch on documented per-surface codes, never assume string equality
across surfaces. HTTP statuses are identical everywhere: that is the
cross-surface contract. `tests/api/vendor-contract.test.ts` pins backend
code → BFF code → status for every row above, and
`tests/partner/vendor-bff.test.ts` exercises the same rules/messages on
the mock surface (`PARTNER_BACKEND=laravel` swaps in the real one).
