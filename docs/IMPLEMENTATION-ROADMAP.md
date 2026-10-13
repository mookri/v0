# Implementation Roadmap — Decoupled MSG91 Widget OTP Subsystem

**Status:** Completed (Phases 1–7 Delivered)  
**Execution Units:** `platform-navdhya` (Laravel 12), `packages/otp`, `apps/web`, `apps/customer`, `apps/partner`

---

## Overview

Complete migration from legacy Firebase phone authentication to a first-class, decoupled MSG91 OTP widget subsystem running in custom-SDK mode (`exposeMethods: true`) with central backend authority in Laravel.

---

## Phase 1 — Laravel Core Subsystem
- [x] Create `App\Platform\Identity\Otp\OtpChallengeManager`: centralized rate limiting, compounding resends (`60s → 180s → 540s`), hourly limits (3/hour, 600s cooldown), and attempt limits.
- [x] Create `App\Platform\Identity\Otp\DbOtpChallengeStore`: repository persisting `otp_challenges` central and tenant tables.
- [x] Create `App\Platform\Identity\Otp\Msg91Client` implementing `VerifiesOtpTokens`: REST client verifying tokens at `/api/v5/widget/verifyAccessToken`.
- [x] Create `App\Platform\Identity\Otp\Msg91WidgetProvider` implementing `OtpVerificationProvider`.
- [x] Update `AppServiceProvider`: bind `VerifiesOtpTokens` to `Msg91Client`, register `OtpProviderRegistry`, configure contextual bindings for `OtpChallengeStore`.

## Phase 2 — Laravel Consumers
- [x] Refactor `CustomerAuthController`: replace legacy phone verification with `OtpChallengeManager` (`issueChallenge`, `redeemToken`).
- [x] Refactor `CustomerVerificationController`: unify email and phone profile verifications under `OtpChallengeManager`.
- [x] Refactor Partner Onboarding `SubmitController`: integrate OTP challenge validation for vendor review and submission.
- [x] Clean up `config/services.php` and `config/customers.php`: drop Firebase configs, add MSG91 configs.

## Phase 3 — Shared `@navdhya/otp` TypeScript Package
- [x] Package structure with clean subpath exports: `@navdhya/otp/client`, `@navdhya/otp/widget`, `@navdhya/otp/resend`, `@navdhya/otp/schemas`.
- [x] `widget/loader.ts`: singleton script injection for `https://verify.msg91.com/otp-provider.js`, offscreen captcha container, custom-SDK mode (`exposeMethods: true`), promise bridges, typed `OtpWidgetError`.
- [x] `resend.ts`: `useResendCountdown` hook and formatting utilities.
- [x] `schemas.ts`: Zod schema validation for `/api/auth/otp/config`.

## Phase 4 — `apps/web` Consumer & Firebase Deletion
- [x] Delete `apps/web/lib/firebase/` and all client-side Firebase dependencies.
- [x] Create BFF route handlers: `/api/auth/otp/config`, `/api/auth/otp`, `/api/auth/otp/resend`, `/api/auth/login`, `/api/auth/verify`.
- [x] Refactor `OtpScreen.tsx`: consume `@navdhya/otp/widget`, handle 202 `mfa_required` step-up, 429 Retry-After, and 3-attempt exhaustion without fabricated locks.

## Phase 5 — `apps/customer` & `apps/partner` Adoption
- [x] Add `@navdhya/otp` workspace dependency to `apps/customer` and `apps/partner`.
- [x] Implement BFF route handlers `/api/auth/otp/config` in both apps.
- [x] Implement OTP review and submission dialogs in partner onboarding.

## Phase 6 — Comprehensive Verification & Testing
- [x] **Laravel Suite:** 818/818 tests passing in PHPUnit (`vendor/bin/phpunit`).
- [x] **PHPStan:** 0 errors in `app/Platform/Identity/Otp` and `app/Providers/AppServiceProvider.php`.
- [x] **`@navdhya/otp` Tests:** 10/10 tests passing in Vitest (`corepack pnpm --filter=@navdhya/otp test`).
- [x] **`apps/web` Tests:** 6/6 tests passing in Jest (`apps/web/components/auth/__tests__/OtpScreen.test.tsx`).
- [x] **Architecture Boundaries:** 4/4 tests passing in Vitest (`tests/architecture/communication-boundaries.test.ts`): zero Firebase traces, `verify.msg91.com` isolated strictly to `loader.ts`.
- [x] **E2E Harness:** Updated `e2e/ui.spec.ts` (intercepts widget SDK), `e2e/run.ps1` (boots MSG91 stub), and `e2e/msg91-stub.mjs`.

## Phase 7 — Architecture Documentation & Decommissioning
- [x] Published `adr/ADR-002-msg91-widget-otp.md` and `docs/adr/ADR-002-msg91-widget-otp.md`.
- [x] Marked `ADR-001` as SUPERSEDED.
- [x] Updated `docs/API-CONTRACT.md`, `docs/STATE-MACHINE.md`, `docs/SECURITY-AND-THREAT-MODEL.md`, `docs/ACCEPTANCE-TESTS.md`.
- [x] Deleted obsolete `firebase-spike-transcript.md` and `platform-navdhya/docs/04-security/firebase-phone-otp-spike.md`.
