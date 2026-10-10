# Customer Contract Parity — Next.js BFF ↔ Laravel modules

**Backend status:** COMPLETE (B1—B8 + P7 + P8a, suite 628 passed) · Frontend wiring **F1–F2 landed behind `CUSTOMER_BACKEND=laravel`** (mocks remain the default).

Both surfaces implement the same flow with their platform's envelope and
code registry. This table is the contract: either side changing a row
updates this doc and both test suites in the same change.

## Flow parity (B2: auth)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| OTP request | `POST /api/auth/login {identityId, proof}` → `{token, sessionId, expiresAt}` (single call, memory identity) | `POST /api/v1/customers/auth/otp {tenant_id, purpose, identity}` → 202 `{challenge_id, expires_in}` | **diverged by design** — BFF keeps its shape; backend splits challenge + redeem (rate control). BFF maps: otp call, then redeem, then shapes `{token, sessionId, expiresAt}` from `{token, actor.session_id, expires_at}` |
| signup | `POST /api/auth/signup {phone, name}` → 202 `{challengeId, expiresIn}` | `POST /api/v1/customers/auth/signup` → 202 `{challenge_id}` | **wired behind flag** |
| signup verify | `POST /api/auth/verify {challengeId, code}` → 201 `{sessionId, expiresAt}` + session cookie (no token to the client) | `POST /api/v1/customers/auth/verify` → 201 `{token, expires_at, tenant_id, actor}` | **wired behind flag** |
| login redeem | `POST /api/auth/login {identityId, proof, totpCode?}` → `{sessionId, expiresAt}` (bearer in HttpOnly cookie, `token` omitted by design) | `POST /api/v1/customers/auth/login` → 200 `{token, expires_at, tenant_id, actor}` (+ optional `totp_code` when the account has MFA enrolled — absent/wrong collapses to 401) | **wired behind flag** (BFF fans out otp → redeem; `totpCode` passthrough) |
| logout | `POST /api/auth/logout` → `{signedOut:true}` | `POST /api/v1/customers/auth/logout` → 200 `{signed_out:true}` | equal semantics (both best-effort until jti store) |
| profile read | `GET /api/profile` → `{profile}` | `GET /api/v1/customers/profile` → 200 masked profile | **wired behind flag** |
| profile update | `PATCH /api/profile` → `{profile}` | `PATCH /api/v1/customers/profile` → 200 (strict fields) | **wired behind flag** |
| deactivate/reactivate | `POST /api/profile:deactivate` (fresh OTP) / `POST /api/profile:reactivate` (public OTP) | `POST /api/v1/customers/profile:deactivate` (authed + fresh OTP) / `:reactivate` (**public** OTP — suspended members cannot pass the tenant gate) → 200 | **wired behind flag** |
| account deletion | `POST /api/profile:delete` (fresh OTP) → 200 `{id, status, deleted_at}` | `POST /api/v1/customers/profile:delete` → 200 (soft delete: sets deleted_at, suspends membership, audit `customers.account.deleted`) | **wired behind flag** |
| verifications | `GET /api/verifications` → `{verifications[]}` / `POST /api/verifications` → 202 `{challenge_id}` / `POST /api/verifications/confirm {id, code}` → 200 | `GET /api/v1/customers/verifications`, `POST …` → 202, `POST …/{id}:confirm` → 200 | **wired behind flag** |
| addresses | `GET/POST /api/addresses`, `PATCH/DELETE /api/addresses/:id`, `POST /api/addresses/:id/default` | `GET/POST /api/v1/customers/addresses`, `PATCH/DELETE …/{id}`, `POST …/{id}:default` (cap 20, default promotion) | **wired behind flag** |
| saved | `GET /api/saved` → items | `GET/POST /api/v1/customers/saved` (opaque ULID refs, enriched by readers — deliberate: bookmarks, not FKs), `DELETE …/{id}` | **wired behind flag** (interim `title=ref` mapping; catalog enrichment later) |
| notifications | `GET /api/notifications` → cursor page | `GET /api/v1/customers/notifications` (cursor), `POST …/{id}:read`, `POST …:read-all` (reports flip count) | **wired behind flag** (producers live: N2 lifecycle facts → N1 dispatch) |
| settings | `GET /api/settings` → `{sections, values}` / `PATCH /api/settings` → `{sections, values}` | `GET/PATCH /api/v1/customers/settings` (closed key set over defaults; the BFF maps key-values → sections) | **wired behind flag** |
| bookings list/detail | `GET /api/bookings` → cards, `GET /api/bookings/:id` → details+actions | `GET /api/v1/booking/reservations` (cursor + `?section=` upcoming/active/completed/cancelled), `GET …/{id}` (+ live `actions`) | **wired behind flag, drilled live** (section mapping: upcoming = pre-service states, active = ASSIGNED/IN_PROGRESS, completed = COMPLETED, cancelled = terminal rest) |
| bookings create | `POST /api/bookings` → 201 `{booking}` | `POST /api/v1/booking/reservations` → 201 (DRAFT, idempotent on Idempotency-Key, overlap guard, SKU validity via Catalog port) | **wired behind flag** (S15: `service_sku` is the catalog SKU, never the item id — the review step resolves item id → SKU via `GET /api/pujas/:id` before POST, and the slot is chosen on the review step) |
| cancel | `POST …/cancel` → 409 `BOOKING_CANNOT_CANCEL` | `POST /api/v1/booking/reservations/{id}:cancel` → 200 (idempotent on CANCELLED), else 409 `state_forbidden` | **wired behind flag** (409 → `BOOKING_CANNOT_CANCEL`; idempotent replay is safe to retry) |
| reschedule | `POST …/reschedule {newStartTime}` → 409 | `POST …/{id}:reschedule {new_start_time, version}` → 200; stale → 409 `version_conflict`, overlap → 409 `conflict`, off-state → 409 `state_forbidden` | **wired behind flag** (BFF reads the live version first; stale → `STALE_RESOURCE`, overlap → `BOOKING_SLOT_CONFLICT`) |
| history | `GET /api/bookings/:bookingId/history` → `{transitions[]}` | `GET …/{id}/history` (append-only transitions) | **wired behind flag** |
| reviews | `GET /api/reviews`, `POST /api/bookings/:bookingId/reviews`, `DELETE /api/reviews/:id` | `POST …/{id}/reviews` (COMPLETED only, one per booking), `GET /api/v1/booking/reviews`, `DELETE …/{id}` | **wired behind flag** |
| discover | `GET /api/discover` → composed feed | `GET /api/v1/customers/discover` → `{recommended, popular, recent}` (SQL-only: recency + 30d booking rank + novelty exclusion; no ML) | **wired behind flag** (BFF unwraps kernel envelope) |
| activity | `GET /api/activity` → cursor items | `GET /api/v1/customers/activity` (cursor + `?kind=`, caller-scoped, dedicated read model — never audit rows) | **wired behind flag** |
| referrals | `GET /api/referrals` → code + count + list | `GET /api/v1/customers/referrals` (code + count + masked list; attribution only, no rewards) | **wired behind flag** |
| support | `GET/POST /api/support/tickets`, thread + reply + close | `POST /api/v1/customers/support/tickets` (cap 10 open), `GET …` (cursor), `GET …/{id}` (thread), `POST …/{id}/messages` (re-opens CLOSED), `POST …/{id}:close` | **wired behind flag**; staff console is a separate surface (S4, below) |
| mfa status | `GET /api/mfa/status` → `{enabled, enrolled_at}` | `GET /api/v1/identity/mfa` → 200 `{object: mfa_status, enabled, enrolled_at}` | **wired behind flag** |
| mfa enroll | `POST /api/mfa/enroll` → `{otpauth_url, secret}` | `POST /api/v1/identity/mfa/enroll` → 201 `{otpauth_url, secret}` | **wired behind flag** |
| mfa confirm | `POST /api/mfa/confirm {code}` → `{recovery_codes}` | `POST /api/v1/identity/mfa/confirm` → 200 `{recovery_codes}` | **wired behind flag** |
| mfa verify (step-up) | `POST /api/mfa/verify {code}` → `{verified: true}` | `POST /api/v1/identity/mfa/verify` → 200 `{verified: true}` (writes step-up proof row) | **wired behind flag** |
| mfa revoke | `POST /api/mfa/revoke {code}` → `{revoked: true}` | `POST /api/v1/identity/mfa/revoke` → 200 `{revoked: true}` (requires live TOTP) | **wired behind flag** |
| mfa recovery codes | `POST /api/mfa/recovery-codes {code}` → `{recovery_codes}` | `POST /api/v1/identity/mfa/recovery-codes` → 200 `{recovery_codes}` (requires live TOTP) | **wired behind flag** |
| sessions list | `GET /api/sessions` → `[{id, ip, ua, last_active}]` | `GET /api/v1/identity/sessions` → 200 `{data: [...], meta}` | **wired behind flag** |
| revoke other sessions | `POST /api/sessions/revoke-others` → `{revoked_count}` | `POST /api/v1/identity/sessions:revoke-others` → 200 `{revoked_count}` | **wired behind flag** |

## Puja / Catalog (P7)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| pujas list | `GET /api/pujas` → `{items: [{id, title, kind:'puja', sku, currency, price_minor, description}]}` | `GET /api/v1/catalog/items?status=ACTIVE` → 200 kernel envelope with items array | **wired behind flag** (BFF unwraps kernel envelope) |
| puja detail | `GET /api/pujas/:pujaId` → `{id, title, kind:'puja', sku, currency, price_minor, description}` | `GET /api/v1/catalog/items/{id}` → 200 kernel envelope with item | **wired behind flag** (404 → NOT_FOUND) |
| puja quote | `POST /api/pujas/:pujaId/quote {currency, lines[]}` → `{object:'price_quote', ...}` | `POST /api/v1/catalog/quote {currency, lines[]}` → 200 kernel envelope with quote | **wired behind flag** (422 validation_failed → VALIDATION_ERROR) |

Profile/saved/notifications/settings/bookings/discover/activity/support rows below are implemented (B3–B7).

## Code correspondence (stable)

| Situation | Next.js code | Laravel code |
|---|---|---|
| unknown identity OTP | `UNAUTHENTICATED` (401) | 202 decoy challenge (no oracle); redeem → `unauthenticated` (401) |
| wrong/expired/exhausted/replayed code | `UNAUTHENTICATED` (401) | `unauthenticated` (401, all collapse) |
| OTP/login flood | `RATE_LIMITED` (429) | `rate_limited` (429) |
| signup duplicate phone | — | `conflict` (409) |
| deactivated account login | `UNAUTHENTICATED` (401, collapsed) | `membership_inactive` (403; BFF collapses to 401) |
| unknown tenant | — | `not_found` (404) |
| bad fields | `VALIDATION_ERROR` (**422 after F1 alignment**) | `validation_failed` (422 + `details.fields`) |
| already-verified contact | — | `conflict` (409) |
| deactivate/reactivate wrong state | — | `state_forbidden` (409) |
| deactivate without fresh OTP | — | `unauthenticated` (401) |
| machine caller on self-serve | — | `membership_inactive` (403) |
| missing profile (staff-actor edge) | — | `not_found` (404) |
| address book full (20) | — | `conflict` (409) |
| duplicate saved ref | — | `conflict` (409) |
| malformed saved ref | — | `validation_failed` (422, `details.fields.ref_id`) |
| unknown setting key / mistyped value | — | `validation_failed` (422) |
| illegal cancel/reschedule state | `BOOKING_CANNOT_CANCEL` / `BOOKING_RESCHEDULE_UNAVAILABLE` (409) | `state_forbidden` (409) |
| reschedule slot overlap | `BOOKING_SLOT_CONFLICT` (409) | `conflict` (409) |
| stale booking version | `STALE_RESOURCE` (409) | `version_conflict` (409) |
| review on non-completed booking | `REVIEW_NOT_ALLOWED` (409, new additive) | `state_forbidden` (409) |
| duplicate review | `REVIEW_EXISTS` (409, new additive) | `conflict` (409) |
| support cap / close-when-closed | `CONFLICT` (409) | `conflict` / `state_forbidden` (409) |
| staff: missing capability / guest | — | `authorization_denied` (403) / `unauthenticated` (401) |
| staff: foreign or unknown ticket id | — | `not_found` (404) |
| staff: close already-closed / bad status filter | — | `state_forbidden` (409, same code as customer side) / `validation_failed` (422) |
| machine principal on the staff console | — | `membership_inactive` (403, customer data is human-only) |
| missing Idempotency-Key on booking create | `VALIDATION_ERROR` (422) | `validation_failed` (422, `details.fields.Idempotency-Key`) |
| invalid/inactive service SKU on create | `VALIDATION_ERROR` (422) | `validation_failed` (422, `details.fields.service_sku`) |
| account deletion (soft delete) | `VALIDATION_ERROR` (422, bad OTP) / `FORBIDDEN` (403, no membership) | `validation_failed` (422) / `membership_inactive` (403) |

Codes differ by registry (BFF domain codes vs kernel snake codes) — clients
MUST branch on documented per-surface codes, never assume string equality
across surfaces. HTTP statuses are identical everywhere: that is the
cross-surface contract (validation: 422 both sides after the F1 alignment).

## Pandit directory (P9)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| pandit directory | `GET /api/pandits` → `{data: {items: PanditProfile[], pagination: {hasMore, nextCursor}}, requestId}` | `GET /api/v1/pandit/profiles` → 200 kernel envelope with items + cursor page (verified + ACTIVE only) | **wired behind flag** (BFF unwraps kernel envelope; `?cursor=`/`?limit=` forwarded untouched, `meta.page` mapped to `pagination`) |
| pandit detail | `GET /api/pandits/:panditId` → `{id, name, bio, specializations[], market, languages[], experience_years, rating_avg, review_count, verification_status, status, created_at}` | `GET /api/v1/pandit/profiles/{id}` → 200 kernel envelope with profile (404 `PANDIT_NOT_FOUND` for unknown/unverified/suspended) | **wired behind flag** (404 → NOT_FOUND) |
| pandit availability | `GET /api/pandits/:panditId/availability?start_date&end_date` → `AvailabilitySlot[]` (kernel envelope unwrapped to a bare array) | `GET /api/v1/pandit/profiles/{id}/availability` → 200 kernel envelope with slots (404 `PANDIT_NOT_FOUND` for unknown/unverified/suspended) | **wired behind flag** (404 → NOT_FOUND) |

### Pandit code correspondence

| Situation | Next.js code | Laravel code |
|---|---|---|
| unknown/unverified/suspended pandit | `NOT_FOUND` (404) | `PANDIT_NOT_FOUND` (404) |
| invalid query params (date format, rating range) | `VALIDATION_ERROR` (422) | `validation_failed` (422 + `details.fields`) |
| guest / unauthenticated caller | `UNAUTHENTICATED` (401) | `unauthenticated` / `tenant_not_in_token` (401) — kernel binds tenant only for a principal |

## Temple directory + dashboard (P11)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| temple directory (customer) | `GET /api/temples` → `{data: TempleProfile[], meta: {page}}` | `GET /api/v1/temple/profiles` → 200 kernel envelope with items + cursor page (VERIFIED + ACTIVE only) | **wired behind flag** (BFF unwraps the kernel envelope to the `{data, meta: {page}}` payload the page reads — S13 fixed a wired branch that returned a bare array and rendered an empty directory; `?cursor=` honored end-to-end since S12, where the API previously dropped it and replayed page 1) |
| temple detail (customer) | `GET /api/temples/:templeId` → profile | `GET /api/v1/temple/profiles/{id}` → 200 kernel envelope with profile (404 `TEMPLE_NOT_FOUND` for unknown/unverified/suspended id) | **wired behind flag** (404 `TEMPLE_NOT_FOUND` → `NOT_FOUND`) |
| temple directory (partner) | `GET /api/temples` → `{data: TempleProfile[], meta: {page}}` | `GET /api/v1/temple/profiles` → same backend as the customer directory | **wired behind flag** (`PARTNER_BACKEND=laravel`) |
| temple detail (partner) | `GET /api/temples/:templeId` → profile | `GET /api/v1/temple/profiles/{id}` → same backend as the customer detail | **wired behind flag** (404 `TEMPLE_NOT_FOUND` → `NOT_FOUND`) |
| temple dashboard (partner) | `GET /api/temples/:templeId/dashboard/:section` → section payload | `GET /api/v1/temple/partner/temples/{id}/dashboard/{section}` — overview, pujas, bookings, calendar, analytics, settings (overview + settings resolve the temple row: unknown → 404 `not_found`; the other sections scope-query and answer 200 with empty data) | **wired behind flag** (`not_found` → `NOT_FOUND`, `permission_missing` → `FORBIDDEN`, `validation_failed` → `VALIDATION_ERROR`) |
| temple pujas (public) | `GET /api/temples/:templeId/pujas?limit&cursor` → `{data: assignments, meta: {page}}` | `GET /api/v1/temple/profiles/{templeId}/pujas?limit` → 200 `{data: assignments, meta: {request_id, page}}`; 404 `TEMPLE_NOT_FOUND` | **wired behind flag** (query forwarded untouched, 404 → `NOT_FOUND`, 422 → `VALIDATION_ERROR`) |
| temple availability (public) | `GET /api/temples/:templeId/availability?start_date&end_date` → `AvailabilitySlot[]` (bare array, like pandit availability) | `GET /api/v1/temple/profiles/{templeId}/availability?start_date&end_date` → 200 `{data: slots, meta: {request_id}}`; 404 `TEMPLE_NOT_FOUND`, 422 `validation_failed` | **wired behind flag** (404 → `NOT_FOUND`, 422 → `VALIDATION_ERROR`) |
| temple puja show (partner) | `GET /api/temples/:templeId/pujas/:pujaId` → assignment | `GET /api/v1/temple/partner/temples/{templeId}/pujas/{pujaId}` → 200 assignment; 404 `TEMPLE_PUJA_NOT_FOUND` | **wired behind flag** (`PARTNER_BACKEND=laravel`; 404 → `NOT_FOUND`, `permission_missing` → `FORBIDDEN`) |

Public visibility is fail-closed: the directory, detail, pujas and availability
expose only VERIFIED + ACTIVE temples — unverified, suspended and unknown ids
collapse into the same 404 (no vetting oracle). Only ACTIVE assignments surface
publicly, and malformed schedule entries are skipped, never fatal. (P11i closed
the P11a detail gap: the profile detail no longer resolves known-but-unvetted
ids.)

### Temple code correspondence

| Situation | Next.js code | Laravel code |
|---|---|---|
| unknown/unverified/suspended temple (public detail, pujas, availability) | `NOT_FOUND` (404) | `TEMPLE_NOT_FOUND` (404) |
| unknown/foreign puja assignment (partner show) | `NOT_FOUND` (404) | `TEMPLE_PUJA_NOT_FOUND` (404) |
| unknown temple on dashboard (overview, settings) | `NOT_FOUND` (404) | `not_found` (404 — kernel rendering of the scoped lookup) |
| invalid query params (limit, start_date/end_date) | `VALIDATION_ERROR` (422) | `validation_failed` (422 + `details.fields`) |
| guest / unauthenticated caller | `UNAUTHENTICATED` (401) | `unauthenticated` / `tenant_not_in_token` (401) — kernel binds tenant only for a principal |

## Instant-book search (B2)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| instant search | `GET /api/instant-search?date&start&end&puja_id&limit` → `{data: {object: 'instant_search', date, start, end, puja_id, pandits[], temple_slots[]}, requestId}` | `GET /api/v1/booking/instant-search` (Booking module; `scope:booking:read` + `throttle:60,1`) → 200 `{data, meta: {request_id}}`; 422 `validation_failed` | **wired behind flag** (window forwarded untouched, 422 → `VALIDATION_ERROR`; mock backend → empty result set, never an invented pandit or slot) |

Assignability is computed here, never in the BFF: pandits are VERIFIED +
ACTIVE with an availability window that **contains** the whole slot
(containment, not overlap — clock-only rows compared as
minutes-since-midnight) and no PENDING/CONFIRMED assignment conflict
(fail-closed); temple slots come from VERIFIED + ACTIVE temples'
weekday schedule windows covering the slot, filtered by `puja_id` when
given. Capacity is surfaced as-is (never decremented). The BFF hands a
selection to `book/start` as a versioned intent (`source:
instant-search`) carrying puja + pandit + slot; the reservation contract
itself still books puja + schedule only — assignment wiring lands with
the checkout track.

## Checkout (B3)

Backend authority only this cycle: Laravel is the single money
authority; no BFF or customer UI is wired until the checkout frontend
track lands (the flow is absent end-to-end until then — the flag stays
off in every deployed environment).

| Step | Laravel module | Match |
|---|---|---|
| begin checkout | `POST /api/v1/booking/reservations/{id}:confirm` (Booking; `throttle.tier`, ownership gate, flag-gated on `payments.enabled` → 404 while off) → 201 `{data: {object: 'payment_intent', id, booking_id, status: PENDING, amount_minor, currency, provider, provider_order_id, test_mode}, meta: {request_id}}`; 409 `state_forbidden`; 422 `validation_failed`; 503 `dependency_unavailable` | **backend only** (idempotent per booking — replay returns the same intent, one provider order ever) |
| provider webhook | `POST /api/v1/payment/webhook` (Payment; anonymous — `withoutMiddleware(['auth.any', 'tenant'])`, `throttle:30,1`) → 200 receipt `{data: {object: 'webhook_receipt', status: processed\|ignored, intent_id, reason}}`; 401 forged signature; 503 verification unconfigured | **backend only** (HMAC-SHA256 over the raw body vs `RAZORPAY_WEBHOOK_SECRET`) |

Money discipline: `:confirm` re-quotes through the catalog quote port
(QuoteCalculator — integer minor-unit arithmetic) and snapshots the
amount into `payment_intents` (unique per tenant+booking) and onto
`bookings.total_minor/currency`; the client never supplies a total.
DRAFT → PENDING_PAYMENT happens in the same transaction as the intent.
The webhook is the ONLY confirmation path: `payment.captured` drives
PENDING_PAYMENT → PAYMENT_CONFIRMED → CONFIRMED, `payment.failed` →
FAILED, `refund.processed` (full refunds only) → REFUNDED — each leg
refused with a 200-ignored receipt (never a retry storm or an oracle)
when the intent status guard, the money guard (exact snapshot match) or
the booking lifecycle matrix says no. Every applied leg is audited on
both sides and appends its `payment.*` outbox event in the same
transaction.

## Fulfillment (B4)

The pandit's own verbs drive the booking lifecycle — the assignment is
the FACT, the lifecycle matrix is the AUTHORITY (same posture as the
payment port). Backend + customer cancel/reschedule UI this cycle; the
pandit app surface is outside the customer parity scope.

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| accept work | (no BFF — pandit app) | `POST /api/v1/pandit/assignments/{id}:confirm` (Pandit; ownership gate) → 200 CONFIRMED assignment; the booking follows CONFIRMED → ASSIGNED through the fulfillment port in the same transaction; 409 `PANDIT_BOOKING_WRONG_STATE` when the booking no longer wants the work (assignment rolled back, nothing written); 409 `PANDIT_ASSIGNMENT_WRONG_STATE` (assignment not PENDING); 404 foreign/unknown | backend only |
| complete work | (no BFF — pandit app) | `POST /api/v1/pandit/assignments/{id}:complete` → 200 COMPLETED assignment; the booking drives ASSIGNED → IN_PROGRESS → COMPLETED (both matrix hops, one fact); COMPLETED rows count into earnings; same 404/409 matrix (assignment must be CONFIRMED) | backend only |
| cancel booking | `POST /api/bookings/{id}/cancel` (`Idempotency-Key`) | `POST /api/v1/booking/reservations/{id}:cancel` (B5, unchanged) | **wired** (B4 UI: the booking detail renders the lifecycle-derived action as confirm + POST with flash + refresh; errors surface inline, never a silent no-op) |
| reschedule booking | `POST /api/bookings/{id}/reschedule` `{newStartTime}` (`Idempotency-Key`; BFF reads the current version first — races surface STALE_RESOURCE) | `POST /api/v1/booking/reservations/{id}:reschedule` (B5, unchanged) | **wired** (B4 UI: datetime-local picker; empty/malformed/past values fail closed client-side — the backend stays the authority on lifecycle state and slot availability) |

One acceptance fact, two module states: `:confirm` and `:complete`
wrap the assignment write and the booking lifecycle hops in ONE
ambient-connection transaction — any refusal (booking cancelled, a
second pandit accepting after the first, completion after
cancellation) rolls the assignment back and writes nothing. Transition
rows carry the pandit's user id as actor (human principal — contrast
the payment webhook's system actor); refusals are the ops/refund
track's input, never silently forced.

## Notification dispatch (N1)

`NotifiesCustomers::notify()` is the dispatcher entry point: HTTP
enqueues on the configured queue connection (database deployed, sync
in tests), the worker resolves its own tenant context (TEN-012, the
outbox-drain posture) and delivers the in-app inbox. Routing follows
packages/communication: **in_app always**; email gated by the
`notify.email` flag AND the notifications_email preference; sms by
notifications_sms (routed, never delivered until the SMS provider
track); quiet hours — a per-user `quiet_hours` window ({start, end}
HH:MM, cross-midnight supported) read in the `timezone` setting —
suppress service/marketing outbound legs only, transactional/security
bypass. Dedup: the spec identity `rule:event:recipient:channel` is
stored per row under a unique (tenant, dedup_key) index, so repeated
deliveries of one event collapse (idempotent insert, raced retries
never double an inbox). The settings surface gains `timezone`
(default Asia/Kolkata) and `quiet_hours` (nullable) inside the same
closed-key 422 posture.

## Booking lifecycle events (N2)

Booking verbs and both outcome ports write `booking.reservation.*`
facts to the transactional outbox **inside the same tenant transaction
as the state change** (same-tx guarantee — a crash can lose neither);
the controller no longer notifies directly. `outbox:drain` replays each
fact into the customer's inbox through `NotifiesCustomers`, passing the
outbox event id as dedup identity — a handler replay collapses into the
same row instead of doubling it.

| Fact | Producer (same tx) | Payload (schema v1) | Inbox copy |
|---|---|---|---|
| `booking.reservation.created` | `POST …/reservations` (store; added to the manifest) | `booking_id, user_id, service_sku, scheduled_at` | "Booking created" |
| `booking.reservation.cancelled` | `{id}:cancel` | `booking_id, user_id, from, to` | "Booking cancelled" |
| `booking.reservation.rescheduled` | `{id}:reschedule` | `booking_id, user_id, old_start, new_start` | "Booking rescheduled" |
| `booking.reservation.transitioned` | payment port (webhook legs) + fulfillment port (pandit assignment legs), reason + `actor_id` carried (`system:payment-webhook` vs the pandit's user id — K-029) | `booking_id, user_id, from, to, reason, actor_id` | mapped per `to`: CONFIRMED → Payment received, ASSIGNED → Pandit assigned, COMPLETED → Booking completed, FAILED → Payment failed, REFUNDED → Payment refunded; any other hop is a fact without an inbox row (never a wrong message) |

A malformed payload fails the claim loudly (attempts grow, `last_error`
records it) — a customer-facing fact is never silently dropped. The
declared-but-unproduced `booking.review.*` events are unchanged. `:confirm`
(DRAFT → PENDING_PAYMENT) still produces no lifecycle fact — payment
state carries that leg, and B3's surface is untouched.

SSE: `/api/events` gains its **first producer** — while a stream is
open, a per-channel notification tail polls the caller's backend
notifications (one poll per channel regardless of open tabs, seeded on
retain so history stays the REST list's job) and publishes
`notification.created` live events to the bus; streams heartbeat every
25s. Wired deployments authenticate the stream with the session cookie
when no bearer is sent (EventSource cannot set headers — the F2b
pattern), and that cookie token is the same bearer the tail reads
with. The bus stays single-instance (memory) behind the EventBus port —
a shared broker is the documented swap for multi-instance.

## Email channel (N3)

Email executes inside `DispatchCustomerNotification` after the in-app
leg: the N1 matrix decides (flag + preference + quiet hours), the job
obeys — provider-agnostic through Laravel's mailer config (`MAIL_*`;
the stock `log` default is the safety net — no compose service added).
Subjects render per kind through `NotificationEmailTemplates` (en, kind
unknown → generic subject, locale carried from settings for translated
copy later); the body renders the notification's own title/body through
an escaping Blade template — producer copy can never inject markup.

Idempotency is the `notification_deliveries` claim row (tenant_shared,
unique `(tenant_id, dedup_key)` = `kind:eventId:userId:email`): `pending`
is written before the hand-off, `sent` after it — a retry after a
partial run resumes its own pending row instead of re-sending, and a
sent row collapses replays. No contactable address (blank, or the
synthetic `{phone}@phone.local` OTP placeholder — the `maskEmail`
posture, inlined because the job must not import the HTTP layer) writes
`suppressed` with the reason, never a guessed recipient. A null event id
(one-shot fact) sends without a claim row — documented: unkeyed legs
cannot dedup. Send exceptions propagate into the job's backoff; the
attempts cap leaves the row `pending` for ops (`failed_jobs` carries the
cause).

## Support staff console (S4)

The staff side of support: a tenant-wide queue over the customers module's
rows, capability-gated (`customers:support:view` / `customers:support:manage`),
reachable from `/api/v1` (bearer) and from the Blade console's shared web
actions (session + CSRF → redirect + coded flash — doc 06 §7). Queue and
thread reads run over the session cookie; customer-side rows above are
unchanged (CustomerSupportTest stays the pin).

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| staff queue | (no BFF endpoint yet) | `GET /api/v1/customers/support/staff/tickets?status=OPEN/CLOSED` → 200 cursor page with masked customer identity | backend only (S4 — the Blade console consumes it directly) |
| staff thread | (no BFF endpoint yet) | `GET /api/v1/customers/support/staff/tickets/{id}` → 200 messages with `by: customer/staff` authorship + author names; foreign id → 404 `not_found` | backend only (S4) |
| staff reply | (no BFF endpoint yet) | `POST /api/v1/customers/support/staff/tickets/{id}/messages` → 201, re-opens CLOSED (same documented semantics as the customer's own reply), audited `via: staff` | backend only (S4) |
| staff close | (no BFF endpoint yet) | `POST /api/v1/customers/support/staff/tickets/{id}:close` → 200; repeat → 409 `state_forbidden` (same code as the customer side) | backend only (S4) |
| console reply/close forms | — | web `POST /customers/support/tickets/{id}/messages` and `…/:close` share the staff actions: session + CSRF → redirect back with `flash`/`flash_error`, API envelopes untouched | web transport over the same actions (doc 06 §7.1-7.2) |