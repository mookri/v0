# P0 Auth Contract

Status: **Architecture Locked / Contract Clarification Required → this document is the clarification.**

Every `auth/p0-*` branch implements against this contract. Reviewers check PRs
mechanically against §10 (invariants) and §9 (concurrency tests).

> **Backend-truth errata (post lazy-trace, see `backend-security-gaps.md`).**
> Several guarantees below once described the *target* security contract
> rather than what `platform-navdhya` enforced, and carried a
> `STATUS: NOT ENFORCED (backend blocker)` tag naming their gap ticket
> (M2–M5). **Those gaps landed 2026-10-09** — atomic redemption (M3),
> revocation/rotation store (M5), resend supersession (M2), MFA lockout +
> replay state (M4) — and the tags below now read `ENFORCED` with evidence.
> The split gate in §9 records the history: the *current-system gate* tests
> the always-true basics; the *security-improvement gate* (§9b) was re-run
> green after the Laravel changes (E2E 83/83, 2026-10-09). The BFF never
> simulates a guarantee the backend does not enforce.

---

## 0. Authority split

Root principle (I18): **assume the BFF is hostile/untrusted infrastructure.**
Mobile clients, workers, API gateways, internal tools, buggy proxies, and
direct internet access may reach Laravel without passing through this BFF.

```text
BFF (Next.js)                     Laravel (authoritative)
──────────────────────────────    ─────────────────────────────
rate limiting                     challenge authority
request validation                OTP verification
transport normalization           attempt counter + lockout
abuse-protection UX               MFA authority
                                  authorization
                                  session issuance / rotation / revocation
```

BFF may reject early for UX. BFF **never** constitutes security state.
Every invariant below must hold for `ANY TRUSTED CALLER → Laravel`.

---

## 1. Challenge lifecycle

```text
CHALLENGE_CREATED
      ↓
OTP_SENT
      ↓
PENDING_VERIFICATION
      ├── VERIFIED ──→ CONSUMED   (single atomic event, see §2)
      ├── FAILED_ATTEMPT ──→ (attempts+1) ──→ EXHAUSTED at 3
      ├── EXPIRED   (absolute TTL, default 10 min)
      └── CONSUMED  (terminal — never re-enterable)
```

Challenge record (backend-owned). Field list below is the **target**
model; the right column states what `customer_otp_challenges` actually
holds today:

```text
id                crypto-random, ≥128 bits, unguessable        (I1)   ✅ ULID
purpose           'login' | 'signup' | 'verify_contact'        (I2)   ✅
identity          normalized E.164 phone / email               (I3)   ✅
createdAt / expiresAt   absolute expiry, no sliding window      (I4)   ✅ +600 s
status            CHALLENGE_CREATED | OTP_SENT | PENDING_VERIFICATION
                  | VERIFIED | CONSUMED | LOCKED | EXPIRED            ⚠ no status
                                                                       column; consume
                                                                       and exhaustion
                                                                       are ROW DELETES
attempts          failed-attempt counter                       (I8)   ✅ max 3
lockUntil         set atomically at attempts >= 5                    ❌ does not exist
                                                                       (no time lock —
                                                                       M6 removed the
                                                                       fake 15-min UX lock)
consumedAt        set atomically on success                    (I6)   ❌ no column;
                                                                       consumption =
                                                                       delete (see §2)
requestFingerprint sha256 of canonical request body            (I13)  ⚠ BFF-side only
```

**Resend decision (hard question 1): `new challenge + invalidate prior active challenge`.**

```text
For a given (identity, purpose): at most ONE active challenge exists.
new challenge  →  prior active challenge invalidated  →  create new challenge
```

This closes the dual-OTP failure mode (`111111` and `222222` both valid). (I12)

> **STATUS: ENFORCED (M2, landed 2026-10-09).** `CustomerOtpService::issue()`
> stamps prior active challenges with `superseded_at` inside the issuing
> transaction; verify filters them into the uniform 401 (no oracle) while
> retaining the rows for the 5/hour cap. Evidence:
> `CustomerOtpSupersessionTest`; E2E C2/G1 (S10, S13).

---

## 2. OTP redemption semantics

**Verification and consumption are one atomic persistence event.**

```text
verify(challenge, proof)
      ↓
atomic compare-and-update (single transaction in Laravel)
      ↓
SUCCESS            → session issued in the SAME logical operation (I14)
ATTEMPT_INCREMENTED
LOCKED
EXPIRED
CHALLENGE_CONSUMED
```

Not: `verify` → later `consume`. That creates a race window.

> **STATUS: ENFORCED (M3, landed 2026-10-09).** `CustomerOtpService::verify()`
> runs inside one transaction with `lockForUpdate()` on the challenge row —
> no lost updates, no double session, attempts bump atomically. Evidence:
> `CustomerOtpSupersessionTest`; E2E C1/C4 (S13).

### Proof semantics (not vocabulary)

`proof` = **opaque challenge verifier** presented against a `challengeId`.

- P0 transport form: the OTP code itself, over TLS.
- Laravel performs the comparison *inside* the atomic consume step.
- The contract defines proof as *client-presented secret whose validation is
  owned by the backend*. The backend may later switch to a derived/signed
  verifier with **zero BFF change**. That migration point is recorded here so
  no one writes `proof = code` as if it were the contract.

### `code` → `proof` migration

```text
challengeId + proof   ← canonical
challengeId + code    ← temporary compatibility, ONE release only
```

- BFF accepts both during migration; every request logs
  `received_form: proof | code` alongside `requestId`.
- Removal milestone: after Laravel contract confirms `proof` and the frontend
  ships `proof`. Two authentication vocabularies are never institutionalized.

---

## 3. Attempt / lock semantics

Backend truth (`CustomerOtpService`) as verified in the lazy trace:

- **3** failed verifies exhaust the challenge (`MAX_ATTEMPTS = 3`);
  the backend deletes the row on exhaustion or expiry — there is **no
  time-based lock**, so no `LOCKED` state and no `lockUntil` ever exists.
- Exhausted/expired challenges reject **before** any proof comparison —
  no oracle leakage (I10, I11); the row is simply gone, so every redeem
  401s identically until the user obtains a new challenge.
- Backend is authoritative. The BFF/frontend mirrors a local failure
  count for UX only (3 attempts shown, then "request a new code") and
  must never fabricate a lock duration the backend does not enforce
  (I18). `Retry-After`-driven waits are real — they echo the backend's
  `rate_limited` response (5 challenges/hour per identity+purpose).

> **STATUS PARTIAL (backend blocker M4 for MFA).** The OTP counter above
> exists and is authoritative; what does *not* exist is any MFA attempt
> counter — see §5.

---

## 4. Idempotency semantics

Scope key:

```text
Idempotency-Key
  + (actorId | anonymousIdentity)
  + route
  + requestFingerprint = sha256(canonical JSON body)
```

State machine (atomic claim prevents the double-lookup race):

```text
ABSENT → IN_PROGRESS → COMPLETED | FAILED
```

- Concurrent `A` and `B` both observe ABSENT → exactly one claims
  `IN_PROGRESS`; the loser blocks or receives the winner's result.
- **Same key + different fingerprint → `409 IDEMPOTENCY_CONFLICT`.**
  Never silently reuse the first response for a semantically different
  request (idempotency poisoning).
- Applies in P0 to: `POST /auth/signup`, `POST /auth/otp`.

---

## 5. MFA assurance semantics

```text
AAL1 = valid session cookie
AAL2 = AAL1 + fresh step-up (TOTP or recovery code verified by the
       backend within the last 15 min — RequiresAcr::FRESH_MINUTES)
```

| Operation                  | Required (backend truth) |
|----------------------------|----------|
| `GET  /mfa/status`         | AAL1 |
| `POST /mfa/verify`         | AAL1 (produces step-up: writes `MfaStepUp`) |
| `POST /mfa/enroll`         | **AAL1 only** — the backend requires no step-up |
| `POST /mfa/confirm`        | AAL1 + valid enroll-secret proof |
| `POST /mfa/revoke`         | **AAL2** (live TOTP; recovery codes rejected) |
| `POST /mfa/recovery-codes` | **AAL2** (live TOTP; recovery codes rejected) |

Enforced in Laravel (authority). BFF pre-checks for UX only (I17).

A normal authenticated cookie does **not** automatically imply sufficient
assurance for any security-setting mutation.

> **Errata:** an earlier revision listed `enroll` as AAL2 — the backend
> accepts AAL1. Requiring a step-up before enroll is a desirable hardening
> (ticket M4-adjacent in `backend-security-gaps.md`), not current behavior.
>
> **STATUS: ENFORCED (M4, landed 2026-10-09) — MFA attempt counting.**
> `users.mfa_failed_attempts`/`mfa_locked_until`: 5 failures → 900 s lock →
> 429 + `Retry-After` (counter transition atomic under row lock),
> `mfa_last_used_step` rejects TOTP replay inside the window. The BFF keeps
> its own `mfaIp 5/900` throttle (strictly tighter, so it never widens the
> backend's envelope). Evidence: `MfaLockoutTest`; E2E G2 (S11).

---

## 6. Session semantics

- Issuance only after authoritative verification/consumption (I14). ✅
  enforced (challenge deleted → `ActorTokenIssuer::mint`).
- **Rotation triggers (target):** login success, MFA completion,
  privilege elevation. (No periodic rotation in P0.)
- Rotation invalidates the predecessor session (I15).
- Logout is idempotent: always `200`, always clears the cookie regardless
  of backend state (I16).

Backend truth today:

```text
token   = base64url(payload).base64url(HMAC-SHA256), claims {v,u,s,r[,m,o],e}
TTL     = 24 h absolute, no refresh
revocation store = DOES NOT EXIST (TOKEN-001 pending)
```

- **Rotation events (I15).** Each login/verify mints a fresh
  `sess_<ULID>` and revokes the presented same-actor predecessor
  (`ActorSessionRotation`); `POST /mfa/verify` rotates too and returns the
  new token in the login envelope — the BFF adopts it into the cookie.
- **Logout revokes.** The backend stamps `actor_sessions.revoked_at` for
  the presented session (the verifier fails closed on it); the BFF clears
  the cookie as the client half. Stolen bearers die at logout.

> **STATUS: ENFORCED (M5, landed 2026-10-09).** `actor_sessions` is the
> revocation store: `ActorTokenVerifier` fails closed on missing/revoked
> rows; logout, `revoke-others`, admin credential revoke, and every
> rotation revoke rows. Cookie Max-Age still tracks `expires_at`. Evidence:
> `ActorSessionRevocationTest`; E2E G3/G4 (S9, S11).

---

## 7. Cookie + authentication precedence

### Cookie policy per environment

| Environment             | Policy |
|-------------------------|--------|
| production HTTPS        | `__Host-` prefix + `Secure` |
| localhost HTTP (dev)    | **intentional separate policy**: no `Secure`, no `__Host-` prefix |
| reverse-proxy TLS       | `Secure` set when `x-forwarded-proto = https` |

Dev must never be a malformed attempt at `__Host-`.

### Authentication precedence matrix

| Cookie | Header | Result |
|--------|--------|--------|
| valid  | any    | cookie wins; header present → logged as legacy anomaly |
| INVALID (present) | any | **401 — no silent fallback** (blocks downgrade attack) |
| absent | valid  | allowed only when `LEGACY_HEADER_AUTH=1`; else 401 |
| absent | absent | 401 |

---

## 8. Error / status contract

| Status | Meaning |
|--------|---------|
| `202`  | challenge created (OTP/signup) |
| `201`  | session issued (login/verify) |
| `200`  | success (logout idempotent, status reads) |
| `400`  | malformed JSON |
| `401`  | unauthenticated / invalid credentials / invalid challenge |
| `403`  | CSRF failure or insufficient assurance |
| `409`  | idempotency conflict (same key, different fingerprint) |
| `422`  | schema validation failure |
| `429`  | throttle or lockout — includes `Retry-After` header |

---

## 9. Concurrency invariants (gate tests)

The gate is **split** so the BFF never pretends the backend is stronger
than it is.

### 9a. Current-system gate (must pass against today's backend)

| ID | Test |
|----|------|
| V1 | Verification start sends `{channel, value}` — never `{type, contact}` |
| V2 | OTP challenge `expiresIn` surfaces backend's 600 s TTL |
| V3 | 3 local failures exhaust the UX challenge → user restarts (no fake lock) |
| C3 | (BFF, automated) idempotent double-submit → one effect |
| V4 | Invalid/unknown `challengeId` → single uniform 401 (no oracle) |
| V5 | Logout always clears the cookie; cookie-only reads / bearer writes semantics hold |
| V6 | MFA route throttle (BFF `5/900` + backend `10/1`) enforced |
| V7 | Recovery codes are single-use (backend) and cannot revoke |
| V8 | Step-up proof expires after 15 min (`RequiresAcr::FRESH_MINUTES`) |
| V9 | No test asserts backend behavior it does not have (locks, rotation) |

### 9b. Security-improvement gate (landed 2026-10-09 — re-run green)

| ID | Test |
|----|------|
| C1 | Two concurrent valid proof submits → exactly 1 success, other `CHALLENGE_CONSUMED` *(M3)* |
| C2 | Concurrent resends → exactly 1 active challenge survives; no dual-OTP window *(M2)* |
| C4 | 3 parallel wrong codes → counter reflects exactly 3, exhaustion correct *(M3)* |
| G1 | Old challenge invalidated on resend *(M2)* |
| G2 | MFA attempt lockout enforced *(M4)* |
| G3 | Session rotation invalidates the predecessor *(M5)* |
| G4 | Logout revokes the credential *(M5)* |

C3 runs alongside the 14 manual gate tests; C1/C2/C4/G1–G4 are the
backend half of the hard gate. They failed against pre-M2–M5 Laravel —
that is why the gate split exists — and were re-run green after the fixes
(E2E 83/83 · 13/13 scenarios, 2026-10-09).

---

## 10. Invariants (review checklist)

```text
I1.  Challenge IDs are unguessable (≥128-bit crypto-random).               ✅
I2.  Challenge is purpose-bound.                                           ✅
I3.  Challenge is identity-bound.                                          ✅
I4.  Challenge has an absolute expiry.                                     ✅ 600 s
I5.  Verification is atomic.                          ✅ (M3 — txn + row lock)
I6.  Successful verification consumes the challenge.   ✅ (row delete)
I7.  Consumed challenge cannot be replayed.             ✅ (row gone)
I8.  Failed-attempt counter is backend-authoritative.   ✅ OTP / ✅ MFA (M4)
I9.  Lock transition is atomic.        ✅ OTP delete-on-4th atomic (M3);
                                       MFA 5-fail → 900 s lock (M4)
I10. Locked challenge cannot be verified.               ✅ (deleted, 401)
I11. Expired challenge cannot be verified.              ✅
I12. New resend invalidates the prior active challenge. ✅ (M2 — superseded_at)
I13. Idempotency key cannot cross request fingerprints.  ✅ BFF-side
I14. Session issuance occurs only after authoritative verification.        ✅
I15. Session rotation invalidates the predecessor.      ✅ (M5 — rotation)
I16. Logout is idempotent.                              ✅ (cookie clear + 200;
                                       backend revokes the session, M5)
I17. MFA-sensitive operations require explicit assurance (AAL2).
                                       PARTIAL: revoke/recovery ✅ live TOTP;
                                       enroll ❌ accepts AAL1 (see §5)
I18. Security state cannot be reconstructed by the BFF.  ✅ (design rule)
```

Each `auth/p0-*` PR maps to the invariants it proves. Invariants once
tagged `NOT ENFORCED` were the security-improvement gate (§9b) —
implemented in Laravel per `backend-security-gaps.md` (M2–M5 landed
2026-10-09, §9b re-run green), never simulated in the BFF.
