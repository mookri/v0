# Vendor Contract Parity — BFF view

BFF-side mirror of the contract. The authoritative table lives in the
backend at `platform-navdhya/docs/api/parity-vendors.md` (P12 complete);
either side changing a row updates that doc, this mirror, and both test
suites in the same change (`tests/api/vendor-contract.test.ts` encodes
backend code → BFF code → status as a reviewed const, plus the registry
↔ route-file coverage both directions).

## Status alignment

`tests/api/vendor-contract.test.ts` `VENDOR_BACKEND_PARITY` pins every
code the vendor surface can emit: both wrong fallbacks and wrong remaps
fail the suite. HTTP statuses are identical on both surfaces — 401
unauthenticated, 403 membership/scope/foreign, 404 unknown, 409 state and
conflict, 422 validation, 429 flood.

## BFF endpoint → backend source

| BFF endpoint | Backend source |
|---|---|
| `GET /api/vendors` | `GET /api/v1/vendor/partner/vendors` |
| `POST /api/vendors` | `POST /api/v1/vendor/partner/vendors` |
| `GET /api/vendors/:vendorId` | `GET /api/v1/vendor/partner/vendors/{id}` |
| `PATCH /api/vendors/:vendorId` | `PATCH /api/v1/vendor/partner/vendors/{id}` |
| `GET /api/vendors/:vendorId/products` | `GET /api/v1/vendor/partner/vendors/{id}/products` |
| `POST /api/vendors/:vendorId/products` | `POST /api/v1/vendor/partner/vendors/{id}/products` |
| `PATCH /api/vendors/:vendorId/products/:itemId` | `PATCH /api/v1/vendor/partner/vendors/{id}/products/{itemId}` |
| `GET /api/vendors/:vendorId/inventory` | `GET /api/v1/vendor/partner/vendors/{id}/inventory` |
| `POST /api/vendors/:vendorId/inventory/adjust` | `POST /api/v1/vendor/partner/vendors/{id}/inventory:adjust` |
| `GET /api/vendors/:vendorId/orders` | `GET /api/v1/vendor/partner/vendors/{id}/orders` |
| `POST /api/vendors/:vendorId/orders` | `POST /api/v1/vendor/partner/vendors/{id}/orders` |
| `GET /api/vendors/:vendorId/orders/:orderId` | `GET /api/v1/vendor/partner/vendors/{id}/orders/{orderId}` |
| `POST /api/vendors/:vendorId/orders/:orderId/advance` | `POST /api/v1/vendor/partner/vendors/{id}/orders/{orderId}:advance` |
| `POST /api/vendors/:vendorId/orders/:orderId/cancel` | `POST /api/v1/vendor/partner/vendors/{id}/orders/{orderId}:cancel` |
| `GET /api/vendors/:vendorId/payouts` | `GET /api/v1/vendor/partner/vendors/{id}/payouts` |

The two inventory/order RPC verbs are REST sub-paths on the BFF and
`:adjust`/`:advance`/`:cancel` colon verbs on the backend — same
semantics, path style is the only difference (documented in the backend
table too). The admin directory/verify/suspend surface is backend-only
until an ops UI lands.

## Code correspondence (BFF codes)

| Situation | BFF code | Backend code |
|---|---|---|
| missing/expired credential | `UNAUTHENTICATED` (401) | `unauthenticated` (401) |
| inactive membership | `UNAUTHENTICATED` (401, collapsed) | `membership_inactive` (403) |
| suspended tenant | `FORBIDDEN` (403) | `tenant_suspended` (403) |
| foreign vendor (same tenant) | `FORBIDDEN` (403) | `permission_missing` (403) |
| unknown / cross-tenant resource | `NOT_FOUND` (404) | `not_found` (404) |
| no/unapproved application; bad order state; archived product | `CONFLICT` (409) | `state_forbidden` (409) |
| duplicate application / SKU / order reference; negative stock | `CONFLICT` (409) | `conflict` (409) |
| bad fields | `VALIDATION_ERROR` (422) | `validation_failed` (422) |
| flood | `RATE_LIMITED` (429) | `rate_limited` (429) |
| unexpected failure | `INTERNAL_ERROR` (500) | `internal_error` (500) |
| backend dependency down | `DEPENDENCY_UNAVAILABLE` (503) | `service_unavailable` (503) |

HTTP statuses are identical everywhere: that is the cross-surface
contract. Mock mode (`PARTNER_BACKEND` unset) mirrors the Laravel rules
and messages one-for-one — `tests/partner/vendor-bff.test.ts` pins them
(401 anon, 409 application gate, 409 duplicate SKU/reference, 409
negative stock, 409 terminal order moves, 404/403 ownership).
