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

Codes differ by registry (BFF domain codes vs kernel snake codes) — clients
MUST branch on documented per-surface codes, never assume string equality
across surfaces. HTTP statuses are identical everywhere: that is the
cross-surface contract (validation: 422 both sides after the F1 alignment).
