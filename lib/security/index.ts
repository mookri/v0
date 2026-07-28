// lib/security/index.ts
// Barrel export for the security module.
//
// This is the single import surface for the security helpers built
// up across PR 0.25 / 0.8. New security primitives should be added
// here (and to the relevant `__tests__`) so callers have one stable
// import path. The auto-generated `index.ts` keeps the API surface
// obvious from the IDE's "jump to definition" on `@/lib/security`.

export {
  sanitizePlainText,
  sanitizeMarkdown,
  sanitizeUrl,
  type SanitizeOptions,
} from "./sanitize";

export {
  InProcessRateLimiter,
  clientIp,
  type RateLimitConfig,
  type RateLimitResult,
} from "./rate-limit";

export {
  acquireScrollLock,
  _resetScrollLockForTests,
  type ScrollLockHandle,
  type AcquireOptions,
} from "./scroll-lock";

export {
  issueCsrfToken,
  validateCsrfToken,
  extractCsrfToken,
  CSRF_CONSTANTS,
  type CsrfTokenOptions,
  type CsrfTokenResult,
  type CsrfValidation,
} from "./csrf";
