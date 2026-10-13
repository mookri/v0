# Security Architecture & Threat Model — Decoupled MSG91 Widget OTP Subsystem

**Status:** Active Specification  
**Scope:** Authentication Security, Decoupled OTP Subsystem, BFF Transport, and Session Boundaries  
**Authority:** Laravel 12 Backend (`platform-navdhya/`)

---

## 1. Trust Boundaries & Flow Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Untrusted Client Zone (Browser)                          │
│    - Renders bespoke, branded UI                            │
│    - Runs MSG91 widget in custom-SDK mode (exposeMethods)   │
│    - Submits entered code to widget SDK -> receives JWT     │
│    - Forwards { challengeId, token } to BFF                 │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTPS / JSON
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Transport Boundary (Next.js BFF Route Handlers)          │
│    - Edge / Serverless reverse proxy                        │
│    - Forwards client IP, CSRF tokens, and trace headers     │
│    - Preserves Set-Cookie headers verbatim                  │
└──────────────────────────────┬──────────────────────────────┘
                               │ Internal Network / HTTPS
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Application Authority (Laravel 12 Modular Monolith)      │
│    - Sole owner of OTP challenges & Actor Sessions          │
│    - Enforces rate limits, resend gates, & attempt caps     │
│    - Calls MSG91 verifyAccessToken to attest identifier     │
│    - Validates attested phone matches challenge phone       │
│    - Mints and signs __Host-navdhya-session HMAC cookies    │
└──────────────────────────────┬──────────────────────────────┘
                               │ Outbound HTTPS (REST API)
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. External Verification Authority (MSG91 Widget Service)   │
│    - Dispatches SMS / WhatsApp / Voice OTP                  │
│    - Validates user input and issues short-lived JWT token  │
│    - Validates JWT signature at /api/v5/widget/...          │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Threat Matrix & Mitigations

### 2.1 SMS Toll Fraud & Traffic Pumping
- **Threat:** Automated scripts repeatedly trigger OTP dispatches to exhaust SMS credit balances.
- **Mitigations:**
  1. **SDK Captcha Container:** The MSG91 widget requires an offscreen captcha container (`captchaRenderId`) and performs automatic bot detection.
  2. **Compounding Resend Delays:** Server enforces delays that compound exponentially: `60s → 180s → 540s`. Maximum 3 resends per challenge.
  3. **Hourly Fresh Challenge Cap:** Maximum 3 fresh challenges allowed per identifier per hour. Violations enforce a 600-second cooldown with HTTP 429.
  4. **Throttle Middleware:** Direct backend endpoints are gated by `throttle:10,1` per IP.

### 2.2 Client Identity Forgery & Token Injection
- **Threat:** An attacker inputs user A's phone number, receives a verification code on their own device, and attempts to authenticate user A.
- **Mitigations:**
  - **Server-to-Server Attestation:** The client cannot supply a phone number to `/login` or `/verify`. The client provides only `{ challengeId, token }`.
  - **Strict Byte-for-Byte Matching:** Laravel requests token verification from MSG91. MSG91 extracts the phone number encrypted in the token. Laravel asserts `attested_phone === challenge.identifier`. Any mismatch immediately rejects the request.

### 2.3 Brute-Force Code Enumeration
- **Threat:** An attacker attempts to guess a 6-digit OTP code through rapid requests.
- **Mitigations:**
  - **3-Attempt Hard Ceiling:** Challenges allow at most 3 failed attempts (`max_attempts = 3`).
  - **Server-Side Destruction:** On the 3rd incorrect attempt, the challenge row is completely deleted from the database.
  - **Unguessable Challenge IDs:** Challenge identifiers use 128-bit ULIDs.

### 2.4 Account Enumeration & Information Leakage
- **Threat:** Attackers submit phone numbers to determine if an account exists.
- **Mitigations:**
  - **Uniform Response Envelopes:** Authentication failures return identical HTTP 401 error payloads regardless of whether the account exists, is unverified, or is suspended.
  - **Zero Leakage:** No user IDs or existence flags are returned until authentication is complete.

### 2.5 Session Hijacking & Token Theft
- **Threat:** Cross-site scripting (XSS) steals authentication tokens from browser storage.
- **Mitigations:**
  - **HttpOnly Cookies Only:** Actor sessions are never stored in `localStorage` or returned in JSON bodies. They are stored in `__Host-navdhya-session` cookies (`HttpOnly; Secure; SameSite=Lax`).
  - **Cryptographic Signatures:** Actor session IDs are validated using HMAC-SHA256 signatures (`IDENTITY_HMAC_SECRET`).

---

## 3. Secret Management & Key Isolation

| Key / Secret | Storage Location | Accessible By | Scope / Exposure |
|---|---|---|---|
| `MSG91_AUTH_KEY` | Server Environment / Vault | Laravel Only | Private (Full MSG91 Account Authority) |
| `MSG91_WIDGET_ID` | Server & BFF Config | Laravel, BFF, Public | Public Widget Identifier |
| `MSG91_TOKEN_AUTH` | Server & BFF Config | Laravel, BFF, Public | Public Client Token Auth |
| `MSG91_VERIFY_BASE_URL` | Server Environment | Laravel Only | Internal verification base URL |
| `IDENTITY_HMAC_SECRET` | Server Environment / Vault | Laravel Only | Private session HMAC signing key |

---

## 4. Fail-Closed Posture

- In any failure condition (database connectivity loss, provider timeout, network error), the subsystem refuses authentication.
- Third-party provider timeouts or 5xx responses trigger `OtpProviderUnavailableException` (HTTP 503). This is treated as a dependency failure: user attempts are **not** burned, and retry arms are cleanly surfaced to the user.
