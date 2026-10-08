# P0 Auth Contract

Status: **Architecture Locked / Contract Clarification Required → this document is the clarification.**

Every `auth/p0-*` branch implements against this contract. Reviewers check PRs
mechanically against §10 (invariants) and §9 (concurrency tests).

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
      ├── FAILED_ATTEMPT ──→ (attempts+1) ──→ LOCKED at 5
      ├── EXPIRED   (absolute TTL, default 10 min)
      └── CONSUMED  (terminal — never re-enterable)
```

Challenge record (backend-owned):

```text
id                crypto-random, ≥128 bits, unguessable        (I1)
purpose           'login' | 'signup' | 'verify_contact'        (I2)
identity          normalized E.164 phone / email               (I3)
createdAt / expiresAt   absolute expiry, no sliding window      (I4)
status            CHALLENGE_CREATED | OTP_SENT | PENDING_VERIFICATION
                  | VERIFIED | CONSUMED | LOCKED | EXPIRED
attempts          failed-attempt counter                       (I8)
lockUntil         set atomically at attempts >= 5              (I9)
consumedAt        set atomically on success                    (I6)
requestFingerprint sha256 of canonical request body            (I13)
```

**Resend decision (hard question 1): `new challenge + invalidate prior active challenge`.**

```text
For a given (identity, purpose): at most ONE active challenge exists.
new challenge  →  prior active challenge invalidated  →  create new challenge
```

This closes the dual-OTP failure mode (`111111` and `222222` both valid). (I12)

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

- 5 failed verifies → `LOCKED`, `lockUntil = now + 15 min`.
- Counter increment and lock transition are **atomic in Laravel** (I8, I9).
- `LOCKED`, `EXPIRED`, `CONSUMED` challenges reject **before** any proof
  comparison — no oracle leakage (I10, I11).
- Backend is authoritative. BFF mirrors `attemptsRemaining / lockedUntil /
  retryAfter` for UX only; reconstructed BFF state is never authoritative (I18).

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
AAL2 = AAL1 + fresh step-up (TOTP, or OTP issued within 5 min)
```

| Operation                  | Required |
|----------------------------|----------|
| `GET  /mfa/status`         | AAL1 |
| `POST /mfa/verify`         | AAL1 (produces step-up) |
| `POST /mfa/enroll`         | **AAL2** |
| `POST /mfa/confirm`        | AAL1 + valid enroll-secret proof |
| `POST /mfa/revoke`         | **AAL2** (live TOTP) |
| `POST /mfa/recovery-codes` | **AAL2** (live TOTP) |

Enforced in Laravel (authority). BFF pre-checks for UX only (I17).

A normal authenticated cookie does **not** automatically imply sufficient
assurance for any security-setting mutation.

---

## 6. Session semantics

- Issuance only after authoritative verification/consumption (I14).
- **Rotation triggers:** login success, MFA completion, privilege elevation.
  (No periodic rotation in P0.)
- Rotation invalidates the predecessor session (I15).
- Logout is idempotent: always `200`, always clears the cookie regardless of
  backend state (I16).

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

| ID | Test |
|----|------|
| C1 | Two concurrent valid proof submits → exactly 1 success, other `CHALLENGE_CONSUMED` |
| C2 | Concurrent resends → exactly 1 active challenge survives; no dual-OTP window |
| C3 | Concurrent idempotent double-submit → exactly 1 backend effect |
| C4 | 5 parallel wrong codes → counter reflects exactly 5, lock correct |

C1–C4 run alongside the 14 manual gate tests.

---

## 10. Invariants (review checklist)

```text
I1.  Challenge IDs are unguessable (≥128-bit crypto-random).
I2.  Challenge is purpose-bound.
I3.  Challenge is identity-bound.
I4.  Challenge has an absolute expiry.
I5.  Verification is atomic.
I6.  Successful verification consumes the challenge.
I7.  Consumed challenge cannot be replayed.
I8.  Failed-attempt counter is backend-authoritative.
I9.  Lock transition is atomic.
I10. Locked challenge cannot be verified.
I11. Expired challenge cannot be verified.
I12. New resend invalidates the prior active challenge.
I13. Idempotency key cannot cross request fingerprints.
I14. Session issuance occurs only after authoritative verification.
I15. Session rotation invalidates the predecessor.
I16. Logout is idempotent.
I17. MFA-sensitive operations require explicit assurance (AAL2).
I18. Security state cannot be reconstructed by the BFF.
```

Each `auth/p0-*` PR maps to the invariants it proves.
