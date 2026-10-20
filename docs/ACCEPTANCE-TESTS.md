# Acceptance Test Specifications — Decoupled MSG91 Widget OTP Subsystem

**Status:** Active Test Specification  
**Test Harness:** PHPUnit (Laravel Backend), Jest (`apps/web`), Vitest (`@navdhya/otp`), Playwright (E2E)

---

## 1. Test Suite Matrix

| Level | Suite / File | Scope |
|---|---|---|
| **Unit / Service** | `platform-navdhya/tests/Unit/Platform/Identity/Otp/*` | `Msg91Client`, `OtpChallengeManager`, `OtpDeliveryPolicy`, token verification. |
| **Integration** | `platform-navdhya/tests/Feature/CustomersAuthTest.php` | Signup, login, MSG91 token redemption, session cookies, and 401 anti-enumeration. |
| **Integration** | `platform-navdhya/tests/Feature/CustomerVerificationTest.php` | Profile email/phone verifications using OTP challenge subsystem. |
| **Integration** | `platform-navdhya/tests/Feature/StepUpFlowTest.php` | Admin & MFA step-up TOTP verification with OTP challenges. |
| **Package Unit** | `packages/otp/src/__tests__/resend.test.ts` | Resend countdown hook, interval progression, labels. |
| **Package Unit** | `packages/otp/src/widget/__tests__/loader.test.ts` | Widget singleton injection, exposeMethods mode, globals bridging, errors. |
| **Frontend Unit** | `apps/web/components/auth/__tests__/OtpScreen.test.tsx` | Web auth console, 3-attempt exhaustion, 202 MFA step-up, 429 Retry-After. |
| **Architecture** | `tests/architecture/communication-boundaries.test.ts` | Complete ban on Firebase, isolation of `verify.msg91.com` to loader.ts. |
| **E2E Full Stack** | `e2e/ui.spec.ts` & `e2e/run.ps1` | Full browser flow: signup → logout → login → 3-attempt exhaustion → fresh code login. |

---

## 2. Test Case Specifications

### TC-01: Happy Path Single Factor Login
- **Preconditions:** Active tenant, customer account exists (`+919877001122`), MFA disabled.
- **Action 1:** `POST /api/auth/otp` with `{ identity: "+919877001122", purpose: "login" }`.
- **Expected 1:** HTTP `200 OK`, `challengeId` returned, DB row created in `otp_challenges`.
- **Action 2:** Browser executes `verifyOtp("123456")` via MSG91 widget SDK -> yields verified JWT access token.
- **Action 3:** `POST /api/auth/login` with `{ challengeId, token }`.
- **Expected 3:** HTTP `200 OK`, `status: "authenticated"`, `Set-Cookie` header present with `__Host-navdhya-session`, challenge row redeemed.

### TC-02: MFA Step-Up Trigger (TOTP Enrolled)
- **Preconditions:** Customer account exists with `mfa_enabled = true` and enrolled TOTP secret.
- **Action:** `POST /api/auth/login` with valid `{ challengeId, token }`.
- **Expected:**
  - HTTP `202 Accepted`, body contains `{ status: "mfa_required" }`.
  - Full session cookie is **withheld**.
  - UI transitions to authenticator app TOTP code entry input.
- **Action 2:** `POST /api/auth/login` with `{ challengeId, totpCode: "<6-digits>" }`.
- **Expected 2:** HTTP `200 OK`, actor session cookie minted.

### TC-03: Three-Attempt Exhaustion (Backend Truth)
- **Preconditions:** Active challenge created in `otp_challenges`.
- **Action:** Submit incorrect code (`000000`) or invalid tokens 3 consecutive times.
- **Expected:**
  - Attempt 1: HTTP `401 Unauthorized` / widget error, UI shows `(2 attempts remaining)`.
  - Attempt 2: HTTP `401 Unauthorized` / widget error, UI shows `(1 attempts remaining)`.
  - Attempt 3: HTTP `401 Unauthorized`, challenge row is immediately **deleted/destroyed from database**.
  - UI shows `Too many failed attempts. Please request a new code.`. No fabricated client-side lock.
  - Subsequent 4th submit blocked locally; user must click "Request fresh code".

### TC-04: Compounding Resend Delays
- **Preconditions:** Active challenge.
- **Action:** Request resend via `POST /api/auth/otp/resend`.
- **Expected:**
  - 1st resend: sets `next_allowed_at` to `now + 60s`, returns `resends_left: 2`.
  - 2nd resend (after 60s): sets `next_allowed_at` to `now + 180s`, returns `resends_left: 1`.
  - 3rd resend (after 180s): sets `next_allowed_at` to `now + 540s`, returns `resends_left: 0`.
  - Calling `/resend` early returns HTTP `429 RATE_LIMITED` with `Retry-After`.

### TC-05: Hourly Fresh Challenge Cap
- **Preconditions:** User requests 3 fresh challenges within a 1-hour rolling window.
- **Action:** Attempt to issue a 4th challenge within the hour.
- **Expected:** HTTP `429 Too Many Requests`, error `RATE_LIMITED`, `Retry-After: 600`.

### TC-06: Identity Attestation Integrity
- **Preconditions:** Challenge created for phone A (`+919877001122`).
- **Action:** Attacker provides a valid token minted for phone B (`+919876500000`).
- **Expected:** Laravel verifies token with MSG91, detects identifier mismatch (`919876500000 !== 919877001122`), rejects with HTTP `401 UNAUTHENTICATED`, burns attempt.
