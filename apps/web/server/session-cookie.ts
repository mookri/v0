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

/**
 * Extract the session token per the precedence matrix (contract §7):

 * | Cookie | Header      | Result                                        |
 * |--------|-------------|-----------------------------------------------|
 * | valid  | any         | cookie wins                                   |
 * | present+invalid | any | 401 — never fall back (blocks downgrade)      |
 * | absent | valid       | allowed only when LEGACY_HEADER_AUTH=1        |
 * | absent | absent      | 401                                           |
 *
 * "Cookie present" = cookie header contains either the current or the
 * legacy cookie name. Token *validity* is decided by the caller — this
 * function only reports provenance so the matrix can be applied.
 */
export type TokenSource = 'cookie' | 'header' | 'none';

export interface TokenExtraction {
  token: string | null;
  source: TokenSource;
  /** Set when the request carried a header token alongside a cookie. */
  legacyHeaderWithCookie: boolean;
}

export function extractSessionToken(req: Request): TokenExtraction {
  const cookieHeader = req.headers.get('cookie') ?? '';
  const cookieToken =
    readCookie(cookieHeader, sessionCookieName()) ??
    readCookie(cookieHeader, LEGACY_COOKIE_NAME);
  const headerRaw = req.headers.get('authorization');
  const headerToken = headerRaw?.startsWith('Bearer ') ? headerRaw.slice(7) : null;

  if (cookieToken !== null) {
    return {
      token: cookieToken,
      source: 'cookie',
      legacyHeaderWithCookie: headerToken !== null,
    };
  }

  if (headerToken !== null && process.env.LEGACY_HEADER_AUTH === '1') {
    return { token: headerToken, source: 'header', legacyHeaderWithCookie: false };
  }

  return { token: null, source: 'none', legacyHeaderWithCookie: headerToken !== null };
}

function readCookie(header: string, name: string): string | null {
  const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = header.match(new RegExp(`(?:^|;\\s*)${escaped}=([^;]*)`));
  return match ? match[1] : null;
}
