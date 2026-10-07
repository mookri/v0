# Customer Contract Parity — Next.js BFF ↔ Laravel modules

**Backend status:** COMPLETE (B1–B8 + P7 + P8a, suite 490 passed) · Frontend wiring (F1–F2) pending.

Both surfaces implement the same flow with their platform's envelope and
code registry. This table is the contract: either side changing a row
updates this doc and both test suites in the same change.

## Flow parity (B2: auth)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| OTP request | `POST /api/auth/login {identityId, proof}` → `{token, sessionId, expiresAt}` (single call, memory identity) | `POST /api/v1/customers/auth/otp {tenant_id, purpose, identity}` → 202 `{challenge_id, expires_in}` | **diverged by design** — BFF keeps its shape; backend splits challenge + redeem (rate control). BFF maps: otp call, then redeem, then shapes `{token, sessionId, expiresAt}` from `{token, actor.session_id, expires_at}` |
| signup | (no BFF endpoint yet) | `POST /api/v1/customers/auth/signup` → 202 `{challenge_id}` | backend only until F2 |
| signup verify | (no BFF endpoint yet) | `POST /api/v1/customers/auth/verify` → 201 `{token, expires_at, tenant_id, actor}` | backend only until F2 |
| login redeem | (folded into BFF login, optional `totpCode` passthrough) | `POST /api/v1/customers/auth/login` → 200 `{token, expires_at, tenant_id, actor}` (+ optional `totp_code` when the account has MFA enrolled — absent/wrong collapses to 401) | backend only until F2 |
| logout | `POST /api/auth/logout` → `{signedOut:true}` | `POST /api/v1/customers/auth/logout` → 200 `{signed_out:true}` | equal semantics (both best-effort until jti store) |
| profile read | `GET /api/profile` → `{profile}` | `GET /api/v1/customers/profile` → 200 masked profile | **wired behind flag** |
| profile update | `PATCH /api/profile` → `{profile}` | `PATCH /api/v1/customers/profile` → 200 (strict fields) | **wired behind flag** |
| deactivate/reactivate | `POST /api/profile:deactivate` (fresh OTP) / `POST /api/profile:reactivate` (public OTP) | `POST /api/v1/customers/profile:deactivate` (authed + fresh OTP) / `:reactivate` (**public** OTP — suspended members cannot pass the tenant gate) → 200 | **wired behind flag** |
| account deletion | `POST /api/profile:delete` (fresh OTP) → 200 `{id, status, deleted_at}` | `POST /api/v1/customers/profile:delete` → 200 (soft delete: sets deleted_at, suspends membership, audit `customers.account.deleted`) | **wired behind flag** |
| verifications | `GET /api/verifications` → `{verifications[]}` / `POST /api/verifications` → 202 `{challenge_id}` / `POST /api/verifications/confirm {id, code}` → 200 | `GET /api/v1/customers/verifications`, `POST …` → 202, `POST …/{id}:confirm` → 200 | **wired behind flag** |
| addresses | web `/user/*` mock | `GET/POST /api/v1/customers/addresses`, `PATCH/DELETE …/{id}`, `POST …/{id}:default` (cap 20, default promotion) | backend only until F2 |
| saved | `GET /api/saved` → mock cards | `GET/POST /api/v1/customers/saved` (opaque ULID refs, enriched by readers — deliberate: bookmarks, not FKs), `DELETE …/{id}` | shape differs by design (backend stores refs; BFF enriches) |
| notifications | `GET /api/notifications` → `items:[]` | `GET /api/v1/customers/notifications` (cursor), `POST …/{id}:read`, `POST …:read-all` (reports flip count) | backend real; producers land B5+ |
| settings | `GET /api/settings` → `{sections, values}` / `PATCH /api/settings` → `{sections, values}` | `GET/PATCH /api/v1/customers/settings` (closed key set over defaults; BFF maps key-values → sections in F2) | **wired behind flag** |
| bookings list/detail | `GET /api/bookings` → cards, `GET /api/bookings/:id` → details+actions | `GET /api/v1/booking/reservations` (cursor + `?section=` upcoming/active/completed/cancelled), `GET …/{id}` (+ live `actions`) | backend real; section mapping documented here (upcoming = pre-service states, active = ASSIGNED/IN_PROGRESS, completed = COMPLETED, cancelled = terminal rest) |
| bookings create | `POST /api/bookings` → 201 `{booking}` | `POST /api/v1/booking/reservations` → 201 (DRAFT, idempotent on Idempotency-Key, overlap guard, SKU validity via Catalog port) | **wired behind flag** |
| cancel | `POST …/cancel` → 409 `BOOKING_CANNOT_CANCEL` | `POST /api/v1/booking/reservations/{id}:cancel` → 200 (idempotent on CANCELLED), else 409 `state_forbidden` | BFF maps 409 → `BOOKING_CANNOT_CANCEL`; idempotent replay is new (safe retries) |
| reschedule | `POST …/reschedule {newStartTime}` → 409 | `POST …/{id}:reschedule {new_start_time, version}` → 200; stale → 409 `version_conflict`, overlap → 409 `conflict`, off-state → 409 `state_forbidden` | version + overlap are new (BFF sends `version` from detail read in F2) |
| history | `GET /api/bookings/:bookingId/history` → `{transitions[]}` | `GET …/{id}/history` (append-only transitions) | **wired behind flag** |
| reviews | web `/user/*` mock | `POST …/{id}/reviews` (COMPLETED only, one per booking), `GET /api/v1/booking/reviews`, `DELETE …/{id}` | backend only until F2 |
| discover | `GET /api/discover` → always-empty feed | `GET /api/v1/customers/discover` → `{recommended, popular, recent}` (SQL-only: recency + 30d booking rank + novelty exclusion; no ML) | backend real; kernel envelope wraps the feed object (BFF unwraps in F2) |
| activity | `/` recentActivity mock strings | `GET /api/v1/customers/activity` (cursor + `?kind=`, caller-scoped, dedicated read model — never audit rows) | backend only until F2 |
| referrals | — | `GET /api/v1/customers/referrals` (code + count + masked list; attribution only, no rewards) | backend only until F2 |
| support | `/support`, `/help` (validation only) | `POST /api/v1/customers/support/tickets` (cap 10 open), `GET …` (cursor), `GET …/{id}` (thread), `POST …/{id}/messages` (re-opens CLOSED), `POST …/{id}:close` | backend real; staff console is a separate surface (S4, below) |
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
| pandit availability | `GET /api/pandits/:panditId/availability?start_date&end_date` → `{data: AvailabilitySlot[], meta}` | `GET /api/v1/pandit/profiles/{id}/availability` → 200 kernel envelope with slots (404 `PANDIT_NOT_FOUND` for unknown/unverified/suspended) | **wired behind flag** (404 → NOT_FOUND) |

### Pandit code correspondence

| Situation | Next.js code | Laravel code |
|---|---|---|
| unknown/unverified/suspended pandit | `NOT_FOUND` (404) | `PANDIT_NOT_FOUND` (404) |
| invalid query params (date format, rating range) | `VALIDATION_ERROR` (422) | `validation_failed` (422 + `details.fields`) |
| guest / unauthenticated caller | `UNAUTHENTICATED` (401) | `unauthenticated` / `tenant_not_in_token` (401) — kernel binds tenant only for a principal |

## Temple directory + dashboard (P11)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| temple directory (customer) | `GET /api/temples` → `{data: TempleProfile[], meta: {page}}` | `GET /api/v1/temple/profiles` → 200 kernel envelope with items + cursor page (VERIFIED + ACTIVE only) | **wired behind flag** (BFF unwraps kernel envelope, passes query params — `?cursor=` honored end-to-end since S12; the API previously dropped it and replayed page 1) |
| temple detail (customer) | `GET /api/temples/:templeId` → profile | `GET /api/v1/temple/profiles/{id}` → 200 kernel envelope with profile (404 `TEMPLE_NOT_FOUND` for unknown/unverified/suspended id) | **wired behind flag** (404 `TEMPLE_NOT_FOUND` → `NOT_FOUND`) |
| temple directory (partner) | `GET /api/temples` → `{data: TempleProfile[], meta: {page}}` | `GET /api/v1/temple/profiles` → same backend as the customer directory | **wired behind flag** (`PARTNER_BACKEND=laravel`) |
| temple detail (partner) | `GET /api/temples/:templeId` → profile | `GET /api/v1/temple/profiles/{id}` → same backend as the customer detail | **wired behind flag** (404 `TEMPLE_NOT_FOUND` → `NOT_FOUND`) |
| temple dashboard (partner) | `GET /api/temples/:templeId/dashboard/:section` → section payload | `GET /api/v1/temple/partner/temples/{id}/dashboard/{section}` — overview, pujas, bookings, calendar, analytics, settings (overview + settings resolve the temple row: unknown → 404 `not_found`; the other sections scope-query and answer 200 with empty data) | **wired behind flag** (`not_found` → `NOT_FOUND`, `permission_missing` → `FORBIDDEN`, `validation_failed` → `VALIDATION_ERROR`) |
| temple pujas (public) | (no BFF endpoint yet) | `GET /api/v1/temple/profiles/{templeId}/pujas?limit` → 200 `{data: assignments, meta: {request_id, page}}`; 404 `TEMPLE_NOT_FOUND` | backend only (P11g) |
| temple availability (public) | (no BFF endpoint yet) | `GET /api/v1/temple/profiles/{templeId}/availability?start_date&end_date` → 200 `{data: slots, meta: {request_id}}`; 404 `TEMPLE_NOT_FOUND`, 422 `validation_failed` | backend only (P11g) |
| temple puja show (partner) | (no BFF endpoint yet) | `GET /api/v1/temple/partner/temples/{templeId}/pujas/{pujaId}` → 200 assignment; 404 `TEMPLE_PUJA_NOT_FOUND` | backend only (P11g) |

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
| unknown/foreign puja assignment (partner show) | — (no BFF surface yet) | `TEMPLE_PUJA_NOT_FOUND` (404) |
| unknown temple on dashboard (overview, settings) | `NOT_FOUND` (404) | `not_found` (404 — kernel rendering of the scoped lookup) |
| invalid query params (limit, start_date/end_date) | `VALIDATION_ERROR` (422) | `validation_failed` (422 + `details.fields`) |
| guest / unauthenticated caller | `UNAUTHENTICATED` (401) | `unauthenticated` / `tenant_not_in_token` (401) — kernel binds tenant only for a principal |

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