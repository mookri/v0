import { verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import type { SessionAdapter, CustomerSession } from '@/adapters/session';
import { isFederated, isSecureContext } from '@/server/federation';
import { payloadSidTerminated } from '@/server/backchannel-logout';

// Backend-cookie session (F2): the login route stores the backend-minted
// bearer in an HttpOnly cookie; handlers resolve the actor by verifying
// it locally with the shared secret (SESSION_SIGNING_KEY must
// equal the backend IDENTITY_HMAC_SECRET in wired deployments).
// Mocks remain the default — this adapter is only constructed when
// CUSTOMER_BACKEND=laravel.
//
// Cookie policy (parity with the web app's server/session-cookie.ts):
// production uses the `__Host-` prefix + Secure; local dev runs on HTTP,
// where a `__Host-` cookie is silently dropped by the browser — so dev
// uses the unprefixed name and the legacy name is always read too.
const DEV_TOKEN_COOKIE = 'nv_customer_token';
const PROD_TOKEN_COOKIE = '__Host-nv_customer_token';
export const LEGACY_CUSTOMER_TOKEN_COOKIE = PROD_TOKEN_COOKIE;

export function customerTokenCookieName(): string {
  return isSecureContext() ? PROD_TOKEN_COOKIE : DEV_TOKEN_COOKIE;
}

export function customerTokenSecret(): string {
  return process.env.SESSION_SIGNING_KEY ?? '';
}

export function readCustomerToken(cookieHeader: string | null): string | null {
  if (!cookieHeader) return null;
  for (const part of cookieHeader.split(';')) {
    const eq = part.indexOf('=');
    if (eq === -1) continue;
    const name = part.slice(0, eq).trim();
    if (name === customerTokenCookieName() || name === LEGACY_CUSTOMER_TOKEN_COOKIE) {
      return decodeURIComponent(part.slice(eq + 1).trim());
    }
  }
  return null;
}

// RP-initiated logout correlation cookie (A-SEC-004-6 landing): the
// logout/start route mints the state, parks it here for the
// end-session round-trip (10 min covers the OP redirect chain), and
// the landing route correlates it before acknowledging the return.
// Non-credential by construction — it gates an acknowledgment page,
// never an actor, so it carries no token material.
const DEV_LOGOUT_STATE_COOKIE = 'nv-logout-state';
const PROD_LOGOUT_STATE_COOKIE = '__Host-nv-logout-state';
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

export function resolveCustomerActor(
  cookieHeader: string | null,
  secret: string,
): { userId: string; sessionId: string } | null {
  const token = readCustomerToken(cookieHeader);
  if (!token) return null;

  // Federated deployments (AUTH_MODE cutover): the cookie carries the
  // issuer's access token. The payload is decoded for routing/UX context
  // ONLY — validity is judged upstream on every proxied call (I18): an
  // expired token surfaces as a 401 from Laravel through
  // laravelErrorResponse, and a forged cookie dies at the same wall.
  if (isFederated()) {
    return decodeFederatedActor(token);
  }

  if (!secret) return null;
  try {
    const actor = verifyActorToken(secret, token, new Date().toISOString());
    return { userId: actor.userId, sessionId: actor.sessionId };
  } catch {
    return null;
  }
}

function decodeFederatedActor(token: string): { userId: string; sessionId: string } | null {
  const parts = token.split('.');
  if (parts.length !== 3) return null;

  let payload: Record<string, unknown>;
  try {
    payload = JSON.parse(Buffer.from(parts[1], 'base64url').toString('utf8'));
  } catch {
    return null;
  }

  const sub =
    typeof payload.sub === 'string' || typeof payload.sub === 'number' ? String(payload.sub) : null;
  const exp = typeof payload.exp === 'number' ? payload.exp : null;
  if (sub === null || exp === null || exp * 1000 <= Date.now()) return null;

  // Direction check against this BFF's own client (league signs `aud`
  // = the client the token was minted for — lcobucci serializes it as a
  // single-element array; signature-free, the upstream still judges
  // validity on every proxied call): a token minted for another client
  // never counts as a local session. (No `iss` check — league access
  // tokens carry no issuer claim; the signature IS the issuer binding,
  // and upstream verifies it.) A missing `aud` fails closed too.
  const clientId = process.env.CUSTOMER_AUTH_CLIENT_ID ?? '';
  if (clientId !== '') {
    const aud = payload.aud;
    const tokenAud = Array.isArray(aud)
      ? typeof aud[0] === 'string'
        ? aud[0]
        : null
      : typeof aud === 'string'
        ? aud
        : null;
    if (tokenAud !== clientId) return null;
  }

  // League access tokens carry no session claim; the jti is the stable
  // per-token identifier (routing/UX context only). Session-bound
  // mints now embed the OP session's `sid` (A-SEC-004-6) — used here
  // ONLY for the back-channel logout denylist check below.
  if (payloadSidTerminated(payload)) return null;

  return { userId: sub, sessionId: typeof payload.jti === 'string' ? payload.jti : '' };
}

export function buildSessionCookie(token: string, maxAgeSeconds: number): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${customerTokenCookieName()}=${encodeURIComponent(token)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${Math.floor(maxAgeSeconds)}${secure}`;
}

export function clearSessionCookie(): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${customerTokenCookieName()}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
}

// ---------------------------------------------------------------------------
// Refresh cookie (A-SEC-004-11, D4 BFF half): the federated refresh token
// rides a SECOND HttpOnly cookie beside the access token — same stateless
// cookie posture as the session cookie (no server-side session store, the
// pair travels with the browser across BFF instances). Max-Age tracks the
// ISSUER's refresh-token lifetime (platform AppServiceProvider:
// Passport::refreshTokensExpireIn — 30 days), not the access token's: the
// pair outlives any single access token, so an idle browser can resurrect
// its session at its next request without an interactive login. Keep the
// constant aligned with the issuer's refresh TTL when that changes.
// ---------------------------------------------------------------------------

const DEV_REFRESH_COOKIE = 'nv_customer_refresh';
const PROD_REFRESH_COOKIE = '__Host-nv_customer_refresh';
export const LEGACY_CUSTOMER_REFRESH_COOKIE = PROD_REFRESH_COOKIE;
export const REFRESH_COOKIE_MAX_AGE_SECONDS = 30 * 24 * 3600;

export function customerRefreshCookieName(): string {
  return isSecureContext() ? PROD_REFRESH_COOKIE : DEV_REFRESH_COOKIE;
}

export function buildRefreshCookie(
  token: string,
  maxAgeSeconds: number = REFRESH_COOKIE_MAX_AGE_SECONDS,
): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${customerRefreshCookieName()}=${encodeURIComponent(token)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${Math.floor(maxAgeSeconds)}${secure}`;
}

export function clearRefreshCookie(): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${customerRefreshCookieName()}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
}

export function readCustomerRefreshToken(cookieHeader: string | null): string | null {
  if (!cookieHeader) return null;
  for (const part of cookieHeader.split(';')) {
    const eq = part.indexOf('=');
    if (eq === -1) continue;
    const name = part.slice(0, eq).trim();
    if (name === customerRefreshCookieName() || name === LEGACY_CUSTOMER_REFRESH_COOKIE) {
      return decodeURIComponent(part.slice(eq + 1).trim());
    }
  }
  return null;
}

// ---------------------------------------------------------------------------
// Rotation-pending cookie (A-SEC-004-11): when a refresh exchange's
// response never reaches the browser (network drop, process restart
// mid-rotation), the parked Idempotency-Key lets the NEXT request retry
// the SAME exchange — the issuer replays a rotation it already committed
// (D4 recovery, federation.refresh_retry_window_seconds) instead of
// seeing a consumed token presented as reuse. The key is not a
// credential: recovery still demands client authentication, the same
// refresh token and an identical fingerprint. Its Max-Age mirrors the
// issuer's recovery window default (60 s) — raise it together with
// AUTH_REFRESH_RETRY_WINDOW.
// ---------------------------------------------------------------------------

const DEV_ROTATION_COOKIE = 'nv_customer_rot';
const PROD_ROTATION_COOKIE = '__Host-nv_customer_rot';
export const LEGACY_ROTATION_PENDING_COOKIE = PROD_ROTATION_COOKIE;
export const ROTATION_PENDING_MAX_AGE_SECONDS = 60;

export function customerRotationPendingCookieName(): string {
  return isSecureContext() ? PROD_ROTATION_COOKIE : DEV_ROTATION_COOKIE;
}

export function buildRotationPendingCookie(
  key: string,
  maxAgeSeconds: number = ROTATION_PENDING_MAX_AGE_SECONDS,
): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${customerRotationPendingCookieName()}=${encodeURIComponent(key)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${Math.floor(maxAgeSeconds)}${secure}`;
}

export function clearRotationPendingCookie(): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${customerRotationPendingCookieName()}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
}

export function readCustomerRotationPending(cookieHeader: string | null): string | null {
  if (!cookieHeader) return null;
  for (const part of cookieHeader.split(';')) {
    const eq = part.indexOf('=');
    if (eq === -1) continue;
    const name = part.slice(0, eq).trim();
    if (name === customerRotationPendingCookieName() || name === LEGACY_ROTATION_PENDING_COOKIE) {
      return decodeURIComponent(part.slice(eq + 1).trim());
    }
  }
  return null;
}

/** Every cookie name this session family ever writes (dev AND prod
 *  casings, regardless of the current context) — the set the rotation
 *  rewrite strips before installing fresh values; a browser may hold
 *  either naming from an earlier context. */
export function sessionCookieNames(): string[] {
  return [
    DEV_TOKEN_COOKIE,
    PROD_TOKEN_COOKIE,
    DEV_REFRESH_COOKIE,
    PROD_REFRESH_COOKIE,
    DEV_ROTATION_COOKIE,
    PROD_ROTATION_COOKIE,
  ];
}

export class BackendSessionAdapter implements SessionAdapter {
  constructor(
    private readonly cookieHeader: string | null,
    private readonly secret: string = customerTokenSecret(),
  ) {}

  async getSession(): Promise<CustomerSession> {
    const actor = resolveCustomerActor(this.cookieHeader, this.secret);
    if (!actor) return { authenticated: false };
    return { authenticated: true, actor: { userId: actor.userId } };
  }
}
