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
| POST `/api/auth/otp` | `POST /customers/auth/otp` | `tenant_id`, `identity` (E.164), `purpose`, `channel?` | `{ challenge_id, expires_in }` | **One active challenge per (identity, purpose)** — issuing invalidates the prior (I12, C2). Concurrent duplicate submits → exactly one challenge (C2). |
| POST `/api/auth/signup` | `POST /customers/auth/signup` | `tenant_id`, `identity` (E.164), `name`, `channel?` | `{ challenge_id, expires_in }` | Same single-active-challenge rule. Signup + challenge creation atomic enough that a failed issue leaves no half-state (C1). |
| POST `/api/auth/verify` | `POST /customers/auth/verify` | `tenant_id`, `challenge_id`, `code` | **Session** (§2.3) | **Atomic verify-and-consume**: concurrent valid submits (same challenge) → exactly one success, one definitive failure (C1). Attempt counting exact under concurrency (C4). Expired challenge → definitive failure (I11). |
| POST `/api/auth/login` | `POST /customers/auth/login` | `tenant_id`, `challenge_id`, `code` (= canonical `proof`), `totp_code?` | **Session** (§2.3) | Same atomic verify-and-consume. `code` key on the wire is the *proof value* (BFF maps `proof\|code` → `code`); optional `totp_code` for step-up-on-login. |

BFF behavior on redeem: single call, no fan-out. Challenge creation and
redemption are separate calls by design — the frontend holds
`challengeId` between them (in-memory; sessionStorage removed).

### 2.2 MFA (`/identity/mfa/*` — AAL requirements in p0-contract §5)

| BFF route | Backend call | Body fields sent | Required backend semantics |
|---|---|---|---|
| GET `/api/mfa/status` | `GET /identity/mfa` | — | Enrollment state for the session's identity. |
| POST `/api/mfa/enroll` | `POST /identity/mfa/enroll` | `tenant_id`, `type` (`"totp"`) | **AAL2**: a fresh step-up must already exist; backend judges freshness (BFF cannot, I18). |
| POST `/api/mfa/confirm` | `POST /identity/mfa/confirm` | `tenant_id`, `code` | `code` proves the pending enroll secret (AAL1 + enroll proof). |
| POST `/api/mfa/verify` | `POST /identity/mfa/verify` | `tenant_id`, `code` | Step-up primitive. Success **produces** AAL2; rotation trigger (§6): rotate the session token on completion. |
| POST `/api/mfa/revoke` | `POST /identity/mfa/revoke` | `tenant_id`, `code` | **AAL2 — live TOTP only.** Replay of a consumed code → definitive failure (C-gate: "MFA replay reject"). |
| POST `/api/mfa/recovery-codes` | `POST /identity/mfa/recovery-codes` | `tenant_id`, `code` | **AAL2 — live TOTP only.** Regeneration invalidates prior recovery codes. |

Attempt/lock for all MFA verify-class operations: wrong codes count to
lock atomically (×5 → locked, post-lock rejects are immediate), backend
authoritative (I5–I10).

### 2.3 Sessions & profile state

| BFF route | Backend call | Body fields sent | Notes |
|---|---|---|---|
| POST `/api/auth/logout` | `POST /customers/auth/logout` | `tenant_id` | Idempotent: repeat logout = success. BFF clears cookie **regardless** of outcome (I16); backend failure is logged, not surfaced. |
| GET `/api/sessions` | `GET /identity/sessions` | — | Session list for the actor. |
| POST `/api/sessions/revoke-others` | `POST /identity/sessions:revoke-others` | `tenant_id` | Naturally idempotent; predecessor sessions invalidated (§6 rotation). |
| GET `/api/saved` | `GET /customers/saved` | — | Array **or** `{ items: [...] }` accepted by BFF. Items carry `ref_id`/`refType` style keys. |
| POST `/api/saved` | `POST /customers/saved` | `ref_id`, `title` | Only these keys are ever sent. |
| GET `/api/settings` | `GET /customers/settings` | — | `{ settings: Record<string, unknown> }` |
| PATCH `/api/settings` | `PATCH /customers/settings` | `{ settings: {...} }` | BFF pre-filters to a **closed key set** — unknown keys never arrive. |
| GET `/api/verifications` | `GET /customers/verifications` | — | Verification records. |
| POST `/api/verifications` | `POST /customers/verifications` | `type` (`phone`\|`email`), `contact` (phone normalized E.164) | Sends a code — same abuse class as OTP issue; backend should rate-limit identity-side too (I7). |
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
`Set-Cookie` (see p0-contract §7 for cookie policy). Rotation on
completion of login/MFA must invalidate the predecessor token (I15).

### 2.5 Challenge object

```ts
interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;     // seconds
}
```

BFF response: `{ challengeId, expiresIn, ... }` (202), plus idempotent
replay of prior successful responses when `Idempotency-Key` repeats.

## 3. Non-negotiable backend invariants (gate checklist)

These cannot be verified from this repo and are the backend half of the
hard gate (details + concurrency scenarios in `p0-contract.md` §9,
full list §10):

- **C1** — concurrent valid submits on one challenge → exactly one success
- **C2** — concurrent resends → exactly one active challenge
- **C3** — (BFF-side, automated) idempotent double-submit → one effect
- **C4** — parallel wrong codes → exact attempt counting, one lock
- Wrong OTP ×5 → lock; post-lock attempts rejected without counting
- Expired challenge → definitive rejection (no proof oracle)
- MFA ×5 lock; consumed TOTP/recovery code replay → rejected
- Session rotation on login / MFA completion / privilege elevation
  invalidates the predecessor
- At most one active challenge per (identity, purpose); resend = new
  challenge, prior invalidated

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
| `service_unavailable` | `DEPENDENCY_UNAVAILABLE` | 503 |
| `internal_error` (and anything unmapped) | `INTERNAL_ERROR` | 500 |

Statuses produced by the BFF itself (cap 413, malformed 400, schema
422, throttle 429 + `Retry-After`, idempotency conflict 409, disabled
401) are listed in `p0-contract.md` §8; the full per-route control
matrix is in `route-hardening-matrix.md`.

## 5. Environment & infrastructure boundary

| Variable | Role |
|---|---|
| `CUSTOMER_BACKEND=laravel` + `NAVDHYA_API_BASE` | both required, else BFF serves 401 on wired routes (mocks stay default) |
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
