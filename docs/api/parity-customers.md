# Customer Contract Parity — Next.js BFF ↔ Laravel modules

Both surfaces implement the same flow with their platform's envelope and
code registry. This table is the contract: either side changing a row
updates this doc and both test suites in the same change.

## Flow parity (B2: auth)

| Step | Next.js BFF | Laravel module | Match |
|---|---|---|---|
| OTP request | `POST /api/auth/login {identityId, proof}` → `{token, sessionId, expiresAt}` (single call, memory identity) | `POST /api/v1/customers/auth/otp {tenant_id, purpose, identity}` → 202 `{challenge_id, expires_in}` | **diverged by design** — BFF keeps its shape; backend splits challenge + redeem (rate control). BFF maps: otp call, then redeem, then shapes `{token, sessionId, expiresAt}` from `{token, actor.session_id, expires_at}` |
| signup | (no BFF endpoint yet) | `POST /api/v1/customers/auth/signup` → 202 `{challenge_id}` | backend only until F2 |
| signup verify | (no BFF endpoint yet) | `POST /api/v1/customers/auth/verify` → 201 `{token, expires_at, tenant_id, actor}` | backend only until F2 |
| login redeem | (folded into BFF login) | `POST /api/v1/customers/auth/login` → 200 `{token, expires_at, tenant_id, actor}` | backend only until F2 |
| logout | `POST /api/auth/logout` → `{signedOut:true}` | `POST /api/v1/customers/auth/logout` → 200 `{signed_out:true}` | equal semantics (both best-effort until jti store) |
| profile read | `GET /api/profile` → `{profile:null}` (stub) | `GET /api/v1/customers/profile` → 200 masked profile | backend real; BFF adopts shape in F2 |
| profile update | — | `PATCH /api/v1/customers/profile` → 200 (strict fields) | backend only until F2 |
| deactivate/reactivate | — | `POST /api/v1/customers/profile:deactivate` (authed + fresh OTP) / `:reactivate` (**public** OTP — suspended members cannot pass the tenant gate) → 200; double-deactivate meets the 403 gate by design | backend only until F2 |
| verifications | — | `GET /api/v1/customers/verifications`, `POST …` → 202, `POST …/{id}:confirm` → 200 | backend only until F2 |
| addresses | web `/user/*` mock | `GET/POST /api/v1/customers/addresses`, `PATCH/DELETE …/{id}`, `POST …/{id}:default` (cap 20, default promotion) | backend only until F2 |
| saved | `GET /api/saved` → mock cards | `GET/POST /api/v1/customers/saved` (opaque ULID refs, enriched by readers — deliberate: bookmarks, not FKs), `DELETE …/{id}` | shape differs by design (backend stores refs; BFF enriches) |
| notifications | `GET /api/notifications` → `items:[]` | `GET /api/v1/customers/notifications` (cursor), `POST …/{id}:read`, `POST …:read-all` (reports flip count) | backend real; producers land B5+ |
| settings | `GET /api/settings` → static sections | `GET/PATCH /api/v1/customers/settings` (closed key set over defaults; BFF maps key-values → sections in F2) | shape differs by design |
| bookings list/detail | `GET /api/bookings` → cards, `GET /api/bookings/:id` → details+actions | `GET /api/v1/booking/reservations` (cursor + `?section=` upcoming/active/completed/cancelled), `GET …/{id}` (+ live `actions`) | backend real; section mapping documented here (upcoming = pre-service states, active = ASSIGNED/IN_PROGRESS, completed = COMPLETED, cancelled = terminal rest) |
| cancel | `POST …/cancel` → 409 `BOOKING_CANNOT_CANCEL` | `POST /api/v1/booking/reservations/{id}:cancel` → 200 (idempotent on CANCELLED), else 409 `state_forbidden` | BFF maps 409 → `BOOKING_CANNOT_CANCEL`; idempotent replay is new (safe retries) |
| reschedule | `POST …/reschedule {newStartTime}` → 409 | `POST …/{id}:reschedule {new_start_time, version}` → 200; stale → 409 `version_conflict`, overlap → 409 `conflict`, off-state → 409 `state_forbidden` | version + overlap are new (BFF sends `version` from detail read in F2) |
| history | — | `GET …/{id}/history` (append-only transitions) | backend only until F2 |
| reviews | web `/user/*` mock | `POST …/{id}/reviews` (COMPLETED only, one per booking), `GET /api/v1/booking/reviews`, `DELETE …/{id}` | backend only until F2 |
| discover | `GET /api/discover` → always-empty feed | `GET /api/v1/customers/discover` → `{recommended, popular, recent}` (SQL-only: recency + 30d booking rank + novelty exclusion; no ML) | backend real; kernel envelope wraps the feed object (BFF unwraps in F2) |
| activity | `/` recentActivity mock strings | `GET /api/v1/customers/activity` (cursor + `?kind=`, caller-scoped, dedicated read model — never audit rows) | backend only until F2 |
| support | `/support`, `/help` (validation only) | `POST /api/v1/customers/support/tickets` (cap 10 open), `GET …` (cursor), `GET …/{id}` (thread), `POST …/{id}/messages` (re-opens CLOSED), `POST …/{id}:close` | backend real; staff console is a separate surface |

Profile/saved/notifications/settings/bookings rows land with B3–B5.

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

Codes differ by registry (BFF domain codes vs kernel snake codes) — clients
MUST branch on documented per-surface codes, never assume string equality
across surfaces. HTTP statuses are identical everywhere: that is the
cross-surface contract (validation: 422 both sides after the F1 alignment).
