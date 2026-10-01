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

Codes differ by registry (BFF domain codes vs kernel snake codes) — clients
MUST branch on documented per-surface codes, never assume string equality
across surfaces. HTTP statuses are identical everywhere: that is the
cross-surface contract (validation: 422 both sides after the F1 alignment).
