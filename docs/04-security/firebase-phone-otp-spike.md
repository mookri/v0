# Firebase Phone-OTP Spike (Verification Fabric S45)

**Status:** DESIGN-VALIDATED · LIVE-VALIDATED S49 (test numbers, 2026-10-12) · **Date:** 2026-10-12 · **Type:** spike / evidence record
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

## Live validation (S49 — 2026-10-12, test-numbers gate)

**Harness:** `scripts/firebase-spike-live.ps1 -EnvFile .\platform-navdhya\.env` (ASCII-only,
PS 5.1-safe). `-SelfTest` runs the whole HTTP + capture + redaction pipeline against a
deliberately invalid key (`API_KEY_INVALID`) so the pipeline itself can be proven without
credentials. Redaction is hard-coded: API key never printed, phone masked, `idToken` /
`refreshToken` stripped, `sessionInfo` replaced by `len+sha256`, OTP codes never printed.

### Environment finding: CLI PHP had no CA bundle (fixed)

The first outbound TLS call this stack ever made (S45–S48 tests all `Http::fake`) failed with
`SSL certificate OpenSSL verify result: unable to get local issuer certificate` — `curl.cainfo`
and `openssl.cafile` were empty in `C:\xampp\php\php.ini`, while the served chain was the
genuine Google Trust Services one (verified subject/issuer — no interception). Fixed by
downloading the official `https://curl.se/ca/cacert.pem` to
`C:\xampp\php\extras\ssl\cacert.pem` and pointing both ini settings at it.
**Deployment note:** Linux images must ship a CA bundle (standard `ca-certificates`);
without it every provider call fails — and that failure incidentally proved the fail-closed
path live (see proof table below: typed 503, no downgrade, no challenge row).

### L1–L6 transcript (full, harness output)

```markdown
# Firebase phone-OTP live spike -- L1..L6 transcript

- **When:** 2026-10-12 15:23:00 UTC
- **Audience:** customer (project: `navdhya-customer-1`)
- **Test number:** `+919*******99` (console *Phone numbers for testing*; no SMS, no SMS cost)
- **API key:** `<redacted>`  -  **reCAPTCHA site key:** `6Lf8zOstAAAAAEnByM8sUzYhxrt51xkLKtCtYady`

## L0 -- recaptchaParams (site key discovery)
```
GET /v1/recaptchaParams  ->  HTTP 200  {"kind":"identitytoolkit#GetRecaptchaParamResponse","recaptchaSiteKey":"6LcMZR0UAAAAALgPMcgHwga7gY5p8QMg1Hj-bmUv","producerProjectNumber":"551503664846","recaptchaStoken":"<redacted>"}
```

## L1 -- send test number WITHOUT attestation
```
POST accounts:sendVerificationCode {phoneNumber:+919*******99}  ->  HTTP 200  {"sessionInfo":"<session len=162 sha256=09d3ef6f95f9>"}
```

**Result:** test number accepted WITHOUT a reCAPTCHA token (captcha demand can stay off for test numbers).

## L2 -- send WITH reCAPTCHA v2 token
**SKIPPED** -- no `FIREBASE_RECAPTCHA_TOKEN` in env (headless minting is the open question; L5 owns the CI story).

## L3 -- verify taxonomy (wrong code x5, exhausted, garbage session)
```
verify attempt 1 (wrong code)  ->  HTTP 400  INVALID_CODE, INVALID
verify attempt 2 (wrong code)  ->  HTTP 400  INVALID_CODE, INVALID
verify attempt 3 (wrong code)  ->  HTTP 400  INVALID_CODE, INVALID
verify attempt 4 (wrong code)  ->  HTTP 400  INVALID_CODE, INVALID
verify attempt 5 (wrong code)  ->  HTTP 400  INVALID_CODE, INVALID
verify with garbage sessionInfo  ->  HTTP 400  INVALID_SESSION_INFO, INVALID
```

## L4 -- happy path round trip
```
send  ->  HTTP 200  {"sessionInfo":"<session len=162 sha256=5f204836c3cc>"}
verify with fixed test code  ->  HTTP 200  {"phoneNumber":"+919*******99","expiresIn":"3600","refreshToken":"<stripped>","idToken":"<stripped>","isNewUser":true,"localId":"<uid len=28>"}
```

**Result:** round trip OK, `phoneNumber` matches the test number (idToken stripped from transcript).

## L5 -- Playwright / invisible reCAPTCHA v2 in CI (DECISION POINT)
- L2 outcome above feeds this: token availability = `not provided (skipped)`.

## L6 -- API-key restrictions + App Check posture (MANUAL, per project)
- Console > APIs & Services > Credentials > Web key: application restrictions (HTTP referrer / IP) + API restrictions (Identity Toolkit only).

## Probe summary (feeds CODE_ERROR_CODES calibration)

| Probe | Outcome |
|---|---|
| L0 | `ok` |
| L1 | `ok` |
| L2 | `skipped:no-token` |
| L3-wrong#1..5 | `INVALID_CODE, INVALID` |
| L3-garbage-session | `INVALID_SESSION_INFO, INVALID` |
| L4 | `ok:phoneNumber-match` |
```

(Attempts 1–2 of the live run failed `OPERATION_NOT_ALLOWED : SMS unable to be sent until
this region enabled by the app developer` until the console SMS region policy / Blaze plan
were set — the region gate applies to test numbers too.)

### Calibration verdict (L3 → `CODE_ERROR_CODES`)

| Probe | Observed symbolic parts (adapter's `symbolicCodes()`) | Adapter maps to |
|---|---|---|
| wrong code ×5 | `INVALID_CODE`, `INVALID` | Mismatch → uniform 401 ✓ |
| garbage sessionInfo | `INVALID_SESSION_INFO`, `INVALID` | Mismatch → uniform 401 ✓ |
| region disabled (send) | `OPERATION_NOT_ALLOWED` | not in family → typed 503, fail closed ✓ |

- Both observed heads are already in `CODE_ERROR_CODES` → **calibrated by live evidence, no
  code change needed**.
- The generic `INVALID` reason deliberately does not participate (it would misclassify every
  400). No lockout/exhaustion transition occurred within 5 wrong attempts (message never
  changed across attempts).
- **L1 verdict:** test numbers are accepted with **no** reCAPTCHA token →
  `OTP_CAPTCHA_REQUIRED_*` stays off while the gate runs on test numbers; real-number sends
  (S50+) reopen the L2/L5 token question.

### L5 (headless reCAPTCHA v2) — OPEN by decision

Token minting was not attempted in CI (`FIREBASE_RECAPTCHA_TOKEN` unset → L2 skipped).
Decision: the gate runs on test numbers with captcha demand OFF, so no UI/widget change is
needed now; the headless question reopens when `OTP_CAPTCHA_REQUIRED_*` turns on.

### L6 (console posture) — MANUAL, pending

- [ ] API key: application restrictions (HTTP referrer/IP) + API restriction to Identity
      Toolkit, per project.
- [ ] App Check posture recorded per project (N/A for pure server-side REST).
- [ ] **Site-key mismatch:** `.env` `FIREBASE_CUSTOMER_RECAPTCHA_KEY` (`6Lf8zOst…`) ≠ this
      project's own `recaptchaParams` key (`6LcMZR0U…`, authoritative). Replace the `.env`
      value before any captcha-on (real-number) send.
- [ ] Vendor project not provisioned (no `FIREBASE_VENDOR_*` in `.env`) → vendor audience
      stays local; flipping it without creds fails closed by design (registry registers
      nothing).

### Live-path proof through the app (gate flip)

Flip = **process env only**: `OTP_PROVIDER_CUSTOMER=firebase` — never into
`platform-navdhya\.env` (test suites load `.env` and would aim every suite at live Google).

| Step | Result |
|---|---|
| `POST auth/signup` (T1) | `202` challenge issued — Firebase send succeeded |
| `POST auth/verify` (fixed test code) | `201` customer session — account created |
| `POST auth/otp purpose=login` (T0) | `202` challenge issued |
| challenge row after send | `provider=firebase-customer`, `code_hash` **NULL**, `provider_ref` len **162**, `status=sent`, `attempts=0` |
| `POST auth/login` (fixed test code) | `200` customer session |
| challenge row after verify | consumed (row removed) — no local code existed at any point |

Bonus fail-closed evidence: during the CA-bundle outage the same call returned the typed
`503 dependency_unavailable` envelope with **no** downgrade to local and **no** challenge row.

**Observability gap (S50 candidate):** provider outages are caught and rendered as the typed
envelope without `report()` — the CA outage produced zero log lines. The exception message
contains only symbolic parts (HTTP status + Google codes), so reporting it is PII-safe.

### Runbook — flipping the gate (production)

1. Console: Blaze plan; Phone sign-in enabled; SMS region policy allows the target region;
   test numbers registered (gate phase).
2. Boot env / secret manager (**not** `.env`): `OTP_PROVIDER_CUSTOMER=firebase`,
   `FIREBASE_CUSTOMER_PROJECT`, `FIREBASE_CUSTOMER_API_KEY`, optional
   `FIREBASE_CUSTOMER_RECAPTCHA_KEY` / `_TIMEOUT` / `_SERVICE_ACCOUNT`.
3. PHP must have a CA bundle (`curl.cainfo`/`openssl.cafile` pointing at a real
   `cacert.pem`).
4. Verify: harness L0–L6 against test numbers → flip → one send/verify round trip as in the
   proof table → keep the harness runbook output as evidence.
5. Vendor audience: repeat with `FIREBASE_VENDOR_*` twins once the second project exists.
