# API Contract — Decoupled MSG91 Widget OTP Subsystem

**Status:** Active Contract  
**Scope:** Navdhya Customer Login/Signup, Profile Verifications, Step-Up MFA, and Partner Onboarding  
**Authority:** Laravel 12 Backend (`platform-navdhya/`)  
**Transport:** Next.js BFFs (`apps/web/app/api/auth/*`, `apps/customer/app/api/auth/*`, `apps/partner/app/api/*`)

---

## 0. Overview & Trust Boundaries

The OTP verification system is a decoupled, provider-swappable subsystem. Verification is driven in the browser via the MSG91 widget in custom-SDK mode (`exposeMethods: true`), eliminating vendor modal popups and routing all token redemption through the backend.

- **Laravel (`platform-navdhya`)** is the sole security authority: owns challenge persistence (`OtpChallengeManager`), rate limiting, compounding resend schedules, hourly caps, third-party token verification (`VerifiesOtpTokens`), and session issuance (`actor_sessions`).
- **BFF / Proxy Layer (`apps/*`)** provides thin same-origin endpoints forwarding requests with client IP and CSRF context.
- **Client Library (`@navdhya/otp`)** encapsulates widget loading, offscreen captcha initialization, and typed error handling across all frontend applications.
- **Session Issuance** is conducted via secure, HttpOnly HMAC bearer cookies (`__Host-navdhya-session` or `navdhya-session`).

---

## 1. Public Configuration

### `GET /api/auth/otp/config` (BFF) / `GET /api/v1/customers/auth/otp/config` (Laravel)

Returns public widget credentials and resend policy parameters needed by the frontend to initialize the SDK.

#### Response `200 OK`
```json
{
  "widget_id": "36616c6c7465737477696467",
  "token_auth": "36616c6c74657374746f6b656e",
  "resend": {
    "delays_seconds": [60, 180, 540],
    "max_resends": 3
  }
}
```

---

## 2. Issue Challenge (Fresh Code)

### `POST /api/auth/otp` (Login) or `POST /api/auth/signup` (Signup)

Creates an active challenge row in the database, applies hourly caps and rate limiting, and prepares the verification boundary.

#### Request Body (Login)
```json
{
  "identity": "+919876543210",
  "purpose": "login"
}
```

#### Request Body (Signup)
```json
{
  "phone": "+919876543210",
  "name": "Aarav Sharma"
}
```

#### Response `200 OK` (Login) or `201 Created` (Signup)
```json
{
  "data": {
    "challengeId": "01HX89Z8M5E0Q9W2K7R4T1Y6P3",
    "purpose": "login",
    "expiresIn": 1200
  }
}
```

#### Constraints:
- Maximum 3 fresh challenges per hour per phone number.
- Exceeding the fresh limit triggers a 600s cooldown with HTTP 429 and `Retry-After: 600`.

---

## 3. Verify Code & Redeem Access Token

### `POST /api/auth/login` or `POST /api/auth/verify`

The browser widget executes `verifyOtp(code)` client-side and produces a JWT access token. The client submits the token and `challengeId` to the backend for redemption.

#### Request Body
```json
{
  "challengeId": "01HX89Z8M5E0Q9W2K7R4T1Y6P3",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

#### Flow:
1. Laravel receives the payload and passes `token` to `Msg91Client::verify` (`/api/v5/widget/verifyAccessToken`).
2. MSG91 validates the JWT signature and returns the verified phone identifier (`"919876543210"`).
3. Laravel verifies that the attested identifier matches the normalized phone number stored on the challenge.
4. On match:
   - If user does not require MFA: marks challenge redeemed, creates `actor_session`, and returns HTTP 200 with session cookie.
   - If user has TOTP enrolled: marks first factor verified, returns HTTP 202 `mfa_required`.
5. On failure or wrong code: increments challenge attempts counter.

#### Response: Single Factor Authenticated `200 OK`
```json
{
  "data": {
    "status": "authenticated",
    "user": {
      "id": "usr_01HX8A...",
      "displayName": "Aarav Sharma"
    }
  }
}
```
**Headers:**
```http
Set-Cookie: __Host-navdhya-session=actor_tok_...; Path=/; HttpOnly; Secure; SameSite=Lax; Max-Age=86400
```

#### Response: MFA Required (Step-Up to TOTP) `202 Accepted`
```json
{
  "data": {
    "status": "mfa_required",
    "message": "Multi-factor authentication required"
  }
}
```

Subsequent TOTP submission:
```json
{
  "challengeId": "01HX89Z8M5E0Q9W2K7R4T1Y6P3",
  "totpCode": "654321"
}
```

---

## 4. Resend Code

### `POST /api/auth/otp/resend`

Requests approval for an OTP resend. Enforces compounding server-side delay.

#### Request Body
```json
{
  "challengeId": "01HX89Z8M5E0Q9W2K7R4T1Y6P3"
}
```

#### Response `200 OK`
```json
{
  "data": {
    "channel": "11",
    "channels": ["11", "4", "12"],
    "resends_left": 2,
    "next_allowed_at": "2026-10-14T01:15:00Z"
  }
}
```

#### Resend Rules:
- **Compounding Schedule:** Delay progresses `60s → 180s → 540s`.
- **Max Resends:** Capped at 3 per challenge.
- **Channels:** SMS (`11`), Voice call (`4`), WhatsApp (`12`).
- Upon receiving HTTP 200, the client invokes `retryOtp(channel)` on the widget SDK.

---

## 5. Attempt & Exhaustion Rules

- **Attempt Budget:** Each challenge permits a maximum of 3 attempts (`MAX_ATTEMPTS = 3`).
- **Exhaustion:** Upon the 3rd failed attempt, the challenge row is deleted immediately (destroyed server-side).
- **Fail-Closed:** No fabricated locks or client-side bypasses exist; the user must click "Request fresh code" to issue a new challenge (subject to the 600s / 3-per-hour limit).

---

## 6. Error Envelope & Status Codes

All errors return the standard error envelope:
```json
{
  "error": {
    "code": "AUTHENTICATION_FAILED",
    "message": "Invalid verification code.",
    "requestId": "req_01HX89S..."
  }
}
```

| HTTP | Code | Meaning |
|---|---|---|
| `400` | `INVALID_REQUEST` | Malformed parameters or missing challenge ID. |
| `401` | `UNAUTHENTICATED` | Token rejected by provider, or identifier mismatch. |
| `404` | `CHALLENGE_NOT_FOUND` | Challenge does not exist or was deleted due to attempt exhaustion. |
| `422` | `VALIDATION_FAILED` | Phone or input validation failure. |
| `429` | `RATE_LIMITED` | Cooldown in effect (resend or hourly cap). Returns `Retry-After` header. |
| `503` | `OTP_PROVIDER_UNAVAILABLE` | Third-party authority network error. Attempts are NOT consumed. |
