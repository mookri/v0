# Laravel API Contract (BFF → `api.navdhya.com`)

Companion to `auth/p0-contract.md`. That document defines *semantics*
(state machine, invariants I1–I18, gate scenarios C1–C4). This one
freezes the *interface* the BFF actually calls today, so the Laravel
team can implement/verify against exactly what the code sends and
expects. Generated from the call sites on branches
`auth/p0-* → auth/p1-session-state → api/consistency-contracts →
api/remaining-hardening`.

**Authority rule (p0-contract §0): Laravel is the sole security
authority.** The BFF validates transport shape, throttles, and forwards;
it never judges proofs, attempts, expiry, or assurance.

---

## 1. Transport conventions

| Aspect | Contract |
|---|---|
| Envelope (success) | `{ data, meta }` — BFF unwraps to `data` |
| Envelope (failure) | `{ error: { code, requestId } }` — BFF maps `code` (§4) |
| `requestId` | BFF sends `X-Request-Id` on **every** call; backend must echo it as `error.requestId` so logs correlate (p0-contract §8) |
| Tenant | BFF sends `X-Tenant-Id` (from `CUSTOMER_TENANT_ID`) **and** `tenant_id` in auth POST bodies (as coded today) |
| Session token | BFF sends `Authorization: Bearer <token>` when a session exists; pre-auth calls send `null` |
| Wire casing | snake_case on the backend side; BFF re-emits camelCase to the frontend |
| Timeouts (BFF-enforced) | `fast` = 2000 ms (reads, OTP issue), `normal` = 8000 ms (redeems, mutations) — no retry on auth POSTs |
| No retries | BFF never retries backend calls it makes for auth/session flows — the backend must handle its own transient faults without duplicate side effects |

## 2. Endpoint contract

### 2.1 Challenge lifecycle (backend-authoritative)

| BFF route | Backend call | Request fields (body) | Response `data` consumed | Required backend semantics |
|---|---|---|---|---|
| POST `/api/auth/otp` | `POST /customers/auth/otp` | `tenant_id`, `identity` (E.164), `purpose` | `{ challenge_id, expires_in }` | **Enforced (I12, C2/G1 — M2):** one active challenge per (identity, purpose) — `issue()` stamps prior active rows `superseded_at` inside the issuing transaction; superseded ids verify as uniform 401 (no oracle), rows retained for the 5/hour cap → `rate_limited`. |
| POST `/api/auth/signup` | `POST /customers/auth/signup` | `tenant_id`, `phone` (`/^\+?[0-9]{6,15}$/`), `name`, `email?`, `locale?`, `referral_code?` | `{ challenge_id, expires_in }` | Same as otp (M2 enforced). Signup intent travels in the challenge `payload` column. |
| POST `/api/auth/verify` | `POST /customers/auth/verify` | `tenant_id`, `challenge_id`, `code` | **Session** (§2.3), HTTP **201** | **Enforced (C1/C4 — M3):** redemption runs in one transaction with `lockForUpdate()` on the challenge row — verify-and-consume atomic, attempts bump exactly. Attempts: **3**; expiry/exhaustion → row deleted. 409 `conflict` if the phone already exists. |
| POST `/api/auth/login` | `POST /customers/auth/login` | `tenant_id`, `challenge_id`, `code` (= canonical `proof`), `totp_code?` | **Session** (§2.3) | Same redeem semantics as verify (M3 enforced). `code` key on the wire is the *proof value* (BFF maps `proof\|code` → `code`); optional `totp_code` for MFA-enabled accounts — required when `mfa_enabled`, else uniform 401. 403 `membership_inactive` for inactive members. |

BFF behavior on redeem: single call, no fan-out. Challenge creation and
redemption are separate calls by design — the frontend holds
`challengeId` between them (in-memory; sessionStorage removed).

### 2.2 MFA (`/identity/mfa/*` — AAL requirements in p0-contract §5)

| BFF route | Backend call | Body fields sent | Backend truth |
|---|---|---|---|
| GET `/api/mfa/status` | `GET /identity/mfa` | — | `{enabled, enrolled_at}` for the session's identity. |
| POST `/api/mfa/enroll` | `POST /identity/mfa/enroll` | `tenant_id`, `type` (`"totp"`) | **AAL1 only** — generates the encrypted secret with `mfa_enabled=false`; no step-up required (the AAL2 claim in the earlier revision was aspirational). Returns `otpauth_url`. |
| POST `/api/mfa/confirm` | `POST /identity/mfa/confirm` | `tenant_id`, `code` | `code` proves the pending enroll secret (AAL1 + enroll proof) → `mfa_enabled=true` + 10 single-use recovery codes (shown once). |
| POST `/api/mfa/verify` | `POST /identity/mfa/verify` | `tenant_id`, `code` | Step-up primitive: TOTP **or** recovery code → writes `MfaStepUp{user_id, verified_at, ip}` (read by `stepup:2` for 15 min). **Rotates the session (M5):** the presented same-actor predecessor is revoked and a fresh token is returned as `{token, expires_at, actor}` (login-envelope shape) — the BFF adopts it into the cookie. |
| POST `/api/mfa/revoke` | `POST /identity/mfa/revoke` | `tenant_id`, `code` | **Live TOTP only** — recovery codes rejected. MFA disabled, secret wiped. |
| POST `/api/mfa/recovery-codes` | `POST /identity/mfa/recovery-codes` | `tenant_id`, `code` | **Live TOTP only** → all prior hashes replaced (rotation of codes ✅). |

Attempt/lock for MFA verify-class operations: **backend-enforced since
2026-10-09 (M4)** — per-user `mfa_failed_attempts`, 5 failures →
`mfa_locked_until` +900 s → **429 + `Retry-After`** (transition atomic
under row lock; a correct code while locked still fails closed; success
resets). `mfa_last_used_step` rejects TOTP replay inside the window
(used-code state persisted). Route `throttle:10,1` remains beneath, and
the BFF keeps its tighter `5/900`. All code failures collapse to one
uniform 401 (no oracle).

### 2.3 Sessions & profile state

| BFF route | Backend call | Body fields sent | Notes |
|---|---|---|---|
| POST `/api/auth/logout` | `POST /customers/auth/logout` | `tenant_id` | Idempotent: repeat logout = success. BFF clears cookie **regardless** of outcome (I16). Backend **revokes the presented session** (`actor_sessions.revoked_at`; the verifier fails closed — M5), so the bearer dies at logout. |
| GET `/api/sessions` | `GET /identity/sessions` | — | Session list for the actor. |
| POST `/api/sessions/revoke-others` | `POST /identity/sessions:revoke-others` | `tenant_id` | Naturally idempotent. **Enforced (I15/M5):** every *other* session of the actor is revoked (web rows + actor-session jtis), while the presented session — the verified actor `AuthenticateAny` stamps on the request — survives on **both** cookie and bearer paths (F-SMOKE3 fix); rotations separately revoke the predecessor on every mint. |
| GET `/api/saved` | `GET /customers/saved` | — | Array **or** `{ items: [...] }` accepted by BFF. Items carry `ref_id`/`refType` style keys. |
| POST `/api/saved` | `POST /customers/saved` | `ref_type` (`puja`\|`content`\|`service`), `ref_id` (26-char Crockford ULID) | Only these keys are ever sent — the BFF schema mirrors `SavedItemController::store` (F-SMOKE2 fix). No `title`: the store has no title column, so forwarding one would promise a write that can never round-trip. |
| GET `/api/settings` | `GET /customers/settings` | — | Response carries `{ settings: Record<string, unknown> }`. |
| PATCH `/api/settings` | `PATCH /customers/settings` | flat top-level keys (`locale`, `notifications_email`, `notifications_sms`, `marketing_opt_in`) | **Flat wire** — the backend diffs top-level request keys against `CustomerSetting::KEYS`, so a `settings` wrapper reads as an unknown key (F-SMOKE1 fix). The BFF closed key set mirrors that key set one for one: unknown keys are rejected 422 at the BFF and never arrive. |
| GET `/api/verifications` | `GET /customers/verifications` | — | Verification records. |
| POST `/api/verifications` | `POST /customers/verifications` | `channel` (`phone`\|`email`), `value` (phone normalized E.164) | **Wire keys are `channel`/`value`** (backend `$request->validate`); the previous `{type, contact}` shape was a hard mismatch (M1, fixed). Success **202** `{challenge_id, expires_in}` — same OTP engine as `/customers/auth/*` (600 s, 3 attempts, 5/hour). 409 if the current value is already verified. |
| POST `/api/verifications/confirm` | `POST /customers/verifications/{id}:confirm` | `code` | `{id}` charset is `[A-Za-z0-9_-]{8,128}` (BFF-validated before path interpolation). Wrong-code lock backend-authoritative. |
| POST `/api/notifications/read-all` | `POST /customers/notifications:read-all` | `{}` | Naturally idempotent. |

### 2.4 Session object (returned by login/verify)

```ts
interface LaravelSession {
  token: string;          // opaque session token → BFF sets the cookie
  expires_at: string;     // ISO — drives cookie Max-Age
  tenant_id: string;
  actor: { user_id: string; session_id: string };
}
```

BFF response to the browser: `{ sessionId, expiresAt, receivedForm }` +
`Set-Cookie` (see p0-contract §7 for cookie policy). **Rotation (M5):**
each redeem mints a fresh `sess_<ULID>` and revokes the presented
same-actor predecessor (`ActorSessionRotation`); MFA completion rotates
too and the BFF adopts the returned token into the cookie; the cookie's
`Max-Age` tracks `expires_at`.

### 2.5 Challenge object

```ts
interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;     // seconds
}
```

BFF response: `{ challengeId, expiresIn, ... }` (202), plus idempotent
replay of prior successful responses when `Idempotency-Key` repeats.

## 3. Gate checklist (split by what today's backend actually does)

### 3.1 Current-system gate — verifiable now, no backend change

- **C3** (BFF-side, automated) — idempotent double-submit → one effect
- Verification start uses `{channel, value}`; `{type, contact}` rejected (M1)
- OTP `expires_in` = 600 s; 3 failed redeem attempts exhaust the challenge
- Expired/unknown challenge → definitive uniform 401 (no proof oracle)
- Logout clears the cookie unconditionally; the backend revokes the presented session (M5, E2E S9/G4)
- Cookie-only GET / bearer-only write semantics hold (`SessionContextForReads`)
- MFA route throttle enforced (backend `10,1`, BFF `5/900`)
- Recovery codes single-use; live-TOTP-only for revoke/regenerate
- Step-up proof expires after 15 min (`RequiresAcr::FRESH_MINUTES`)

### 3.2 Security-improvement gate — landed 2026-10-09 (`backend-security-gaps.md`, all green)

- **C1** — concurrent valid submits on one challenge → exactly one success *(M3 ✅)*
- **C2** — concurrent resends → exactly one active challenge *(M2 ✅)*
- **C4** — parallel wrong codes → exact attempt counting, one exhaustion *(M3 ✅)*
- Wrong OTP ×3 → challenge deleted; no time-based lock exists at all *(unchanged)*
- MFA attempt lockout + TOTP used-code replay rejection *(M4 ✅ — 5/900 s + used-step state)*
- Session rotation on login / MFA / privilege elevation invalidates the predecessor *(M5 ✅)*
- Logout (and `revoke-others`) invalidates the credential *(M5 ✅ — `actor_sessions`)*
- At most one active challenge per (identity, purpose); resend invalidates prior *(M2 ✅)*

The BFF must never emit behavior implying a 3.2 item holds (no fake
`LOCKED`, no `attemptsRemaining` sourced from invented backend state,
no "session revoked" claims).

## 4. Error-code map (BFF side)

Backend `error.code` → BFF envelope code (`DEFAULT_BACKEND_CODE_MAP`;
unmapped codes become `INTERNAL_ERROR`, HTTP 500 — **so new backend
codes must be added to the map or deliberately left opaque**):

| Backend code | BFF code | Typical status |
|---|---|---|
| `unauthenticated`, `membership_inactive` | `UNAUTHENTICATED` | 401 |
| `tenant_suspended`, `permission_missing`, `authorization_denied` | `FORBIDDEN` | 403 |
| `validation_failed` | `VALIDATION_ERROR` | 422 |
| `not_found` | `NOT_FOUND` | 404 |
| `conflict`, `state_forbidden` | `CONFLICT` | 409 |
| `version_conflict` | `STALE_RESOURCE` | 409 |
| `rate_limited` | `RATE_LIMITED` | 429 |
| `step_up_required` | `FORBIDDEN` | 403 |
| `service_unavailable` | `DEPENDENCY_UNAVAILABLE` | 503 |
| `internal_error` (and anything unmapped) | `INTERNAL_ERROR` | 500 |

Statuses produced by the BFF itself (cap 413, malformed 400, schema
422, throttle 429 + `Retry-After`, idempotency conflict 409, disabled
401) are listed in `p0-contract.md` §8; the full per-route control
matrix is in `route-hardening-matrix.md`.

## 5. Environment & infrastructure boundary

| Variable | Role |
|---|---|
| `CUSTOMER_BACKEND=laravel` + `NAVDHYA_API_BASE` | both required, else BFF serves 401 on wired routes (mocks stay default). **Base is the bare origin only** (e.g. `https://api.navdhya.com`) — `laravel-client.ts` appends `/api/v1` itself; putting `/api/v1` in the base double-prefixes and 404s (integration note M10). |
| `CUSTOMER_TENANT_ID` | sent as `X-Tenant-Id` + `tenant_id` (per-request tenant resolution is future work) |
| `SESSION_COOKIE_SECURE=0\|1` | cookie policy override (p0-contract §7); default follows `NODE_ENV` |
| `LEGACY_HEADER_AUTH=1` | opt-in header-only auth during migration; cookie always wins |
| `CSRF_SECRET` | explicit CSRF gates (ads decision, revalidate); warning in dev |
| `next build` | `NEXT_STANDALONE=0` for local/Windows builds (standalone output EPERM on symlinks); engines pinned `node 20.x`, `pnpm@9.15.4` |

**Scaling caveat (must-fix before multi-instance):** the P0 rate limiter
and idempotency store are in-process. With N instances behind a load
breaker, effective limits are `limit × instances` and idempotency claims
are per-instance. The `RateLimiter` interface
(`application/auth-throttle.ts`) exists precisely so a Redis/Valkey
adapter drops in for Phase 3 without touching routes. Until then, keep
auth traffic pinned to a single instance or accept the relaxed bound
explicitly.

## 6. Deferred decisions (recorded, not silently implemented)

1. **Login CSRF** — an attacker-orchestrated redeem can plant an
   attacker-owned session (OTP goes to the attacker's phone). Needs a
   product decision: `Sec-Fetch-Site` enforcement on pre-auth routes
   vs. per-login nonce vs. accept (JSON content-type already forces
   CORS preflight for browser attackers).
2. **`code` alias removal** — `proof` is canonical; `code` accepted one
   release with `receivedForm` + requestId telemetry (§2 migration).
   Removal milestone is verifiable from logs.
3. **Header-auth removal** — raw `Authorization` readers are gone from
   the BFF; `LEGACY_HEADER_AUTH` window can close once any external
   API consumers are confirmed migrated.
4. **`challengeId + proof` fan-out removal is done** — `/auth/login`
   makes a single redeem call; documented here so the backend does not
   keep a legacy two-call path alive.
