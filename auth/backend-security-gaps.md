# Backend Security Gaps (Laravel tickets M2–M5)

Status: **FIXED — implemented in `platform-navdhya`, verified by the §9b
gate (2026-10-09).** `p0-contract.md` invariant tags have been flipped
accordingly.

Produced by the P0 lazy trace of the live backend (see `laravel-contract.md`
§3.2 for the gate these unlock). Each gap was a case where the target
contract (`p0-contract.md`) was *not* enforced by Laravel. The BFF
deliberately did **not** simulate any of these behaviors — a hardened BFF
layer over a weaker backend would be a false security boundary — and it
still doesn't: every guarantee below is backend state, not BFF theater.

Ordering follows risk: M3 (double session) > M5 (no revocation) >
M2 (dual-OTP) > M4 (MFA brute force).

**Verification evidence (all green):**

| Gate | Result |
|---|---|
| PHPUnit (sqlite, `platform-navdhya`) | ✅ 647 tests / 3703 assertions — 0 failures |
| PHPUnit-on-MySQL (`phpunit.mysql.xml`, fresh `navdhya_test_*`) | ✅ 647 tests · **0 failures** · 14 errors = documented non-auth baseline F-DB3a–c |
| E2E HTTP (`e2e/run.ps1`, incl. §9b wire scenarios S9/S10/S11/S13) | ✅ **83/83 assertions · 13/13 scenarios · exit 0** |
| E2E Playwright (UI driver) | ✅ exit 0 |
| Pint (all touched files) | ✅ clean |

New dedicated tests: `CustomerOtpSupersessionTest` (M2/M3, 5),
`MfaLockoutTest` (M4, 5), `ActorSessionRevocationTest` (M5, 4); adapted
`ActorTokenVerifierTest`, `HmacTokenMiddlewareTest`, `SuspensionEnforcementTest`.

---

## M3 — Atomic challenge redemption (concurrency) — ✅ fixed

**Was: critical (auth bypass under concurrency)**

Old implementation — `CustomerOtpService::verify()`:

```text
first()            ← read, no lock
  ↓ expiry / attempts check
forceFill(attempts++).save()   ← read-modify-write, lost updates
  ↓ hash_equals
delete()           ← second request may have passed first() already
```

Race window: two concurrent valid submits both read the row before either
deleted → **two sessions minted from one challenge** (violated C1);
concurrent wrong codes lost increments (violated C4).

**Implemented** (`app/Modules/Customers/Auth/CustomerOtpService.php`):

- `verifyForPurposes()` runs inside the central transaction with
  `lockForUpdate()` on the challenge row (`SELECT … FOR UPDATE` on MySQL;
  no-op on sqlite). Read → expiry/attempts check → `hash_equals` →
  consume/attempts-bump all happen under the row lock — one writer, no
  lost updates, no double session.
- Sequential semantics preserved: bump-before-compare, delete on the 4th
  failed call; superseded ids die in the uniform 401 (no oracle).
- `lockForUpdate()` is portable: MySQL row lock, sqlite compiles to a
  no-op (single-writer anyway).

**Evidence:** `CustomerOtpSupersessionTest` (exact attempt counting under
overlap); E2E **C1** (overlapping redeems → exactly one 200, one uniform
401, challenge deleted) and **C4** (3 overlapping wrong codes → attempts
exactly 3) in S13; S4/S5 wire forms.

---

## M5 — Session revocation / rotation (TOKEN-001) — ✅ fixed

**Was: critical (logout was cosmetic; stolen token lived 24 h)**

Old truth: `ActorTokenVerifier` checked HMAC + expiry only — no jti
lookup; logout wrote an audit row while the bearer stayed valid; no
rotation event; `sessions:revoke-others` invalidated nothing real.

**Implemented:**

- `actor_sessions` table (central; `sess_` ULID PK, `user_id`, `issued_at`,
  `expires_at`, `revoked_at`) + `ActorSession` model — the revocation
  store. `ActorTokenIssuer::mint()` persists every issued session
  (`TOKEN_TTL_SECONDS = 86400`).
- `ActorTokenVerifier::verify()` **fails closed**: missing or revoked row
  ⇒ null (timing-safe, uniform 401).
- Logout revokes the presented `actor.session_id` (then the BFF clears
  the cookie — both halves now).
- **Rotation** via `ActorSessionRotation`: every `mintToken()` revokes the
  presented same-actor predecessor, and MFA verification (`MfaController::
  verify` → login envelope) revokes the predecessor and returns the new
  token; the BFF adopts it (`app/api/mfa/verify/route.ts` → `setSessionCookie`).
- `revoke-others` (`IdentitySelfServiceController`) and
  `UserAdminController::revokeCredentials` revoke the actor's sessions;
  `revoke-others` spares the session the **caller presented** (the
  verified `actor` request attribute, same source logout reads — F-SMOKE3
  fix: the unconditional sweep used to log the bearer caller out of its
  own request; cookie path presents no actor session, sweep unchanged
  there); `CleanupExpiredCommand` sweeps expired/revoked rows.
- HMAC actor tokens carry **one** dot (two segments) — rotation reads
  `presentedClaims()` accordingly; JWTs (two dots) are excluded.

**Evidence:** `ActorSessionRevocationTest` (4) + adapted verifier/middleware/
suspension tests; E2E **G4** (S9: logout → bearer dead on direct Laravel =
401, `actor_sessions.revoked_at` 1→2) and **G3** (S11: old token dead /
rotated token alive on direct Laravel).

---

## M2 — Challenge supersession on resend — ✅ fixed

**Was: high (dual-OTP window)**

Old truth — `CustomerOtpService::issue()` counted the last hour's
challenges against the 5/hour cap but **never invalidated prior active
challenges**: `111111` and `222222` were both valid until their own
600 s TTL (violated I12).

**Implemented** (`CustomerOtpService::issue()`):

- Runs inside the central transaction with `lockForUpdate()` on the
  tenant row; prior *active* challenges for
  `(tenant_id, purpose, identifier)` are stamped with the new
  `superseded_at` column before the new row is inserted.
- `verify()` filters `whereNull('superseded_at')` — superseded ids fall
  into the uniform 401 (no supersession oracle) and **rows are retained**
  so they still count toward the 5/hour cap (no cap dodge on resend).
- One engine covers `/customers/auth/*` and `/customers/verifications`.

**Evidence:** `CustomerOtpSupersessionTest` (5); E2E **C2/G1** — S10
(total=2, active=1, superseded redeem = 401) and S13 (overlapping resends
→ active=1, superseded rows retained).

---

## M4 — MFA attempt lockout + TOTP replay state — ✅ fixed

**Was: high (6-digit space vs. no counter)**

Old truth: `MfaService` had **no attempt counter and no lockout** (only
route `throttle:10,1`), and no used-code state — a TOTP could be replayed
inside its 30 s step + ±1 window. Recovery codes were already correct.

**Implemented:**

- `users.mfa_failed_attempts` / `users.mfa_locked_until` +
  `MfaLockedException` (`App\Platform\Identity`, carries `retryAfter`):
  **5 failures → 900 s lock**; controllers catch → **429 + Retry-After**.
  Counter increments atomically (transaction + `lockForUpdate`, throw
  after commit); success = guarded reset; revoke() resets counters.
- Replay prevention: `MfaService::matchingStep()` over
  {now−1, now, now+1} with `hash_equals` against
  `users.mfa_last_used_step` — a used code dies inside its own window.
  (Known latent note: contract WINDOW=1 reads ±1 *step* while the
  implementation's candidate set spans ±1 *s* within the current step —
  pinned as-is by tests; contract wording to be reconciled separately.)
- Login TOTP branch and `MfaController::verify` both enforce the lock.
- Optional hardening remains open: step-up before `POST /identity/mfa/
  enroll` (today it is AAL1-only — `p0-contract.md` §5 errata).

**Evidence:** `MfaLockoutTest` (5 — lock transition, Retry-After,
fail-closed correct code, counter reset, replay reject); E2E **G2** (S11:
direct-on-Laravel drill — 5 wrong codes → 429 + Retry-After=900, correct
code while locked → 429).

---

## What the BFF does (still explicitly *not* a substitute)

| Control | BFF state | Honest label |
|---|---|---|
| OTP attempt mirror | local count, stops at 3, no fake lock (`OtpScreen`) | UX only (I18) |
| OTP/IP throttle | `10/600`, `5/600` — tighter than backend `5/hour` | abuse bound only |
| MFA throttle | `5/900` vs backend `10/1` | tightens the envelope; the ×5/15-min lock is backend state (M4) |
| Logout | BFF clears the cookie **and** forwards the bearer so the backend revokes; no "revoked" claim invented by the BFF | boundary = backend store + cookie |
| Idempotency-Key | BFF in-process store (single instance) | C3 only; per-instance |

The BFF still does not fabricate any backend state. Closing these tickets
meant changing `platform-navdhya`, re-running the security-improvement
gate (§9b) — done, 83/83 — and only then flipping `p0-contract.md`
invariant tags from `NOT ENFORCED`.
