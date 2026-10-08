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
| POST /api/auth/login | 1 MB | zod (challengeId, proof\|code) | IP 10/10m | pre-auth | JSON-only ¹ | normal | ✅ | – (redeem is atomic backend-side) |
| POST /api/auth/verify | 1 MB | zod (challengeId, code) | IP 10/10m | pre-auth | JSON-only ¹ | normal | ✅ | – (same) |
| POST /api/auth/logout | – (body unread) | – | – | cookie, always clears ² | JSON-only ¹ | fast | ✅ | idempotent by design (I16) |
| POST /api/mfa/verify | 1 MB | zod command | IP 5/15m | cookie AAL1; AAL2 judged by backend (§5) | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/enroll | 1 MB | zod command | IP 5/15m | cookie AAL1; **AAL2 backend** | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/confirm | 1 MB | zod command | IP 5/15m | cookie AAL1 + enroll proof | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/revoke | 1 MB | zod command | IP 5/15m | cookie AAL1; **live TOTP backend** | JSON-only ¹ | normal | ✅ | – |
| POST /api/mfa/recovery-codes | 1 MB | zod command | IP 5/15m | cookie AAL1; **live TOTP backend** | JSON-only ¹ | normal | ✅ | – |
| GET /api/mfa/status | – | – | – | cookie (§7 matrix) | – | fast | ✅ | – |
| GET /api/sessions | – | – | – | cookie (§7 matrix) ³ | – | fast | ✅ | – |
| POST /api/sessions/revoke-others | 1 MB | zod `{}` (extras stripped) | IP 10/10m | cookie | JSON-only ¹ | normal | ✅ | naturally idempotent |
| GET /api/saved | – | – | – | cookie | – | fast | ✅ | – |
| POST /api/saved | 1 MB | zod (path-safe ref) | – ⁴ | cookie | JSON-only ¹ | normal | ✅ | – |
| GET /api/settings | – | – | – | cookie (anon dev fallback, non-security) ⁵ | – | fast | ✅ | – |
| PATCH /api/settings | 1 MB | object schema + closed key set | – ⁴ | cookie | JSON-only ¹ | normal | ✅ | – |
| GET /api/verifications | – | – | – | cookie | – | fast | ✅ | – |
| POST /api/verifications | 1 MB | zod discriminated union (E.164/email) | IP 10/10m + identity 5/10m | cookie | JSON-only ¹ | fast | ✅ | Key + replay, conflict→409 |
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
2. **Logout** clears the cookie unconditionally, even when backend
   revocation fails (I16). No body is read, so no cap is needed.
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

## Backend-authoritative items (Laravel — gate checklist)

The BFF cannot verify these (I18); they are contract requirements in
`auth/p0-contract.md` §9/§10 and must be gate-tested against Laravel:

- C1 concurrent valid submit → exactly one success
- C2 concurrent resend → exactly one active challenge
- C4 parallel wrong codes → exact attempt counting
- wrong-OTP ×5 lock, post-lock reject, expired-challenge reject
- MFA ×5 lock, MFA replay reject, session rotation (I15)
