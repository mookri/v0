# API Route Hardening Matrix

Audit artifact for the P0→P2 hardening work (`auth/p0-contract.md`
companion). One row per BFF route; every cell is enforced in code and
covered by tests where marked.

Legend: ✅ enforced · – not applicable · ⚠ deferred (rationale below)

## Coverage

| Route | Body cap | Validation | Throttle (→429) | Actor / auth | CSRF | Timeout | X-Request-Id | Idempotency |
|---|---|---|---|---|---|---|---|---|
| POST /api/auth/otp | 1 MB | zod (E.164, purpose) | IP 10/10m + identity 5/10m | pre-auth | JSON-only ¹ | fast | ✅ | Key + replay, conflict→409 |
| POST /api/auth/signup | 1 MB | zod (E.164, name) | IP 5/10m | pre-auth | JSON-only ¹ | fast | ✅ | Key + replay, conflict→409 |
| POST /api/auth/login | 1 MB | zod (challengeId, proof\|code) | IP 10/10m | pre-auth | JSON-only ¹ | normal | ✅ | – (single redeem call; backend atomicity = M3) |
| POST /api/auth/verify | 1 MB | zod (challengeId, code) | IP 10/10m | pre-auth | JSON-only ¹ | normal | ✅ | – (same) |
| POST /api/auth/logout | – (body unread) | – | – | cookie, always clears ² | JSON-only ¹ | fast | ✅ | idempotent by design (I16) |
| POST /api/mfa/verify | 1 MB | zod command | IP 5/15m | cookie AAL1 (produces step-up; backend locks at 5 fails/15 min — M4) | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/enroll | 1 MB | zod command | IP 5/15m | cookie **AAL1 only** (backend requires no step-up) | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/confirm | 1 MB | zod command | IP 5/15m | cookie AAL1 + enroll proof | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/revoke | 1 MB | zod command | IP 5/15m | cookie AAL1; **live TOTP backend** | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/recovery-codes | 1 MB | zod command | IP 5/15m | cookie AAL1; **live TOTP backend** | JSON-only ¹ | normal | ✅ | – |
| GET /api/mfa/status | – | – | – | cookie (§7 matrix) | – | fast | ✅ | – |
| GET /api/sessions | – | – | – | cookie (§7 matrix) ³ | – | fast | ✅ | – |
| POST /api/sessions/revoke-others | 1 MB | zod `{}` (extras stripped) | IP 10/10m | cookie | JSON-only ¹ | normal | ✅ | naturally idempotent |
| GET /api/saved | – | – | – | cookie | – | fast | ✅ | – |
| POST /api/saved | 1 MB | zod (`ref_type` enum + ULID `ref_id`) | – ⁴ | cookie | JSON-only ¹ | normal | ✅ | – |
| GET /api/settings | – | – | – | cookie (anon dev fallback, non-security) ⁵ | – | fast | ✅ | – |
| PATCH /api/settings | 1 MB | object schema + closed key set | – ⁴ | cookie | JSON-only ¹ | normal | ✅ | – |
| GET /api/verifications | – | – | – | cookie | – | fast | ✅ | – |
| POST /api/verifications | 1 MB | zod discriminated union (`channel`/`value`, E.164/email) | IP 10/10m + identity 5/10m | cookie | JSON-only ¹ | fast | ✅ | Key + replay, conflict→409 |
| POST /api/verifications/confirm | 1 MB | zod (path-safe id, code) | IP 5/15m | cookie | JSON-only ¹ | normal | ✅ | – |
| POST /api/notifications/read-all | 1 MB | zod `{}` (extras stripped) | – ⁴ | cookie | JSON-only ¹ | fast | ✅ | naturally idempotent |
| POST /api/ads/decision | 64 KB | zod envelope | – ⁴ | public by design | **CSRF** (prod/secret) | local | – (client id in payload) | – |
| POST /api/ads/track | 64 KB | zod batch (50 events max) | IP 60 batches/min | public by design | JSON-only ¹ ⁶ | local | – | dedupe via idempotencyKey |
| POST /api/ads/track-beacon | 64 KB | zod batch | IP 60 batches/min | public by design | JSON-only ¹ ⁶ | local | – | same |
| POST /api/revalidate | 64 KB | slug caps + format | 30/min cost-weighted | secret (timing-safe HMAC) | **CSRF** (prod/secret) | local | – | – (ISR is idempotent) |

## Rationale notes

1. **JSON-only CSRF posture**: every browser call sends
   `Content-Type: application/json`, which forces a CORS preflight — a
   cross-site attacker page cannot issue these requests without CORS
   approval, and session cookies are `SameSite=Lax`. The two public
   non-authenticated hot paths that predate this work (`decision`,
   `revalidate`) additionally carry explicit CSRF tokens. This is
   defence-in-depth layering, not a token dependency.
2. **Logout** clears the cookie unconditionally (I16). No body is read,
   so no cap is needed. The backend also revokes the presented session
   (`actor_sessions`, M5 landed 2026-10-09) — the cookie clear is the
   client half of the logout.
3. **Precedence matrix (§7)**: cookie wins; a present-but-invalid
   cookie is a hard 401 with no silent `Authorization` fallback;
   header-only auth exists only behind `LEGACY_HEADER_AUTH=1`.
   The two raw-`Authorization` call sites (sessions) were the last
   ones in the tree and are gone.
4. No throttle: benign, authenticated, non-credential operations.
   Backend request volumes still bound by the backend's own limits (I18).
5. Settings `GET` serves the section catalog without a backend only in
   dev (`CUSTOMER_BACKEND` unset); no security decision is made there.
6. Tracking deliberately has **no CSRF token**: no client sends
   `x-csrf-token` on tracking calls, so gating would silently drop
   impression/click accounting wherever the gate activates. Bounded
   instead by cap + zod + per-IP throttle.
7. ⚠ **Deferred — login CSRF**: an attacker-orchestrated flow could
   plant an attacker-owned session in a victim browser (the OTP goes to
   the attacker's phone). Impact is standard login-CSRF (victim acting
   in the attacker's account). Mitigation options (Sec-Fetch-Site
   enforcement on pre-auth routes, or per-login CSRF nonce) need a
   product decision on non-browser callers — recorded here rather than
   silently implemented.

## Backend-authoritative items — split by what Laravel enforces today

The BFF cannot verify any of these (I18); they are contract requirements
in `auth/p0-contract.md` §9/§10.

### Current-system gate (holds against today's backend)

- Expired-challenge reject (row deleted after 600 s)
- Wrong-OTP ×3 → challenge deleted, every further redeem uniform 401
- Recovery codes single-use; revoke/regenerate require live TOTP
- Step-up proof expires after 15 min (`RequiresAcr::FRESH_MINUTES`)
- MFA route throttle `10,1` (BFF tightens to `5/15m`)
- Logout clears the BFF cookie; backend revokes the bearer (`actor_sessions`, M5)

### Security-improvement gate — landed 2026-10-09 (all green)

Ticketed in `auth/backend-security-gaps.md`; now enforced by Laravel and
re-run green (E2E 83/83) — BFF tests/UX may assert them:

- C1 concurrent valid submit → exactly one success *(M3 ✅)*
- C2 concurrent resend → exactly one active challenge *(M2 ✅)*
- C4 parallel wrong codes → exact attempt counting *(M3 ✅)*
- Resend invalidates the prior challenge — `superseded_at` stamp *(M2 ✅)*
- wrong-OTP exhaustion — still row-delete, now atomic under the row lock *(M3 ✅)*
- MFA attempt lock + TOTP replay reject — 5 fails → 900 s, used-step state *(M4 ✅)*
- Session rotation (I15) and logout revocation — `actor_sessions` store *(M5 ✅)*
