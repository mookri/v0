# Customer Contract Parity — BFF view

BFF-side mirror of the contract. The authoritative table lives in the
backend at `platform-navdhya/docs/api/parity-customers.md` (COMPLETE
B1–B8); either side changing a row updates that doc, this mirror, and
both test suites in the same change (`tests/api/customer-contract.test.ts`
encodes the mapping as a reviewed const).

## Status alignment (F1)

`VALIDATION_ERROR` is **422** on both surfaces (was 400 on the BFF).
`packages/api-contracts` `statusFor()` is the single status map; the BFF
`ApiErrorCode` union (`apps/customer/application/api-envelope.ts`) is a
subset of the contract union with identical statuses. New codes:
`REVIEW_NOT_ALLOWED`, `REVIEW_EXISTS` (both 409).

## BFF endpoint → backend source

| BFF endpoint | Backend source |
|---|---|
| `GET /api/home` | composes `GET /api/v1/booking/reservations` + `GET /api/v1/customers/profile` |
| `GET /api/bookings` | `GET /api/v1/booking/reservations` (`?section=`) |
| `GET /api/bookings/:bookingId` | `GET /api/v1/booking/reservations/{id}` (+ live `actions`) |
| `POST /api/bookings/:bookingId/cancel` | `POST /api/v1/booking/reservations/{id}:cancel` (idempotent; 409 → `BOOKING_CANNOT_CANCEL`) |
| `POST /api/bookings/:bookingId/reschedule` | `POST …/{id}:reschedule {new_start_time, version}` (stale → 409 `STALE_RESOURCE`, overlap → 409 `BOOKING_SLOT_CONFLICT`) |
| `GET /api/profile` | `GET /api/v1/customers/profile` (masked contacts) |
| `GET /api/saved` | `GET /api/v1/customers/saved` (opaque refs, enriched here) |
| `GET /api/notifications` | `GET /api/v1/customers/notifications` |
| `GET /api/discover` | `GET /api/v1/customers/discover` (`{recommended, popular, recent}` unwrapped from the kernel envelope) |
| `GET /api/settings` | `GET /api/v1/customers/settings` (key-values mapped to sections here) |
| `POST /api/auth/login` | `POST /api/v1/customers/auth/otp` then `POST …/auth/login`; shapes `{token, sessionId, expiresAt}` from `{token, actor.session_id, expires_at}` |
| `POST /api/auth/logout` | `POST /api/v1/customers/auth/logout` |

## Code correspondence (BFF codes)

| Situation | BFF code | Backend code |
|---|---|---|
| unknown identity / bad OTP | `UNAUTHENTICATED` (401) | 202 decoy, then `unauthenticated` (401) |
| flood | `RATE_LIMITED` (429) | `rate_limited` (429) |
| deactivated login | `UNAUTHENTICATED` (401, collapsed) | `membership_inactive` (403) |
| foreign/unknown resource | `BOOKING_NOT_FOUND` / `NOT_FOUND` (404) | `not_found` (404) |
| bad fields | `VALIDATION_ERROR` (**422**) | `validation_failed` (422 + `details.fields`) |
| illegal cancel/reschedule | `BOOKING_CANNOT_CANCEL` / `BOOKING_RESCHEDULE_UNAVAILABLE` (409) | `state_forbidden` (409) |
| slot overlap | `BOOKING_SLOT_CONFLICT` (409) | `conflict` (409) |
| stale version | `STALE_RESOURCE` (409) | `version_conflict` (409) |
| review on non-completed | `REVIEW_NOT_ALLOWED` (409) | `state_forbidden` (409) |
| duplicate review | `REVIEW_EXISTS` (409) | `conflict` (409) |

HTTP statuses are identical everywhere: that is the cross-surface contract.
