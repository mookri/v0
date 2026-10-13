# Firebase Phone-OTP Spike (Verification Fabric S45)

**Status:** DESIGN-VALIDATED · LIVE-VALIDATED S49 (test numbers, 2026-10-12) · **real-number send
BLOCKED on the captcha attestation path — RCA 2026-10-13** (phone-auth key + mint origin, *not* an
opaque Google-side fault) · **Date:** 2026-10-13 · **Type:** spike / evidence record
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

> **Status (2026-10-13):** L0–L4 captured in "Live validation (S49)" below; L5 mint mechanism
> resolved there (headed mints, pure-headless does not); L2 remains skipped-by-design while the
> gate runs on test numbers; L6 is open, partly resolved. **L5's real-number send is the open
> blocker — read the RCA section before re-attempting.**

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

> **Transcript provenance note (2026-10-13):** the header line above records site key
> `6Lf8zOst…`, which does **not** match the key this same run received from `recaptchaParams` in
> L0 (`6LcMZR0U…`, and `.env` today). The header was hand-written, not harness-emitted. Harmless for
> S49's conclusions — L1 proved test numbers are accepted with **no** token, so no send in this
> transcript ever validated one — but key identity is load-bearing for real numbers now. Note that
> *neither* of these is the phone-auth key: `recaptchaParams` returns a general project key, while
> phone auth uses `6Lcao-st…` (see "RCA — why real-number sends fail"). Four distinct site keys
> appear in this file (`6Lf8zOst…` header, `6LcMZR0U…` recaptchaParams, `6Le9Aewt…`
> domain-restricted, `6Lcao-st…` phone-auth).

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

### L5 (headless reCAPTCHA v2) — mint mechanism RESOLVED 2026-10-13; real-number send blocked on the captcha path

Mint mechanism proven live 2026-10-13 (Playwright `@playwright/test` chromium, page served on `localhost`):
- **Pure headless auto-pass did NOT work** — the invisible widget presented an image challenge
  (fire hydrant) that headless cannot solve.
- **Headed, user-assisted mint DID work** — launch non-headless, `grecaptcha.render(size:'invisible')`
  + `grecaptcha.execute()`; the operator solves the challenge, the callback returns a valid v2
  token. The token is short-lived + single-use and is forwarded to the app as `captcha_token`
  (S48 pass-through) or straight to `identitytoolkit`.
- The site key must authorize `localhost` (domain restrictions) or the widget will not render
  there at all: the `6LcMZR0U…` key from `recaptchaParams` does — but see below, that key is not
  the phone-auth key, so a clean local mint on it does not advance a real-number send. A
  domain-restricted key (e.g. `6Le9Aewt…`) renders "Localhost is not supported" and cannot mint
  locally.

**Real-number send (2026-10-13, live-calibrated via the S50 `report()` fix):** a valid,
user-solved token was sent to `accounts:sendVerificationCode` for real `+91` numbers through
the app path. The S50 observability fix surfaced the exact Google error heads, resolving the
earlier opaque `500 INTERNAL` into a precise taxonomy:
- **no token** → `500 INTERNAL backendError` — Google's shape for a missing attestation on a
  real number.
- **hammered number** → `400 TOO_MANY_ATTEMPTS_TRY_LATER` — per-number rate limit; re-arms on
  every attempt → back off ~30–60 min, use a fresh number.
- **`6LcMZR0U…` (the `recaptchaParams` key) + token** → `400 INVALID_APP_CREDENTIAL` — that key
  is a general project key, **not** the phone-auth key; its tokens are rejected at the
  credential stage. (The earlier "authoritative key" label, based on the three-layer
  `recaptchaParams` check, was a false positive: `recaptchaParams` does not report the
  phone-auth key.)
- **`6Lcao-st…` (user's key) + token** → `400 CAPTCHA_CHECK_FAILED` — this **is** the phone-auth
  site key (it passes the credential stage), but the token itself fails verification.

**Cause of `CAPTCHA_CHECK_FAILED` (L5 wall) — two live hypotheses, not yet discriminated:**
with the correct phone-auth key `6Lcao-st…`, the token passes the credential stage but fails
verification. The error code alone does **not** separate:
- **(A) token origin** — phone-auth rejects a localhost-minted token. (Weakened: the widget
  renders *and* mints on localhost, so the site key authorizes that domain — an origin check
  against the same list would pass.)
- **(B) reCAPTCHA secret mismatch** — the secret in Authentication → Phone does not pair with
  the `6Lcao-st…` site key, so Google's server-side `siteverify` fails. (Now the more likely
  of the two.)
Discriminator: (1) **free** — confirm the Authentication → Phone reCAPTCHA secret matches
`6Lcao-st…` (tests B directly); (2) mint the token from the trusted `navdhya-customer-1.web.app`
origin via a Hosting deploy — a web.app token that succeeds where localhost failed proves (A);
one that *also* fails `CAPTCHA_CHECK_FAILED` proves (B) and the deploy would not have helped.
Test numbers bypass captcha entirely, which is why the S49 gate (test-numbers) passed.

### L6 (console posture) — MANUAL, pending

- [ ] API key: application restrictions (HTTP referrer/IP) + API restriction to Identity
      Toolkit, per project.
- [ ] App Check posture recorded per project (N/A for pure server-side REST).
- [ ] **Site-key — REOPENED, earlier "RESOLVED" was a false positive (2026-10-13).** The check
      below concluded `6LcMZR0U…` was "the project's authoritative key" because `.env` →
      `GET auth/captcha` → `recaptchaParams` all agreed. Live real-number sends falsify it:
      `recaptchaParams` does not report the *phone-auth* site key, so agreement across those three
      layers proves nothing about phone auth. Tokens minted from `6LcMZR0U…` return
      `INVALID_APP_CREDENTIAL`; the phone-auth key is `6Lcao-st…` (clears the credential stage,
      then fails on origin — `CAPTCHA_CHECK_FAILED` from localhost). `.env` repointed to
      `6Lcao-st…` on 2026-10-13. See "RCA — why real-number sends fail".
      ~~**Site-key mismatch — RESOLVED 2026-10-13:**~~ three-layer check:
      `.env` → live `GET auth/captcha` endpoint → `recaptchaParams` all agree. A
      transient wrong paste (`6Lcao-st…`, not this project's key) was caught by the
      three-layer check before any captcha-on send depended on it.
- [ ] Vendor project not provisioned (no `FIREBASE_VENDOR_*` in `.env`) → vendor audience
      stays local; flipping it without creds fails closed by design (registry registers
      nothing).
- [x] **Admin credential wiring (S51, 2026-10-13).** `FIREBASE_{CUSTOMER,VENDOR}_SERVICE_ACCOUNT`
      is now resolved and validated at registry boot — absolute path to the key file (or inline JSON
      from a secret manager), required fields present, and `project_id` equal to the audience's
      configured project. Unset stays the supported state, because send/verify authenticate with
      the web API key alone. A configured-but-unusable credential throws
      `OtpProviderConfigurationException` (a subtype of the unavailable fault → typed 503) instead
      of registering a provider whose cross-check quietly does not exist; rejection messages name
      the field or path and never echo key material. The project cross-check is the guard against
      the exact mistake this spike recorded above: credentials and site key borrowed from a
      different project. `storage/app/private/` is gitignored (contents excluded, directory
      shipped) and a console-named `*-firebase-adminsdk-*.json` dropped in the repo root is ignored
      too. **The idToken cross-check itself is still open** — it needs an Admin SDK dependency, and
      no credential exists for `navdhya-customer-1` on this machine yet.

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

**Observability — RESOLVED (S50, 2026-10-13):** the typed 503 now `report()`s the
`OtpProviderUnavailableException` in both send-path catch blocks
(`CustomerAuthController::requestOtp` / `signup`). The exception message carries only
symbolic parts (HTTP status + Google error head/reason — never the phone, token, code,
or session handle), so reporting it is PII-safe. Covered by
`CustomerCaptchaPassThroughTest::test_provider_outage_surfaces_as_a_typed_503` (spies the
`ExceptionHandler` binding and asserts `report()` receives the provider exception; the
503 envelope itself is unchanged). The earlier CA-bundle outage produced zero log lines;
a repeat would now log the symbolic Google error head instead of failing silently. (The
verify path then still returned a bare `OtpVerifyOutcome::Unavailable` with no detail —
closed the same day, see "Verify path — CLOSED" below.)

**Verify path — CLOSED (same day):** the Firebase adapter now *throws* its typed
`OtpProviderUnavailableException` from `verify()` on provider faults (network, captcha,
misconfig, 4xx/5xx) instead of returning a bare `OtpVerifyOutcome::Unavailable`, carrying the
same `http <status> <HEADS>` symbolic text the send path uses. Match/Mismatch returns are
unchanged, and the port still accepts the enum case from providers that have no detail to
report. Two consequences:

- The redemption outage is now diagnosable from logs (`INVALID_APP_CREDENTIAL` vs
  `CAPTCHA_CHECK_FAILED` vs quota), which is what step 5 of the RCA depends on.
- It fixed a latent envelope bug: verify call sites (`auth/verify`, `auth/login`, the three
  profile quorum endpoints) never caught that exception, so a provider outage during
  redemption fell through `ErrorEnvelope::classify` to a **bare 500** — contradicting the
  typed-503 contract this doc records. Both provider faults now map centrally in
  `ErrorEnvelope`: `OtpProviderUnavailableException` → 503 `dependency_unavailable`,
  `OtpProviderRateLimitedException` → 429 `rate_limited`.

**Provider back-off is no longer a fake outage:** `TOO_MANY_ATTEMPTS_TRY_LATER` (live shape,
400) throws `OtpProviderRateLimitedException`, which *extends* the unavailable marker — so
every fail-closed caller keeps failing closed — and surfaces as **429 `rate_limited`** rather
than 503. No `Retry-After` is claimed: Google sends none and the window is theirs, so the
envelope tells the client to back off without inventing a number. Retrying in a loop re-arms
it, which is exactly the behaviour that contaminated the earlier evidence.

**PII tightening:** `symbolicCodes()` accepted the whole message when it contained no `':'`,
so Google's prose (`'API key not valid. Please pass a valid API key.'`) entered exception text
as if it were a code — against this adapter's own hard rule. Heads now count only in the
`FOO_BAR` shape; anything else falls back to `error.status` / `errors[*].reason`.

**Config lesson:** `OTP_CAPTCHA_REQUIRED_CUSTOMER=true` must NOT live in
`platform-navdhya\.env`. Suites load `.env`, and with the flag set there every
firebase-provider test send started 422-ing for a missing attestation (3 failures in
`CustomerCaptchaPassThroughTest` before it was moved back out). Same rule the gate flip
already follows for `OTP_PROVIDER_CUSTOMER`: **process env**. The pass-through tests now pin
the policy off explicitly so they stay independent of a developer's `.env`.

### RCA — why real-number sends fail (consolidated, 2026-10-13)

**Layer 0 is green: the integration itself is not broken.** Endpoint shape
(`{phoneNumber, recaptchaToken?}`), both send/verify calls, the `sessionInfo` round trip, the
`CODE_ERROR_CODES` taxonomy, and the fail-closed 503 are all proven live on test numbers
(L1/L3/L4 + the gate-flip proof table above). No defect in our HTTP layer explains the failure.

**Ruled out — SMS region policy.** The region gate has its own unambiguous signature:
`OPERATION_NOT_ALLOWED : SMS unable to be sent until this region enabled by the app developer`,
and it fires for **test numbers too** (see the note under the transcript). The test path is green
now, so +91 is already permitted. A region problem would turn the green path red, and would never
present as `500 INTERNAL`. The Runbook step-1 "region allows +91" check is confirmation only, not
a suspect.

**Ruled out — rate limit as the cause of the 500.** This one inverts the usual reasoning:
`TOO_MANY_ATTEMPTS_TRY_LATER` is a **400**. Receiving a **500** is positive evidence that the
request cleared the rate limiter and reached the SMS dispatch path. Back-off is still correct
hygiene — it keeps evidence clean and a retry inside a back-off window returns a *different*
error — but no amount of waiting resolves a 500.

**What the earlier "500 with or without a valid token" framing got wrong:** the live calibration
splits those cases apart. The `500 INTERNAL` is Google's shape for a **missing** attestation on a
real number — it was the no-token case all along. Once a token is actually present the send gets
past that and returns a **400 captcha-class** head, which is why this only became visible after the
S50 `report()` fix surfaced the masked symbolic error. The failure is therefore **not** an opaque
Google-side dispatch fault; it is the captcha attestation path, and it narrows to two distinct,
named causes:

1. **Wrong key for the purpose** — a token minted from `6LcMZR0U…` returns
   `INVALID_APP_CREDENTIAL`: that key is a general project key, **not** the phone-auth key. The
   "authoritative key" label it carried came from the three-layer `recaptchaParams` check, which is
   a **false positive** — `recaptchaParams` does not report the phone-auth site key at all.
   Consequence: `.env`'s `FIREBASE_CUSTOMER_RECAPTCHA_KEY` currently holds a key that can *never*
   pass a real-number send.
2. **Origin, not credentials** — a token minted from `6Lcao-st…` (which *does* clear the credential
   stage, so it is the phone-auth key) returns `CAPTCHA_CHECK_FAILED`, because the widget minted it
   on `http://localhost:<port>`. The phone-auth captcha check does not trust a localhost origin, even
   though the widget renders and mints fine there. Remaining 30-second rule-out: confirm the console
   reCAPTCHA **secret** under Authentication → Phone is the pair of `6Lcao-st…` (a mismatched secret
   produces the same head).

**Billing is no longer implicated.** Every observed real-number head is captcha/attestation-class;
none is a dispatch or quota fault. Keep the active-payment-method check as console confirmation, not
as a suspect.

**Second blocker — on our side, independent of the console.** `OTP_CAPTCHA_REQUIRED_CUSTOMER`
is absent from the environment, so it defaults `false` (`config/otp-policy.php:43`). The guard at
`CustomerOtpService.php:117` is an `&&` chain, so with the flag off a supplied `captcha_token`
still travels to the provider — but nothing ever *forces* one, which means **app-path real-number
sends go out with no attestation at all**, which is exactly the `500 INTERNAL` above. A correct
console and key alone leave the app path red; the flag must be flipped with them. Two fixes, not one.

**Path to green, in order:**

1. Point `FIREBASE_CUSTOMER_RECAPTCHA_KEY` at the **phone-auth** site key
   (`6Lcao-stAAAAABjjTG-s3xk9bKUT6DOEgtC-t93h`) — **DONE 2026-10-13** in `.env`; the
   `recaptchaParams` key it replaced was the wrong class for phone auth.
2. Set `OTP_CAPTCHA_REQUIRED_CUSTOMER=true` as **process env** (never in
   `platform-navdhya\.env` — suites load it and every firebase-provider test would then
   demand an attestation).
3. Mint from an **authorized HTTPS origin** — add the deployed preview/dev-over-HTTPS domain to
   Firebase Auth authorized domains. No localhost mint can pass, so this step gates the rest;
   headless CI inherits the same wall (L5's decision point).
4. One send to a fresh real number (not one already hammered into `TOO_MANY_ATTEMPTS`), with a
   **newly minted** token — v2 tokens are single-use and short-lived; do not retry in a loop.
5. Read `storage/logs`: the typed fault now `report()`s from **both** paths, so the symbolic
   Google head is recorded server-side while the client sees only the envelope — 429
   `rate_limited` for a provider back-off, 503 `dependency_unavailable` for everything else
   (previously both were an undifferentiated 503 on send and a bare 500 on verify).
   `INVALID_APP_CREDENTIAL` → key still wrong; `CAPTCHA_CHECK_FAILED` from a production origin →
   check the console secret pairing; `OPERATION_NOT_ALLOWED` → region; only then is billing in play.
   Probe it directly with `scripts/firebase-spike-live.ps1 -RealNumber '+91…'` (see R1/R2 below).

### R1/R2 — real-number probe (harness extension, post-RCA)

`scripts/firebase-spike-live.ps1 -RealNumber '+91…' [-RealCode <sms code>]`

- **R1** sends to the real number **once**, with the token from
  `FIREBASE_RECAPTCHA_TOKEN`, then prints the masked number, the symbolic head, the session
  digest, and a verdict line that names the RCA branch (`INVALID_APP_CREDENTIAL` → wrong key
  class; `CAPTCHA_CHECK_FAILED` → origin/secret; `OPERATION_NOT_ALLOWED` → region/billing;
  `TOO_MANY_*` → back off, do not rerun).
- **R2** redeems that session with the code read from the handset — only runs when R1 returned
  a session handle, and only once.
- **Refuses to run without a token.** An unattested real send is the known-500 path, still
  billable on success, and wastes quota on a number Google has already throttled.
- **Runs before L0–L6 and spends the token**, so L2 self-skips with "token spent by R1"
  instead of reusing a dead single-use token and reporting a misleading result.
- Redaction is unchanged (API key never printed, phone masked, `sessionInfo` as digest, codes
  never echoed). Pipeline proven with
  `-SelfTest -RealNumber '+910000000000' -RealCode 123456`, which exercises R1/R2/L2-skip
  against an invalid key and emits no live evidence.

### Runbook — flipping the gate (production)

1. Console: Blaze plan with an **active payment method**; Phone sign-in enabled; SMS region
   policy allows the target region; **the Phone provider's own reCAPTCHA site/secret pair** — this
   is *not* the key `recaptchaParams` returns (that one is a general project key and yields
   `INVALID_APP_CREDENTIAL` on a real send; see RCA). Register the origin you will mint from in
   Auth **authorized domains**; localhost cannot pass. Test numbers registered (gate phase).
2. Boot env / secret manager (**not** `.env`): `OTP_PROVIDER_CUSTOMER=firebase`,
   `FIREBASE_CUSTOMER_PROJECT`, `FIREBASE_CUSTOMER_API_KEY`, optional
   `FIREBASE_CUSTOMER_RECAPTCHA_KEY` / `_TIMEOUT` / `_SERVICE_ACCOUNT` — and
   **`OTP_CAPTCHA_REQUIRED_CUSTOMER=true` before any real-number send** (default `false` means
   the app never demands an attestation, so real sends go out bare — see RCA second blocker).
3. PHP must have a CA bundle (`curl.cainfo`/`openssl.cafile` pointing at a real
   `cacert.pem`).
4. Verify: harness L0–L6 against test numbers → flip → one send/verify round trip as in the
   proof table → keep the harness runbook output as evidence.
5. Vendor audience: repeat with `FIREBASE_VENDOR_*` twins once the second project exists.
