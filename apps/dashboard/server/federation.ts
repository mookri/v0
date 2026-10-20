import { createHash, randomBytes, timingSafeEqual } from 'node:crypto';

/**
 * AUTH_MODE cutover (ADR-0020) — dashboard BFF.
 *
 *  - direct (default): today's app — the API ceremony is whatever the
 *    deployment's dashboard backend provides (server/backend.ts; none
 *    is wired yet, so those routes fail closed as unavailable).
 *  - federated: the browser is handed to the auth subdomain's unified
 *    login (/oauth/authorize, PKCE + state); the BFF completes the
 *    back-channel code exchange server-side and stores the issuer's
 *    access token in the session cookie. The issuer is the sole
 *    authentication authority (I18) — the BFF verifies that token
 *    locally against the issuer JWKS (server/oidc) on /api/auth/me
 *    before the app ever renders an authenticated screen.
 *
 * Fail-closed posture, mirroring the Laravel boot checks: an unknown
 * mode, or a federated deployment missing its issuer/client config,
 * throws at first use — a broken deployment must not silently degrade
 * to a login surface that cannot complete.
 *
 * (Intentionally parallel to apps/{customer,web,partner}/server/federation.ts:
 * the apps share an env contract but no server modules.)
 */

export type AuthMode = 'direct' | 'federated';

export interface FederationConfig {
  issuer: string;
  clientId: string;
  clientSecret: string;
}

export function authMode(): AuthMode {
  const raw = (process.env.DASHBOARD_AUTH_MODE ?? 'direct').trim();
  if (raw !== 'direct' && raw !== 'federated') {
    throw new Error(`DASHBOARD_AUTH_MODE must be 'direct' or 'federated', got '${raw}'`);
  }
  return raw;
}

export function isFederated(): boolean {
  return authMode() === 'federated';
}

export function federationConfig(): FederationConfig {
  const issuer = (process.env.DASHBOARD_AUTH_ISSUER ?? '').replace(/\/+$/, '');
  const clientId = process.env.DASHBOARD_AUTH_CLIENT_ID ?? '';
  const clientSecret = process.env.DASHBOARD_AUTH_CLIENT_SECRET ?? '';

  if (authMode() === 'federated' && (issuer === '' || clientId === '' || clientSecret === '')) {
    throw new Error(
      'DASHBOARD_AUTH_MODE=federated requires DASHBOARD_AUTH_ISSUER, DASHBOARD_AUTH_CLIENT_ID and DASHBOARD_AUTH_CLIENT_SECRET',
    );
  }

  return { issuer, clientId, clientSecret };
}

export type ParMode = 'off' | 'required';

/**
 * RFC 9126 posture per deployment (A-SEC-004-8):
 *
 *  - off (default): the authorize URL carries the parameters in the
 *    front channel — today's behavior.
 *  - required: every authorization request is PUSHED over the
 *    authenticated back channel first and the browser presents only
 *    the returned single-use request_uri. Set this when the client's
 *    profile mandates PAR at the issuer (the N2 cohort): a mandate
 *    client must never fall back to front-channel parameters.
 *
 * Unknown values throw, exactly like AUTH_MODE: a typo must not
 * silently downgrade a mandate deployment to an unpushed authorize.
 */
export function parMode(): ParMode {
  const raw = (process.env.DASHBOARD_AUTH_PAR ?? 'off').trim();
  if (raw !== 'off' && raw !== 'required') {
    throw new Error(`DASHBOARD_AUTH_PAR must be 'off' or 'required', got '${raw}'`);
  }
  return raw;
}

/** Where the issuer sends the browser back — registered per client. */
export const OAUTH_CALLBACK_PATH = '/api/auth/callback';

export function callbackUrl(origin: string): string {
  return `${origin}${OAUTH_CALLBACK_PATH}`;
}

/**
 * Open-redirect fence for every round-trip return: only same-origin
 * absolute paths survive (no scheme, no protocol-relative //, no
 * backslash tricks). Mirrors the auth-subdomain safeNext (P5).
 */
export function safeNext(raw: string | null | undefined): string | null {
  if (!raw) return null;
  if (!raw.startsWith('/') || raw.startsWith('//') || raw.startsWith('/\\')) return null;
  return raw;
}

export function randomBase64Url(bytes = 32): string {
  return randomBytes(bytes).toString('base64url');
}

/** RFC 7636 S256: base64url(SHA-256(verifier)). */
export function pkceChallenge(verifier: string): string {
  return createHash('sha256').update(verifier).digest('base64url');
}

export function timingSafeCompare(a: string, b: string): boolean {
  const bufA = Buffer.from(a);
  const bufB = Buffer.from(b);
  if (bufA.length !== bufB.length) return false;
  return timingSafeEqual(bufA, bufB);
}

/** The full authorization-request parameter set, wherever it travels. */
export interface AuthorizeParams {
  redirectUri: string;
  state: string;
  challenge: string;
  nonce: string;
}

/**
 * 9a (ADR-0021 D3): the openid scope makes this an OIDC flow — the
 * issuer mandates a nonce on every openid authorization request, and
 * mints it (verified, exact-match) into the id_token the BFF checks
 * at the callback. The scope is the only one registered.
 */
export function authorizeParams(
  config: FederationConfig,
  params: AuthorizeParams,
): Record<string, string> {
  return {
    response_type: 'code',
    client_id: config.clientId,
    redirect_uri: params.redirectUri,
    scope: 'openid',
    state: params.state,
    nonce: params.nonce,
    code_challenge: params.challenge,
    code_challenge_method: 'S256',
  };
}

export function buildAuthorizeUrl(
  config: FederationConfig,
  redirectUri: string,
  state: string,
  challenge: string,
  nonce: string,
): string {
  const url = new URL(`${config.issuer}/oauth/authorize`);
  for (const [key, value] of Object.entries(authorizeParams(config, { redirectUri, state, challenge, nonce }))) {
    url.searchParams.set(key, value);
  }
  return url.toString();
}

/**
 * RFC 9126 pushed-authorization back channel (A-SEC-004-8): the full
 * parameter set travels to the issuer's PAR endpoint over client
 * authentication (client_secret_post — the same posture the callback
 * token exchange uses); the response's single-use, short-TTL
 * request_uri is what the browser presents. Fail-closed: a push that
 * errors or answers without a request_uri THROWS — a mandate client
 * never falls back to front-channel parameters (that fallback is
 * exactly what the mandate forbids).
 */
export async function pushAuthorizationRequest(
  config: FederationConfig,
  params: Record<string, string>,
): Promise<string> {
  const body = new URLSearchParams({ ...params, client_secret: config.clientSecret });

  const res = await fetch(`${config.issuer}/api/oauth/par`, {
    method: 'POST',
    headers: {
      accept: 'application/json',
      'content-type': 'application/x-www-form-urlencoded',
    },
    body: body.toString(),
  });

  if (res.status !== 201) {
    throw new Error(`pushed authorization request failed with status ${res.status}`);
  }

  const payload = (await res.json()) as { request_uri?: unknown };

  if (typeof payload.request_uri !== 'string' || payload.request_uri === '') {
    throw new Error('pushed authorization request returned no request_uri');
  }

  return payload.request_uri;
}

/**
 * RFC 9126 §4: the browser-facing authorize request for a pushed
 * request carries ONLY client_id and request_uri — every other
 * parameter rode the authenticated back channel and the issuer
 * replays the stored set verbatim.
 */
export function buildRequestUriUrl(config: FederationConfig, requestUri: string): string {
  const url = new URL(`${config.issuer}/oauth/authorize`);
  url.searchParams.set('client_id', config.clientId);
  url.searchParams.set('request_uri', requestUri);
  return url.toString();
}

// ---------------------------------------------------------------------------
// Flow cookie — state + PKCE verifier + return path, carried through the
// issuer round-trip. HttpOnly, SameSite=Lax (top-level GET redirect comes
// back as a navigation), 10-minute life, cleared on completion.
//
// Cookie policy follows apps/web/server/session-cookie.ts: `__Host-`
// requires Secure, and a `__Host-` cookie over dev HTTP is silently
// dropped by the browser — a broken login that reads like a bug. Dev
// therefore uses the unprefixed name; production uses `__Host-` + Secure.
// ---------------------------------------------------------------------------

const DEV_FLOW_COOKIE = 'nv-dashboard-auth-flow';
const PROD_FLOW_COOKIE = '__Host-nv-dashboard-auth-flow';

export const LEGACY_FLOW_COOKIES = [DEV_FLOW_COOKIE, PROD_FLOW_COOKIE];

export function flowCookieName(): string {
  return isSecureContext() ? PROD_FLOW_COOKIE : DEV_FLOW_COOKIE;
}

export function isSecureContext(): boolean {
  const override = process.env.SESSION_COOKIE_SECURE;
  if (override === '1') return true;
  if (override === '0') return false;
  return process.env.NODE_ENV === 'production';
}

export interface OAuthFlow {
  state: string;
  verifier: string;
  nonce: string;
  redirect: string | null;
}

export function buildFlowCookie(flow: OAuthFlow, maxAgeSeconds = 600): string {
  const value = [flow.state, flow.verifier, flow.nonce, encodeURIComponent(flow.redirect ?? '')].join('.');
  const secure = isSecureContext() ? '; Secure' : '';
  return `${flowCookieName()}=${encodeURIComponent(value)}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${maxAgeSeconds}${secure}`;
}

export function clearFlowCookie(): string {
  const secure = isSecureContext() ? '; Secure' : '';
  return `${flowCookieName()}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0${secure}`;
}

export function readFlowCookie(cookieHeader: string | null): OAuthFlow | null {
  if (!cookieHeader) return null;
  for (const name of LEGACY_FLOW_COOKIES) {
    const match = cookieHeader.match(new RegExp(`(?:^|;\\s*)${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`));
    if (!match) continue;
    const decoded = decodeURIComponent(match[1]);
    // state, verifier and nonce are base64url (never contain '.'); only
    // the encoded redirect may — so the first three segments are fixed
    // and the remainder rejoins into the redirect. A cookie without a
    // nonce segment (pre-9a) is not a flow: the id_token binding would
    // have nothing to check against, so it fails closed.
    const segments = decoded.split('.');
    if (segments.length < 4) return null;
    const [state, verifier, nonce] = segments;
    const decodedRedirect = decodeURIComponent(segments.slice(3).join('.'));
    return { state, verifier, nonce, redirect: decodedRedirect === '' ? null : decodedRedirect };
  }
  return null;
}
