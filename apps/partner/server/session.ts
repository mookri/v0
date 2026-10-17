import type { RequestActor } from '@navdhya/identity/src/ports/actor-resolver';
import { SESSION_COOKIE } from '@/server/auth';
import { federationConfig, isSecureContext } from '@/server/federation';
import { verifyAccessToken } from '@/server/oidc';
import { isSidTerminated } from '@/server/backchannel-logout';

// Federated session (ADR-0020 cutover) — partner BFF.
//
// The cookie name is the app's existing SESSION_COOKIE: direct and
// federated deployments never coexist, so one name serves both. Under
// federation its value is the ISSUER'S ACCESS TOKEN (same contract as
// customer/web's token cookies), and because partner's API routes act
// on the app's own stores with no upstream to re-judge the token, the
// token is verified locally on every request (server/oidc →
// application/actor's mode-aware resolver) — signature against the
// issuer JWKS, exp/iat/aud bounded. An unverified cookie is never an
// actor: forged values die at the same wall as expired ones.
//
// Cookie policy parity with customer's backend-session.ts: HttpOnly,
// SameSite=Lax, Secure in production (or SESSION_COOKIE_SECURE=1); the
// `__Host-` prefix is deliberately NOT used — SESSION_COOKIE is the
// pre-existing `__session` name the app's pages already read.
export function buildSessionCookie(token: string, maxAgeSeconds: number): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${SESSION_COOKIE}=${encodeURIComponent(token)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${Math.floor(maxAgeSeconds)}${secure}`;
}

export function clearSessionCookie(): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${SESSION_COOKIE}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
}

// RP-initiated logout correlation cookie (A-SEC-004-6 landing): the
// logout/start route mints the state, parks it here for the
// end-session round-trip (10 min covers the OP redirect chain), and
// the landing route correlates it before acknowledging the return.
// Non-credential — it gates an acknowledgment page, never an actor.
const DEV_LOGOUT_STATE_COOKIE = 'nv-partner-logout-state';
const PROD_LOGOUT_STATE_COOKIE = '__Host-nv-partner-logout-state';
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

/**
 * Resolve the actor behind a federated bearer (the cookie's issuer
 * access token). Returns null on any verification failure — callers
 * treat null as unauthenticated, never a partial session.
 *
 * Roles are deliberately EMPTY: league access tokens carry scopes,
 * not partner role claims, and this app has no upstream role source
 * yet — role-gated routes (review) therefore fail CLOSED under
 * federation until identity provisioning maps issuer subjects to
 * partner roles (roadmap).
 *
 * federationConfig() is allowed to THROW here: a federated deployment
 * missing its issuer/client config must surface as a loud failure,
 * not a quiet 401 that reads like a bad login.
 */
export async function federatedActorFromToken(token: string): Promise<RequestActor | null> {
  const config = federationConfig();
  const claims = await verifyAccessToken(token, config.issuer, config.clientId);
  if (!claims) return null;
  // A-SEC-004-6: a sid the OP ended over the back channel stops
  // resolving actors here — the cookie is still validly signed and
  // unexpired, but the session behind it is gone. Session-bound mints
  // embed `sid`; a token without one has no lineage to terminate.
  if (typeof claims.sid === 'string' && claims.sid !== '' && isSidTerminated(claims.sid)) return null;
  return {
    userId: claims.sub,
    // jti remains the stable per-token identifier for local context;
    // the OP session id (`sid`) is the revocation/denylist key above.
    sessionId: typeof claims.jti === 'string' ? claims.jti : '',
    roles: [],
  };
}
