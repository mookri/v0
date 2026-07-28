// lib/auth/cookie-names.ts

/**
 * Single source of truth for cookie names used by the auth subsystem.
 * Previously the dev-role cookie string was duplicated in 3 places
 * (`lib/auth/mock-session.ts`, `lib/auth/mock-session-client.ts`,
 * `components/dev/DevRoleSwitcher.tsx`). Changing it in one place
 * silently broke the others — easy to miss in code review.
 *
 * IMPORTANT: renaming a cookie name is a breaking change for any
 * users/devices that had the old cookie set. Bump the version
 * suffix when doing a deliberate rename.
 */

export const DEV_ROLE_COOKIE = "navdhya-dev-role";

/** Session cookie name (placeholder for the real auth integration in PR 1). */
export const SESSION_COOKIE = "navdhya-session";

/** Consent cookie (placeholder for PR 1's cookie-consent work). */
export const CONSENT_COOKIE = "navdhya-consent";

/** Analytics opt-in cookie (mirrors PostHog's person-flag). */
export const ANALYTICS_OPT_IN_COOKIE = "navdhya-analytics-opt-in";

/** A/B experiment cohort cookie (set on first allocation). */
export const EXPERIMENT_COHORT_COOKIE = "navdhya-experiment-cohort";

/** Layout cache key cookie (PR 0.25 stable-seed). */
export const LAYOUT_SEED_COOKIE = "navdhya-seed";

/**
 * Valid dev role values. Anything outside this set is ignored by the
 * dev role switcher; in production the cookie is a no-op (the switcher
 * is gated behind `process.env.NODE_ENV !== "production"`).
 */
export const VALID_DEV_ROLES = [
  "User",
  "Pandit",
  "Vendor",
  "Manager",
  "Admin",
  "SuperAdmin",
] as const;

export type DevRole = (typeof VALID_DEV_ROLES)[number];

export function isValidDevRole(value: string | undefined | null): value is DevRole {
  if (!value) return false;
  return (VALID_DEV_ROLES as readonly string[]).includes(value);
}
