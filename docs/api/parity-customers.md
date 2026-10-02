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
| `GET /api/home` | composes `GET /api/v1/booking/reservations` + `GET /api/v1/customers/profile` — **wired behind flag** (profile + upcoming + discover + activity, fail-closed) |
| `GET /api/bookings` | `GET /api/v1/booking/reservations` � **wired behind flag, drilled live** |
| `GET /api/bookings/:bookingId` | `GET /api/v1/booking/reservations/{id}` � **wired behind flag, drilled live** |
| `POST /api/bookings/:bookingId/cancel` | `POST /api/v1/booking/reservations/{id}:cancel` — **wired behind flag** (idempotent; 409 → `BOOKING_CANNOT_CANCEL`) |
| `POST /api/bookings/:bookingId/reschedule` | `POST …/{id}:reschedule` — **wired behind flag** (reads live version first; stale → `STALE_RESOURCE`, overlap → `BOOKING_SLOT_CONFLICT`) |
| `GET /api/profile` | `GET /api/v1/customers/profile` (masked contacts) � **wired behind flag** |
| `GET /api/saved` | `GET /api/v1/customers/saved` � **wired behind flag** (interim title=ref mapping; catalog enrichment later) |
| `GET /api/notifications` | `GET /api/v1/customers/notifications` � **wired behind flag** |
| `GET /api/discover` | `GET /api/v1/customers/discover` � **wired behind flag** (envelope unwrapped here) |
| `GET /api/settings` | `GET /api/v1/customers/settings` � **wired behind flag** (values merged into sections response) |
| `POST /api/auth/login` | `POST /api/v1/customers/auth/otp` then `POST …/auth/login` (with optional `totpCode` → `totp_code`) - **wired behind flag, drilled (401 path live)**; `{sessionId, expiresAt}`, bearer in HttpOnly cookie, `token` omitted by design |
| `POST /api/auth/signup` | `POST /api/v1/customers/auth/signup` - **wired behind flag** (intent, 202 challenge) |
| `POST /api/auth/verify` | `POST /api/v1/customers/auth/verify` - **wired behind flag** (201 + session cookie, no token to client) |
| `POST /api/auth/logout` | `POST /api/v1/customers/auth/logout` � **wired behind flag, drilled live** |

| `GET /api/addresses` | `GET /api/v1/customers/addresses` - **wired behind flag** |
| `POST /api/addresses` | `POST /api/v1/customers/addresses` - **wired behind flag** |
| `PATCH /api/addresses/:id` | `PATCH /api/v1/customers/addresses/{id}` - **wired behind flag** |
| `DELETE /api/addresses/:id` | `DELETE /api/v1/customers/addresses/{id}` - **wired behind flag** |
| `POST /api/addresses/:id/default` | `POST /api/v1/customers/addresses/{id}:default` - **wired behind flag** |
| `GET /api/activity` | `GET /api/v1/customers/activity` (`?kind=`) - **wired behind flag** |
| `GET /api/support/tickets` | `GET /api/v1/customers/support/tickets` - **wired behind flag** |
| `POST /api/support/tickets` | `POST /api/v1/customers/support/tickets` - **wired behind flag** |
| `GET /api/support/tickets/:id` | `GET /api/v1/customers/support/tickets/{id}` - **wired behind flag** |
| `POST /api/support/tickets/:id/messages` | reply (re-opens) - **wired behind flag** |
| `POST /api/support/tickets/:id/close` | close - **wired behind flag** |
| `GET /api/referrals` | `GET /api/v1/customers/referrals` - **wired behind flag** |
| `GET /api/reviews` | `GET /api/v1/booking/reviews` - **wired behind flag** |
| `POST /api/bookings/:bookingId/reviews` | `POST /api/v1/booking/reservations/{id}/reviews` - **wired behind flag** |
| `DELETE /api/reviews/:id` | `DELETE /api/v1/booking/reviews/{id}` - **wired behind flag** |

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
