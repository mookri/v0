/**
 * Session cookie policy — `auth/p0-contract.md` §7.
 *
 * Production (HTTPS):  `__Host-` prefix + Secure + HttpOnly + SameSite=Lax
 * Local dev (HTTP):    intentional separate policy — no `Secure`, no
 *                      `__Host-` prefix (a `__Host-` cookie over HTTP is
 *                      silently dropped by the browser, which reads like a
 *                      broken login).
 *
 * Dev must never be a malformed attempt at `__Host-`.
 */

const PROD_COOKIE = '__Host-navdhya-session';
const DEV_COOKIE = 'navdhya-session';

function isSecureContext(): boolean {
  // Explicit override wins so CI/staging can force either policy.
  const override = process.env.SESSION_COOKIE_SECURE;
  if (override === '1') return true;
  if (override === '0') return false;
  return process.env.NODE_ENV === 'production';
}

export function sessionCookieName(): string {
  return isSecureContext() ? PROD_COOKIE : DEV_COOKIE;
}

/** Legacy name pre-`__Host-`; read but always rewritten to current name. */
export const LEGACY_COOKIE_NAME = 'navdhya-session';

export function setSessionCookie(token: string, maxAgeSeconds: number): string {
  const secure = isSecureContext();
  const maxAge = Math.max(60, Math.floor(maxAgeSeconds));
  return [
    `${sessionCookieName()}=${token}`,
    'Path=/',
    'HttpOnly',
    'SameSite=Lax',
    `Max-Age=${maxAge}`,
    // `Secure` is required for `__Host-`; omit only on intentional dev HTTP.
    ...(secure ? ['Secure'] : []),
  ].join('; ');
}

export function clearSessionCookie(): string {
  const secure = isSecureContext();
  return [
    `${sessionCookieName()}=`,
    'Path=/',
    'HttpOnly',
    'SameSite=Lax',
    'Max-Age=0',
    ...(secure ? ['Secure'] : []),
  ].join('; ');
}

// RP-initiated logout correlation cookie (A-SEC-004-6 landing): the
// logout/start route mints the state, parks it here for the
// end-session round-trip (10 min covers the OP redirect chain), and
// the landing route correlates it before acknowledging the return.
// Non-credential — it gates an acknowledgment page, never an actor.
const DEV_LOGOUT_STATE_COOKIE = 'nv-web-logout-state';
const PROD_LOGOUT_STATE_COOKIE = '__Host-nv-web-logout-state';
const LOGOUT_STATE_MAX_AGE_SECONDS = 600;

export function logoutStateCookieName(): string {
  return isSecureContext() ? PROD_LOGOUT_STATE_COOKIE : DEV_LOGOUT_STATE_COOKIE;
}

export function buildLogoutStateCookie(state: string): string {
  const secure = isSecureContext();
  return [
    `${logoutStateCookieName()}=${encodeURIComponent(state)}`,
    'Path=/',
    'HttpOnly',
    'SameSite=Lax',
    `Max-Age=${LOGOUT_STATE_MAX_AGE_SECONDS}`,
    ...(secure ? ['Secure'] : []),
  ].join('; ');
}

export function clearLogoutStateCookie(): string {
  const secure = isSecureContext();
  return [
    `${logoutStateCookieName()}=`,
    'Path=/',
    'HttpOnly',
    'SameSite=Lax',
    'Max-Age=0',
    ...(secure ? ['Secure'] : []),
  ].join('; ');
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

/**
 * Extract the session token per the precedence matrix (contract §7):

 * | Cookie | Header      | Result                                        |
 * |--------|-------------|-----------------------------------------------|
 * | valid  | any         | cookie wins                                   |
 * | present+invalid | any | 401 — never fall back (blocks downgrade)      |
 * | absent | any         | 401 — header tokens are not credentials      |
 * | absent | absent      | 401                                           |
 *
 * The legacy header-token path (LEGACY_HEADER_AUTH) was removed: cookie
 * is the only credential source, so `source` is 'cookie' | 'none'.
 *
 * "Cookie present" = cookie header contains either the current or the
 * legacy cookie name. Token *validity* is decided by the caller — this
 * function only reports provenance so the matrix can be applied.
 */
export type TokenSource = 'cookie' | 'none';

export interface TokenExtraction {
  token: string | null;
  source: TokenSource;
}

export function extractSessionToken(req: Request): TokenExtraction {
  const cookieHeader = req.headers.get('cookie') ?? '';
  const cookieToken =
    readCookie(cookieHeader, sessionCookieName()) ??
    readCookie(cookieHeader, LEGACY_COOKIE_NAME);

  if (cookieToken !== null) {
    return {
      token: cookieToken,
      source: 'cookie',
    };
  }

  return { token: null, source: 'none' };
}

function readCookie(header: string, name: string): string | null {
  const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = header.match(new RegExp(`(?:^|;\\s*)${escaped}=([^;]*)`));
  return match ? match[1] : null;
}
