# Firebase Phone-OTP Spike (Verification Fabric S45)

**Status:** DESIGN-VALIDATED · **Date:** 2026-10-12 · **Type:** spike / evidence record
**Scope:** server-driven Firebase phone OTP as a *verification channel only*; core auth stays in
Navdhya DBs (Laravel sole authority). Companion to SEC/token platform doc; replaces nothing.

## Decision

**Proceed with the server-driven design.** Google's Identity Platform REST API supports fully
server-to-server send + verify of phone OTP codes: our clients only ever see the frozen
`POST /api/verifications` → `confirm` contract, and every provider call happens inside Laravel.
No Firebase JS SDK ships to any client; the only client artifact is a plain reCAPTCHA v2 token.

## Evidence (docs.cloud.google.com, accessed 2026-10-12)

### 1. Send — `accounts:sendVerificationCode`

```
POST https://identitytoolkit.googleapis.com/v1/accounts:sendVerificationCode?key=<WEB_API_KEY>
{ "phoneNumber": "+91…", "recaptchaToken": "<v2 token>" }
→ { "sessionInfo": "<opaque>" }
```

- Exactly one attestation is required: `recaptchaToken` (reCAPTCHA **v2**),
  `playIntegrityToken` (Android), `iosReceipt`+`iosSecret`, or `captchaResponse`
  (reCAPTCHA Enterprise — only when enterprise is enabled; then `clientType` +
  `recaptchaVersion` are also required).
- The site key is project-bound and fetched via
  `GET https://identitytoolkit.googleapis.com/v1/recaptchaParams` → `recaptchaSiteKey`
  (reCAPTCHA v2, "always present"). The client renders a plain reCAPTCHA v2 widget with
  that key — **no Firebase SDK involved** — and posts the token with the phone number.
- `sessionInfo` is the opaque `provider_ref` we persist on the challenge row.

### 2. Verify — `accounts:signInWithPhoneNumber`

```
POST https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key=<WEB_API_KEY>
{ "sessionInfo": "<opaque>", "code": "<6 digits>" }
→ { "idToken", "refreshToken", "expiresIn", "localId", "isNewUser",
    "phoneNumber" /* always present */ }
```

- Server-side verification; no captcha at this step.
- `phoneNumber` is **always present** in the response — we do not need to decode the
  `idToken` to learn the verified number. Cross-checking the `phone` claim via Admin SDK
  `verifyIdToken` (with `aud` pinned to the audience's project) remains an optional
  hardening step, recorded as a live-validation item.
- A *shadow* account may be minted inside the Firebase project (documented, expected,
  non-authoritative — we never read users from Firebase).

### 3. Test phone numbers (gate strategy)

- Console → Authentication → Sign-in method → Phone → **Phone numbers for testing**:
  fixed code (e.g. `123456`), **no SMS sent → no SMS cost**. Signature otherwise matches a
  real number.
- The JS SDK can bypass captcha for tests via `appVerificationDisabledForTesting` — that is
  **client-SDK-only**. Whether REST `sendVerificationCode` accepts a test number *without* a
  valid `recaptchaToken` (or tolerates a Google test-key token) is **unresolved** → L1/L2
  below.

### 4. Pricing & quotas (drives the budget-envelope design)

| Item | Value |
|------|-------|
| Plan | Phone auth requires **Blaze** (billing attached); Spark (free) excludes it |
| Per-SMS | ~**$0.01** US/CA/IN · up to ~$0.46 expensive regions |
| Free allowance | first **10 SMS/day** free (test numbers: free, no SMS at all) |
| Rate limit | **150 verification requests / IP / hour** |
| Blast radius | separate Firebase project per audience isolates quota + pumping risk |

Our local caps (5/hour/identifier, DB-backed) stay **in front of** every provider call as the
billing guard; per-audience budget envelopes meter actual burn.

## Open items — live validation checklist (blocked on Firebase creds)

Exact transcript to capture when `FIREBASE_{CUSTOMER,VENDOR}_*` env values arrive:

- **L1** send test-number **without** token → accepted or captcha error? (error code recorded)
- **L2** send test-number **with** reCAPTCHA v2 token from `recaptchaSiteKey` in headless CI
- **L3** verify wrong code / expired `sessionInfo` / exhausted → exact error codes, for the
  normalized taxonomy (`invalid_code | expired | provider_down | rate_limited | …`)
- **L4** happy-path round trip via curl → `sessionInfo` → correct fixed code → `phoneNumber`
  matches (transcript pasted into this file)
- **L5** Playwright path: invisible reCAPTCHA v2 in CI — works, or UI flow stays on
  `LocalCodeProvider` (decision point, record outcome here)
- **L6** API-key restrictions (IP/referrer) + App Check posture per project

## Design consequences (feeds S46–S49)

- Port shape confirmed: `send(phone, audience, purpose, attestation) → provider_ref`,
  `verify(provider_ref, code) → normalized outcome`; Firebase adapter = thin HTTP client
  over the two endpoints above, keyed by the audience registry.
- Challenge row stores `provider` + `provider_ref` (the `sessionInfo` blob) instead of
  `code_hash` for remote sends; attempt caps, supersede, TTL, rate limits remain local and
  unchanged (uniform 401 no-oracle maps Google's error codes at the adapter).
- Fail closed: quota/captcha/provider errors → typed `DEPENDENCY_UNAVAILABLE`, never a
  silent downgrade to a channel that cannot deliver SMS.
- `customer_otp_challenges.id` width fix rides along in S46 (root cause of the MySQL
  step-up test cluster).
