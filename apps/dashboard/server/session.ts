import { federationConfig, isSecureContext } from '@/server/federation';
import { verifyAccessToken } from '@/server/oidc';
import { isSidTerminated } from '@/server/backchannel-logout';

// Federated session (ADR-0020 cutover) — dashboard BFF.
//
// The cookie carries the ISSUER'S ACCESS TOKEN (same contract as
// customer/web/partner). The browser can never read it (HttpOnly), and
// nothing ever trusts it unverified: /api/auth/me re-verifies it
// against the issuer JWKS (server/oidc) on every boot, then confirms
// against the issuer's userinfo endpoint before the app renders an
// authenticated screen.
//
// Cookie policy parity with customer's backend-session.ts: dev runs on
// HTTP, where a `__Host-` cookie is silently dropped by the browser —
// so dev uses the unprefixed name and the production name is always
// read too (a session minted under either still resolves).
const DEV_TOKEN_COOKIE = 'nv_dashboard_token';
const PROD_TOKEN_COOKIE = '__Host-nv_dashboard_token';
const LEGACY_TOKEN_COOKIES = [DEV_TOKEN_COOKIE, PROD_TOKEN_COOKIE];

export function sessionCookieName(): string {
  return isSecureContext() ? PROD_TOKEN_COOKIE : DEV_TOKEN_COOKIE;
}

export function buildSessionCookie(token: string, maxAgeSeconds: number): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${sessionCookieName()}=${encodeURIComponent(token)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${Math.floor(maxAgeSeconds)}${secure}`;
}

export function clearSessionCookie(): string[] {
  const secure = isSecureContext() ? '; Secure' : '';
  // Clear both names: a session minted under the other context (dev vs
  // prod) must not outlive the logout that asked it gone.
  return LEGACY_TOKEN_COOKIES.map(
    (name) => `${name}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`,
  );
}

// RP-initiated logout correlation cookie (A-SEC-004-6 landing): the
// logout/start route mints the state, parks it here for the
// end-session round-trip (10 min covers the OP redirect chain), and
// the landing route correlates it before acknowledging the return.
// Non-credential — it gates an acknowledgment page, never an actor.
const DEV_LOGOUT_STATE_COOKIE = 'nv-dashboard-logout-state';
const PROD_LOGOUT_STATE_COOKIE = '__Host-nv-dashboard-logout-state';
const LOGOUT_STATE_MAX_AGE_SECONDS = 600;

export function logoutStateCookieName(): string {
  return isSecureContext() ? PROD_LOGOUT_STATE_COOKIE : DEV_LOGOUT_STATE_COOKIE;
}

export function buildLogoutStateCookie(state: string): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${logoutStateCookieName()}=${encodeURIComponent(state)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${LOGOUT_STATE_MAX_AGE_SECONDS}${secure}`;
}

export function clearLogoutStateCookie(): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${logoutStateCookieName()}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
}

export function readLogoutStateCookie(cookieHeader: string | null): string | null {
  if (!cookieHeader) return null;
  for (const part of cookieHeader.split(';')) {
    const eq = part.indexOf('=');
    if (eq === -1) continue;
    if (part.slice(0, eq).trim() === logoutStateCookieName()) {
      return decodeURIComponent(part.slice(eq + 1).trim());
    }
  }
  return null;
}

/** Read the session token — accepts either cookie name. */
export function readSessionToken(cookieHeader: string | null): string | null {
  if (!cookieHeader) return null;
  for (const name of LEGACY_TOKEN_COOKIES) {
    const match = cookieHeader.match(new RegExp(`(?:^|;\\s*)${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`));
    if (match) {
      const decoded = decodeURIComponent(match[1]);
      if (decoded !== '') return decoded;
    }
  }
  return null;
}

/**
 * Verify the session cookie's issuer access token (fail-closed: null
 * on any verification failure). federationConfig() is allowed to
 * THROW — a federated deployment missing its issuer/client config must
 * surface as a loud failure, not a quiet 401 that reads like a bad
 * login.
 */
export async function verifiedSessionClaims(token: string) {
  const config = federationConfig();
  const claims = await verifyAccessToken(token, config.issuer, config.clientId);
  if (!claims) return null;
  // A-SEC-004-6: a sid the OP ended over the back channel stops
  // resolving here — the cookie is still validly signed and unexpired,
  // but the session behind it is gone. Session-bound mints embed
  // `sid`; a token without one has no lineage to terminate.
  if (typeof claims.sid === 'string' && claims.sid !== '' && isSidTerminated(claims.sid)) return null;
  return claims;
}
