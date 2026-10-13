# ADR-002: Decoupled, Provider-Swappable MSG91 Widget OTP Subsystem

- **Status:** Accepted
- **Date:** 2026-10-14
- **Deciders:** Navdhya Core Architecture Team
- **Technical Domains:** Identity, Security, Platform Monolith (`platform-navdhya`), `@navdhya/otp`, Web Apps (`apps/web`, `apps/customer`, `apps/partner`)
- **Supersedes:** ADR-001 (Backend-Owned Firebase Phone OTP Architecture)

---

## 1. Context & Problem Statement

Prior iterations relied on Firebase Phone Authentication (S50 kit). In production testing, Firebase phone delivery encountered high failure rates on Indian telcos, opaque reCAPTCHA attestation failures on custom domains, and forced client-side state coupling with Google Identity SDKs.

### Architectural Requirements:
1. **Total Firebase Removal:** Remove all Firebase SDKs, config, and tokens from every client, server, and CI workflow.
2. **First-Class Decoupled Subsystem:** The OTP verification system must be a decoupled subsystem reusable across customer login/signup, account verifications (email/phone), MFA step-up, and partner onboarding.
3. **Provider-Swappable:** MSG91 is the verification authority today, but the subsystem must be swappable for another vendor (Twilio, AWS SNS, etc.) via configuration and container re-binding, without touching call sites.
4. **Bespoke UX (Zero Vendor Popups):** The vendor's default UI modal must never appear. The MSG91 widget operates in custom-SDK mode (`exposeMethods: true`), allowing our branded UI components to drive input, errors, and timers.
5. **DB-Based Auth Core:** User credentials and sessions remain database-backed (`users`, `actor_sessions`, HMAC bearer cookies managed exclusively by Laravel).

---

## 2. Decision & Architecture

We have established a unified, decoupled OTP subsystem spanning Laravel (`platform-navdhya`) and a shared TypeScript library (`@navdhya/otp`).

### 2.1 Backend Authority (`platform-navdhya`)

1. **Central Challenge Manager (`OtpChallengeManager`):**
   - Single point of truth for challenge creation, resend schedules, and token redemption.
   - Enforces a compounding resend schedule: `60s → 180s → 540s` (maximum 3 resends).
   - Enforces an hourly fresh-challenge cap (maximum 3 fresh challenges per hour per identifier) and a 600s cooldown.
   - Enforces strict attempt limits (maximum 3 failed attempts, after which the challenge row is destroyed).
2. **Provider Inversion (`VerifiesOtpTokens` Port):**
   - `Msg91Client` implements `VerifiesOtpTokens`, calling MSG91's `/api/v5/widget/verifyAccessToken`.
   - Bound in `AppServiceProvider`. Any future provider swap only requires writing a new adapter implementing `VerifiesOtpTokens` and rebinding in the service container.
3. **Storage Portability (`OtpChallengeStore`):**
   - `DbOtpChallengeStore` encapsulates database persistence. Contextual binding permits central customer auth (`otp_challenges`) and tenant-scoped tables (e.g. partner onboarding) to share the same manager.
4. **Security & Validation:**
   - Client sends `{ challenge_id, token }`.
   - Laravel verifies the JWT token with the provider. The provider attests the verified phone identifier.
   - Laravel strictly compares the attested identifier with the challenge's bound identifier. Clients cannot forge identity claims.

### 2.2 Client Subsystem (`@navdhya/otp`)

1. **Singleton Widget Loader (`@navdhya/otp/widget`):**
   - Injects `https://verify.msg91.com/otp-provider.js` exactly once.
   - Mounts a hidden, offscreen captcha element (`captchaRenderId`) required by the SDK.
   - Configures `exposeMethods: true` to suppress vendor modals and expose SDK global functions (`sendOtp`, `retryOtp`, `verifyOtp`).
   - Wraps callback-based methods in standard Promises and maps failures into typed `OtpWidgetError` instances.
2. **Resend Timer Engine (`@navdhya/otp/resend`):**
   - `useResendCountdown` hook synchronizes UI countdowns with server-provided `next_allowed_at` timestamps.
3. **HTTP Client (`@navdhya/otp/client`):**
   - Lightweight proxy client fetching public widget config (`/api/auth/otp/config`).

### 2.3 Consumers

- `apps/web`: Customer signup, login, MFA step-up (`OtpScreen.tsx`).
- `apps/customer`: Mobile-first customer portal authentication and profile verification.
- `apps/partner`: Partner onboarding phone/document verification modal.
- `platform-navdhya`: Tenant admin step-up verification and user status changes.

---

## 3. Consequences

### Positive:
- **Zero Third-Party Vendor Popups:** Full control over UI styling, animation, accessibility, and micro-interactions.
- **Provider Independence:** Swapping providers requires no changes to frontend screens or business domain controllers.
- **Fail-Closed Security Posture:** Third-party tokens are validated server-to-server; attempts and rate limits are strictly enforced in the database.
- **Zero Firebase Overhead:** Complete removal of heavy client SDKs and security rule configurations.

### Operational:
- Requires `MSG91_AUTH_KEY`, `MSG91_WIDGET_ID`, `MSG91_TOKEN_AUTH`, and `MSG91_VERIFY_BASE_URL` in environment configuration.
