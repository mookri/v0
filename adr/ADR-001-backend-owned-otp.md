# ADR-001: Backend-Owned Firebase Phone OTP Architecture

- **Status:** SUPERSEDED by [ADR-002: Decoupled MSG91 Widget OTP Subsystem](ADR-002-msg91-widget-otp.md)
- **Date:** 2026-10-13 (Superseded 2026-10-14)
- **Deciders:** Navdhya Core Architecture Team
- **Technical Domains:** Identity, Security, Platform Monolith (`platform-navdhya`), Web Client (`apps/web`)

---

## 1. Context & Problem Statement

In the legacy architecture (S50 kit), the browser client directly loaded the Firebase JavaScript SDK (`firebase/app` and `firebase/auth`), executed the SMS round-trip via `signInWithPhoneNumber`, confirmed the code in the browser, and exchanged the resulting Firebase ID token with Laravel (`/customers/auth/firebase/exchange`).

While functional, this approach introduced critical architectural weaknesses:
1. **Split Security Boundary:** The browser handled Firebase credentials (`sessionInfo`, `idToken`, and `refreshToken`), creating an untrusted client attack surface.
2. **Client Bundle Weight & Complexity:** Importing Firebase Auth in the Next.js client added bundle weight and complex lazy-loading harnesses to avoid test pollution.
3. **Pre-SMS Abuse Exposure:** The backend had limited visibility and control over client-initiated SMS sends, increasing risk of SMS pumping / toll fraud.
4. **Token Verification Latency:** The client-to-backend token exchange required subsequent verification against Google servers after the user had already waited for browser verification.

---

## 2. Decision

We will **move all Firebase phone OTP challenge lifecycle operations exclusively to Laravel**.

1. **Client Isolation:**
   - The browser will **not** import `firebase/app` or `firebase/auth`.
   - The client will interact solely with Navdhya's API (`/api/auth/firebase/*`).
   - The client is responsible only for presenting the user's phone number, rendering a standard Google reCAPTCHA widget (v2 or Enterprise), and supplying the 6-digit OTP code.

2. **Backend Ownership:**
   - Laravel acts as the sole orchestrator calling Google Identity Platform REST APIs:
     - `POST /v1/accounts:sendVerificationCode?key=<API_KEY>`
     - `POST /v1/accounts:signInWithPhoneNumber?key=<API_KEY>`
   - Firebase `sessionInfo` is encrypted at rest in the central database (`firebase_phone_auth_intents`) and never returned to the browser.
   - The returned Firebase ID token is verified server-side using Kreait Admin SDK (`Kreait\Firebase\Factory->createAuth()->verifyIdToken()`).
   - The verified phone number is bound to the intent, checked against Navdhya account and MFA policies, and converted into an actor session using `ActorTokenIssuer`.

3. **Thin Transport BFF:**
   - Next.js Route Handlers serve as transparent reverse proxies, preserving request tracing headers and forwarding `Set-Cookie` (`__Host-navdhya-session`).

4. **Multi-Audience Isolation:**
   - Customer and vendor audiences remain pinned to separate Firebase projects, validated at application boot.

---

## 3. Consequences

### Positive Consequences
- **Elimination of Client Credentials:** The browser never receives Firebase `sessionInfo`, ID tokens, refresh tokens, or raw actor tokens.
- **Dramatically Lighter Client Bundle:** Next.js application no longer bundles or dynamically loads the Firebase SDK on auth routes.
- **Centralized Abuse Prevention:** Rate limiting, IP throttling, and mandatory CAPTCHA evaluation occur server-side *before* any SMS is dispatched.
- **Unified Identity Governance:** Navdhya maintains 100% control over account existence, tenant membership, lockout counters, and MFA step-up.
- **Clean Testability:** Backend unit and feature tests can mock Identity Toolkit REST calls and Kreait verification without headless browser dependencies.

### Negative / Trade-Offs
- **Backend Latency on Verification:** Laravel performs two sequential external calls during code verification (Identity Toolkit REST verification + Kreait revocation/cert check), mitigated by aggressive filesystem caching of Google x509 public keys.
- **Concurrency Management:** The backend must manage atomic compare-and-set leases across MySQL and SQLite to block concurrent verification race conditions.

---

## 4. Compliance & References
- Identity Toolkit `sendVerificationCode`: `https://docs.cloud.google.com/identity-platform/docs/reference/rest/v1/accounts/sendVerificationCode`
- Identity Toolkit `signInWithPhoneNumber`: `https://docs.cloud.google.com/identity-platform/docs/reference/rest/v1/accounts/signInWithPhoneNumber`
- Google reCAPTCHA Enterprise: `https://docs.cloud.google.com/identity-platform/docs/recaptcha-enterprise`
- Kreait Firebase PHP SDK: `https://firebase-php.readthedocs.io/8.6.0/`
- Navdhya Spike Document: `platform-navdhya/docs/04-security/firebase-phone-otp-spike.md`
