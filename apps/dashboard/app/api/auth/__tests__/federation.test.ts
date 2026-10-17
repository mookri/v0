/**
 * AUTH_MODE cutover (ADR-0020) + 9a OIDC (ADR-0021) — dashboard BFF
 * federation flow.
 *
 * The cutover invariants that are automatable here:
 *   - direct deployments are untouched (authorize bounces to /login,
 *     the login/me proxy fails closed while no backend is configured,
 *     callback does not exist)
 *   - federated deployments own no credential form: authorize hands the
 *     browser to the issuer with PKCE + state + openid nonce; callback
 *     exchanges the code server-side (verifier + secret never in the
 *     browser) and parks the access token in the session cookie
 *   - 9a (ADR-0021): the authorize request is an OIDC flow (scope
 *     openid, mandatory nonce in the flow cookie); the callback
 *     REQUIRES the RFC 9207 `iss` parameter to match the configured
 *     issuer byte for byte, and verifies the token endpoint's id_token
 *     (RS256 against the issuer JWKS, iss/aud/exp/iat/nonce) before any
 *     session exists
 *   - fail-closed: bad modes/config throw; mismatched state, missing or
 *     foreign iss, and unverifiable id_tokens never mint a session;
 *     open-redirect targets never survive
 *   - /api/auth/me re-verifies the cookie's issuer access token locally
 *     (kid-less league header against the published JWKS key set) and
 *     then confirms against the issuer's userinfo endpoint — sub
 *     disagreement fails closed — before the app renders authenticated
 *   - logout revokes at the issuer best-effort and always clears
 *
 * Error bodies are `{ message }` — the SPA client's failure contract.
 * The parallel suites live in apps/web (CUSTOMER_* env, pinned for
 * customer+web) and apps/partner (PARTNER_*); this one pins the
 * DASHBOARD_* contract.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';
import { GET as authorizeGet } from '@/app/api/auth/authorize/route';
import { GET as callbackGet } from '@/app/api/auth/callback/route';
import { POST as loginPost } from '@/app/api/auth/login/route';
import { POST as logoutPost } from '@/app/api/auth/logout/route';
import { GET as meGet } from '@/app/api/auth/me/route';
import {
  authMode,
  federationConfig,
  safeNext,
  pkceChallenge,
  buildFlowCookie,
  readFlowCookie,
  buildAuthorizeUrl,
  timingSafeCompare,
} from '@/server/federation';
import { verifyIdToken, verifyAccessToken, clearOidcJwksCache } from '@/server/oidc';
import { readSessionToken, sessionCookieName } from '@/server/session';
import { createSign, generateKeyPairSync } from 'node:crypto';

const ENV_KEYS = [
  'DASHBOARD_AUTH_MODE',
  'DASHBOARD_AUTH_ISSUER',
  'DASHBOARD_AUTH_CLIENT_ID',
  'DASHBOARD_AUTH_CLIENT_SECRET',
  'DASHBOARD_API_BASE',
  'SESSION_COOKIE_SECURE',
] as const;

const saved: Record<string, string | undefined> = {};

function setEnv(values: Record<string, string | undefined>) {
  for (const key of ENV_KEYS) {
    saved[key] = process.env[key];
    if (values[key] === undefined) delete process.env[key];
    else process.env[key] = values[key];
  }
}

function restoreEnv() {
  for (const key of ENV_KEYS) {
    if (saved[key] === undefined) delete process.env[key];
    else process.env[key] = saved[key];
  }
}

const FEDERATED = {
  DASHBOARD_AUTH_MODE: 'federated',
  DASHBOARD_AUTH_ISSUER: 'http://auth.localhost:8000',
  DASHBOARD_AUTH_CLIENT_ID: 'dashboard-bff',
  DASHBOARD_AUTH_CLIENT_SECRET: 'dashboard-bff-secret',
  DASHBOARD_API_BASE: undefined,
};

const DIRECT = {
  DASHBOARD_AUTH_MODE: undefined,
  DASHBOARD_AUTH_ISSUER: undefined,
  DASHBOARD_AUTH_CLIENT_ID: undefined,
  DASHBOARD_AUTH_CLIENT_SECRET: undefined,
  DASHBOARD_API_BASE: undefined,
};

const FLOW_COOKIE = 'nv-dashboard-auth-flow';
const BASE = 'http://localhost:3003';

function findCookie(headers: Headers, name: string): string | null {
  for (const line of headers.getSetCookie()) {
    const match = line.match(new RegExp(`(?:^|;\\s*)${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`));
    if (match) return decodeURIComponent(match[1]);
  }
  return null;
}

afterEach(() => {
  restoreEnv();
  vi.restoreAllMocks();
  clearOidcJwksCache();
});

describe('federation module', () => {
  it('defaults to direct and rejects unknown modes', () => {
    setEnv(DIRECT);
    expect(authMode()).toBe('direct');

    process.env.DASHBOARD_AUTH_MODE = 'halfway';
    expect(() => authMode()).toThrow(/DASHBOARD_AUTH_MODE/);
  });

  it('fails closed when federated config is incomplete', () => {
    setEnv({ ...FEDERATED, DASHBOARD_AUTH_CLIENT_SECRET: undefined });
    expect(() => federationConfig()).toThrow(/DASHBOARD_AUTH_MODE=federated/);
  });

  it('trims trailing slashes off the issuer', () => {
    setEnv({ ...FEDERATED, DASHBOARD_AUTH_ISSUER: 'http://auth.localhost:8000///' });
    expect(federationConfig().issuer).toBe('http://auth.localhost:8000');
  });

  it('safeNext fences every open-redirect shape', () => {
    expect(safeNext('/settings')).toBe('/settings');
    expect(safeNext('//evil.example')).toBeNull();
    expect(safeNext('https://evil.example')).toBeNull();
    expect(safeNext('/\\evil.example')).toBeNull();
    expect(safeNext('')).toBeNull();
    expect(safeNext(null)).toBeNull();
  });

  it('pkceChallenge matches the RFC 7636 test vector', () => {
    expect(pkceChallenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk')).toBe(
      'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
    );
  });

  it('flow cookie round-trips state/verifier/nonce/redirect', () => {
    const header = buildFlowCookie({ state: 'state-1', verifier: 'verifier-1', nonce: 'nonce-1', redirect: '/next' });
    expect(header).toContain('nv-dashboard-auth-flow='); // dev (test) name — no __Host- over HTTP
    expect(header).toContain('HttpOnly');
    expect(header).toContain('SameSite=Lax');

    const flow = readFlowCookie(`other=x; ${header.split(';')[0]}`);
    expect(flow).toEqual({ state: 'state-1', verifier: 'verifier-1', nonce: 'nonce-1', redirect: '/next' });

    const dotted = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/pujas/a.b' });
    expect(readFlowCookie(dotted.split(';')[0])?.redirect).toBe('/pujas/a.b');

    const bare = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: null });
    expect(readFlowCookie(bare.split(';')[0])).toEqual({ state: 's', verifier: 'v', nonce: 'n', redirect: null });
  });

  it('a pre-9a cookie (no nonce segment) is not a flow', () => {
    // 9a: the id_token binding needs a nonce to check against — a
    // legacy three-segment cookie fails closed instead of degrading.
    const legacy = encodeURIComponent(['s', 'v', encodeURIComponent('/')].join('.'));
    expect(readFlowCookie(`${FLOW_COOKIE}=${legacy}`)).toBeNull();
  });

  it('authorize URL carries openid scope, nonce and PKCE', () => {
    const url = new URL(
      buildAuthorizeUrl(
        { issuer: 'http://auth.localhost:8000', clientId: 'c1', clientSecret: 's' },
        'http://localhost/api/auth/callback',
        'state-1',
        'challenge-1',
        'nonce-1',
      ),
    );
    expect(url.origin + url.pathname).toBe('http://auth.localhost:8000/oauth/authorize');
    expect(url.searchParams.get('response_type')).toBe('code');
    expect(url.searchParams.get('client_id')).toBe('c1');
    expect(url.searchParams.get('redirect_uri')).toBe('http://localhost/api/auth/callback');
    // 9a (ADR-0021 D3): openid makes this an OIDC flow; the issuer
    // mandates a nonce on every openid authorization request.
    expect(url.searchParams.get('scope')).toBe('openid');
    expect(url.searchParams.get('nonce')).toBe('nonce-1');
    expect(url.searchParams.get('state')).toBe('state-1');
    expect(url.searchParams.get('code_challenge')).toBe('challenge-1');
    expect(url.searchParams.get('code_challenge_method')).toBe('S256');
  });

  it('timingSafeCompare rejects mismatches without throwing', () => {
    expect(timingSafeCompare('abc', 'abc')).toBe(true);
    expect(timingSafeCompare('abc', 'abd')).toBe(false);
    expect(timingSafeCompare('abc', 'ab')).toBe(false);
  });
});

describe('GET /api/auth/authorize', () => {
  it('direct: bounces to /login and keeps a fenced redirect', async () => {
    setEnv(DIRECT);
    const response = await authorizeGet(new Request(`${BASE}/api/auth/authorize?redirect=/pujas`));
    expect(response.status).toBe(302);
    expect(response.headers.get('location')).toBe('/login?redirect=%2Fpujas');
  });

  it('direct: an open-redirect target falls back to the login form', async () => {
    setEnv(DIRECT);
    const response = await authorizeGet(new Request(`${BASE}/api/auth/authorize?redirect=//evil.example`));
    expect(response.status).toBe(302);
    expect(response.headers.get('location')).toBe('/login');
  });

  it('federated: hands the browser to the issuer with matching PKCE + nonce', async () => {
    setEnv(FEDERATED);
    const response = await authorizeGet(new Request(`${BASE}/api/auth/authorize?redirect=/settings`));
    expect(response.status).toBe(302);

    const location = new URL(response.headers.get('location')!);
    expect(location.origin + location.pathname).toBe('http://auth.localhost:8000/oauth/authorize');

    const flowCookie = findCookie(response.headers, FLOW_COOKIE);
    expect(flowCookie).toBeTruthy();
    const flow = readFlowCookie(`${FLOW_COOKIE}=${flowCookie}`);

    expect(location.searchParams.get('state')).toBe(flow!.state);
    // The nonce travels in the URL AND the flow cookie — the callback
    // exact-matches the id_token's nonce against the cookie's copy.
    expect(location.searchParams.get('scope')).toBe('openid');
    expect(location.searchParams.get('nonce')).toBe(flow!.nonce);
    expect(location.searchParams.get('redirect_uri')).toBe(`${BASE}/api/auth/callback`);
    expect(location.searchParams.get('code_challenge')).toBe(pkceChallenge(flow!.verifier));
    expect(flow!.redirect).toBe('/settings');
  });
});

describe('GET /api/auth/callback', () => {
  it('direct: the callback surface does not exist', async () => {
    setEnv(DIRECT);
    const response = await callbackGet(new Request(`${BASE}/api/auth/callback?code=x&state=y`));
    expect(response.status).toBe(404);
    expect((await response.json()).message).toMatch(/direct deployments/);
  });

  it('federated: without a flow cookie there is no session (401)', async () => {
    setEnv(FEDERATED);
    const response = await callbackGet(new Request(`${BASE}/api/auth/callback?code=x&state=y`));
    expect(response.status).toBe(401);
    expect((await response.json()).message).toBeTruthy();
  });

  it('federated: state mismatch never exchanges the code', async () => {
    setEnv(FEDERATED);
    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await callbackGet(
      new Request(`${BASE}/api/auth/callback?code=x&state=tampered`, {
        headers: { cookie: buildFlowCookie({ state: 'expected', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0] },
      }),
    );
    expect(response.status).toBe(401);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated: issuer refusal is uniform and reflects nothing', async () => {
    setEnv(FEDERATED);
    const response = await callbackGet(
      new Request(`${BASE}/api/auth/callback?error=access_denied&error_description=secret-detail`, {
        headers: { cookie: buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0] },
      }),
    );
    expect(response.status).toBe(401);
    const text = await response.text();
    expect(text).not.toContain('secret-detail');
  });

  it('federated: a response WITHOUT iss is rejected before any exchange', async () => {
    setEnv(FEDERATED);
    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await callbackGet(
      new Request(`${BASE}/api/auth/callback?code=x&state=s`, {
        headers: { cookie: buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0] },
      }),
    );
    expect(response.status).toBe(401);
    // Mix-up defence (9a D2): no iss, no exchange — not even attempted.
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated: a foreign iss is rejected before any exchange', async () => {
    setEnv(FEDERATED);
    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await callbackGet(
      new Request(`${BASE}/api/auth/callback?code=x&state=s&iss=http://evil.example`, {
        headers: { cookie: buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0] },
      }),
    );
    expect(response.status).toBe(401);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated: happy path verifies the id_token and sets the session', async () => {
    setEnv(FEDERATED);
    const authorize = await authorizeGet(new Request(`${BASE}/api/auth/authorize?redirect=/pujas`));
    const flowCookieHeader = authorize.headers.getSetCookie().find((l) => l.startsWith(`${FLOW_COOKIE}=`))!;
    const location = new URL(authorize.headers.get('location')!);
    const state = location.searchParams.get('state')!;
    const nonce = location.searchParams.get('nonce')!;

    const fetchSpy = vi.spyOn(global, 'fetch').mockImplementation(async (input) => {
      const url = String(input);
      if (url.endsWith('/.well-known/jwks.json')) {
        return new Response(JSON.stringify(jwksDocument()), {
          status: 200,
          headers: { 'content-type': 'application/json' },
        });
      }
      return new Response(
        JSON.stringify({
          access_token: 'issuer.jwt.token',
          token_type: 'Bearer',
          expires_in: 900,
          id_token: mintIdToken({ iss: FEDERATED.DASHBOARD_AUTH_ISSUER, aud: 'dashboard-bff', nonce }),
        }),
        { status: 200, headers: { 'content-type': 'application/json' } },
      );
    });

    const response = await callbackGet(
      new Request(
        `${BASE}/api/auth/callback?code=authcode-1&state=${encodeURIComponent(state)}&iss=${encodeURIComponent(FEDERATED.DASHBOARD_AUTH_ISSUER)}`,
        { headers: { cookie: flowCookieHeader.split(';')[0] } },
      ),
    );

    expect(response.status).toBe(302);
    expect(response.headers.get('location')).toBe('/pujas');

    const sessionCookie = response.headers.getSetCookie().find((l) => l.startsWith(`${sessionCookieName()}=`))!;
    expect(sessionCookie).toContain('issuer.jwt.token');
    expect(sessionCookie).toContain('HttpOnly');
    // The flow cookie is cleared in the same response.
    expect(
      response.headers.getSetCookie().some((l) => l.startsWith(`${FLOW_COOKIE}=`) && l.includes('Max-Age=0')),
    ).toBe(true);

    expect(fetchSpy).toHaveBeenCalledTimes(2);
    const [url, init] = fetchSpy.mock.calls[0] as unknown as [string, RequestInit];
    expect(url).toBe('http://auth.localhost:8000/oauth/token');
    const body = new URLSearchParams(String(init.body));
    expect(body.get('grant_type')).toBe('authorization_code');
    expect(body.get('code')).toBe('authcode-1');
    expect(body.get('client_id')).toBe('dashboard-bff');
    expect(body.get('client_secret')).toBe('dashboard-bff-secret');
    expect(body.get('redirect_uri')).toBe(`${BASE}/api/auth/callback`);
    // The verifier sent back is the one the authorize step parked.
    const flow = readFlowCookie(flowCookieHeader.split(';')[0])!;
    expect(body.get('code_verifier')).toBe(flow.verifier);
    // The secret never appears anywhere the browser could read it.
    expect(response.headers.getSetCookie().join('\n')).not.toContain('dashboard-bff-secret');
  });

  it('federated: an id_token with a foreign nonce never mints a session', async () => {
    setEnv(FEDERATED);
    mockTokenEndpoint({
      access_token: 'issuer.jwt.token',
      expires_in: 900,
      id_token: mintIdToken({
        iss: FEDERATED.DASHBOARD_AUTH_ISSUER,
        aud: 'dashboard-bff',
        nonce: 'some-other-nonce',
      }),
    });

    const response = await callbackGet(callbackRequest({ nonce: 'flow-nonce' }));
    expect(response.status).toBe(401);
    expect(sessionCookieSet(response)).toBe(false);
  });

  it('federated: an id_token signed by another key never mints a session', async () => {
    setEnv(FEDERATED);
    const attacker = generateKeyPairSync('rsa', { modulusLength: 2048 });
    mockTokenEndpoint({
      access_token: 'issuer.jwt.token',
      expires_in: 900,
      id_token: signToken(
        { iss: FEDERATED.DASHBOARD_AUTH_ISSUER, aud: 'dashboard-bff', nonce: 'n-1' },
        attacker.privateKey,
      ),
    });

    const response = await callbackGet(callbackRequest({ nonce: 'n-1' }));
    expect(response.status).toBe(401);
    expect(sessionCookieSet(response)).toBe(false);
  });

  it('federated: an expired id_token never mints a session', async () => {
    setEnv(FEDERATED);
    const now = Math.floor(Date.now() / 1000);
    mockTokenEndpoint({
      access_token: 'issuer.jwt.token',
      expires_in: 900,
      id_token: mintIdToken({ iss: FEDERATED.DASHBOARD_AUTH_ISSUER, aud: 'dashboard-bff', nonce: 'n-1', exp: now - 3600 }),
    });

    const response = await callbackGet(callbackRequest({ nonce: 'n-1' }));
    expect(response.status).toBe(401);
    expect(sessionCookieSet(response)).toBe(false);
  });

  it('federated: an id_token for another audience never mints a session', async () => {
    setEnv(FEDERATED);
    mockTokenEndpoint({
      access_token: 'issuer.jwt.token',
      expires_in: 900,
      id_token: mintIdToken({ iss: FEDERATED.DASHBOARD_AUTH_ISSUER, aud: 'customer-bff', nonce: 'n-1' }),
    });

    const response = await callbackGet(callbackRequest({ nonce: 'n-1' }));
    expect(response.status).toBe(401);
    expect(sessionCookieSet(response)).toBe(false);
  });

  it('federated: an id_token from another issuer never mints a session', async () => {
    setEnv(FEDERATED);
    mockTokenEndpoint({
      access_token: 'issuer.jwt.token',
      expires_in: 900,
      id_token: mintIdToken({ iss: 'http://rogue-op.example', aud: 'dashboard-bff', nonce: 'n-1' }),
    });

    const response = await callbackGet(callbackRequest({ nonce: 'n-1' }));
    expect(response.status).toBe(401);
    expect(sessionCookieSet(response)).toBe(false);
  });

  it('federated: a token response without id_token is a failed sign-in', async () => {
    setEnv(FEDERATED);
    mockTokenEndpoint({ access_token: 'issuer.jwt.token', expires_in: 900 });

    const response = await callbackGet(callbackRequest({ nonce: 'n-1' }));
    // openid was requested: an id_token is mandatory, never optional.
    expect(response.status).toBe(502);
    expect(sessionCookieSet(response)).toBe(false);
  });

  it('federated: a failing token endpoint never mints a session', async () => {
    setEnv(FEDERATED);
    vi.spyOn(global, 'fetch').mockResolvedValue(new Response('nope', { status: 400 }));

    const flow = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0];
    const response = await callbackGet(
      new Request(
        `${BASE}/api/auth/callback?code=x&state=s&iss=${encodeURIComponent(FEDERATED.DASHBOARD_AUTH_ISSUER)}`,
        { headers: { cookie: flow } },
      ),
    );
    expect(response.status).toBe(502);
    expect(response.headers.getSetCookie().some((l) => l.includes(`${FLOW_COOKIE}=`))).toBe(true);
  });

  it('federated: an unreachable issuer is a typed failure, not a 500', async () => {
    setEnv(FEDERATED);
    vi.spyOn(global, 'fetch').mockRejectedValue(new Error('ECONNREFUSED'));

    const flow = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0];
    const response = await callbackGet(
      new Request(
        `${BASE}/api/auth/callback?code=x&state=s&iss=${encodeURIComponent(FEDERATED.DASHBOARD_AUTH_ISSUER)}`,
        { headers: { cookie: flow } },
      ),
    );
    expect(response.status).toBe(502);
  });
});

describe('verifyIdToken (server/oidc)', () => {
  const ISSUER = 'http://auth.localhost:8000';

  it('accepts a well-formed token and returns the claims', async () => {
    mockJwks();
    const claims = await verifyIdToken(
      mintIdToken({ iss: ISSUER, aud: 'dashboard-bff', nonce: 'n-1' }),
      ISSUER,
      'dashboard-bff',
      'n-1',
    );
    expect(claims).not.toBeNull();
    expect(claims!.nonce).toBe('n-1');
  });

  it('rejects alg confusion (HS256 header on an RSA-signed body)', async () => {
    mockJwks();
    const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: 'test-key' })).toString('base64url');
    const payload = Buffer.from(
      JSON.stringify({ iss: ISSUER, aud: 'dashboard-bff', nonce: 'n-1', exp: Math.floor(Date.now() / 1000) + 600 }),
    ).toString('base64url');
    const claims = await verifyIdToken(`${header}.${payload}.sig`, ISSUER, 'dashboard-bff', 'n-1');
    expect(claims).toBeNull();
  });

  it('rejects a token whose kid is absent from the JWKS', async () => {
    mockJwks();
    const claims = await verifyIdToken(
      mintIdToken({ iss: ISSUER, aud: 'dashboard-bff', nonce: 'n-1' }, { kid: 'unknown-key' }),
      ISSUER,
      'dashboard-bff',
      'n-1',
    );
    expect(claims).toBeNull();
  });

  it('rejects a tampered payload', async () => {
    mockJwks();
    const token = mintIdToken({ iss: ISSUER, aud: 'dashboard-bff', nonce: 'n-1' });
    const [header, , signature] = token.split('.');
    const forged = Buffer.from(
      JSON.stringify({ iss: ISSUER, aud: 'dashboard-bff', nonce: 'n-1', exp: Math.floor(Date.now() / 1000) + 9999 }),
    ).toString('base64url');
    const claims = await verifyIdToken(`${header}.${forged}.${signature}`, ISSUER, 'dashboard-bff', 'n-1');
    expect(claims).toBeNull();
  });
});

describe('verifyAccessToken (server/oidc, local judgment)', () => {
  const ISSUER = 'http://auth.localhost:8000';

  it('accepts a kid-less league-shaped access token verified against the JWKS set', async () => {
    mockJwks();
    const claims = await verifyAccessToken(
      mintAccessToken({ aud: ['dashboard-bff'], jti: 'jti-1' }),
      ISSUER,
      'dashboard-bff',
    );
    expect(claims).not.toBeNull();
    expect(claims!.sub).toBe('sub:abc123');
    expect(claims!.jti).toBe('jti-1');
  });

  it('accepts a single-string aud', async () => {
    mockJwks();
    const claims = await verifyAccessToken(mintAccessToken({ aud: 'dashboard-bff' }), ISSUER, 'dashboard-bff');
    expect(claims).not.toBeNull();
  });

  it('rejects an access token signed by another key', async () => {
    mockJwks();
    const attacker = generateKeyPairSync('rsa', { modulusLength: 2048 });
    const token = signToken(
      { sub: 'sub:abc123', aud: ['dashboard-bff'], exp: Math.floor(Date.now() / 1000) + 600 },
      attacker.privateKey,
      { alg: 'RS256', typ: 'JWT' },
    );
    expect(await verifyAccessToken(token, ISSUER, 'dashboard-bff')).toBeNull();
  });

  it('rejects an expired access token', async () => {
    mockJwks();
    const now = Math.floor(Date.now() / 1000);
    const token = mintAccessToken({ aud: ['dashboard-bff'], exp: now - 3600 });
    expect(await verifyAccessToken(token, ISSUER, 'dashboard-bff')).toBeNull();
  });

  it('rejects an access token minted for another client', async () => {
    mockJwks();
    const token = mintAccessToken({ aud: ['customer-bff'] });
    expect(await verifyAccessToken(token, ISSUER, 'dashboard-bff')).toBeNull();
  });

  it('rejects an access token without a subject', async () => {
    mockJwks();
    const token = mintAccessToken({ aud: ['dashboard-bff'], sub: '' });
    expect(await verifyAccessToken(token, ISSUER, 'dashboard-bff')).toBeNull();
  });

  it('rejects alg confusion on the access token path too', async () => {
    mockJwks();
    const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT' })).toString('base64url');
    const payload = Buffer.from(
      JSON.stringify({
        sub: 'sub:abc123',
        aud: ['dashboard-bff'],
        exp: Math.floor(Date.now() / 1000) + 600,
        iat: Math.floor(Date.now() / 1000),
      }),
    ).toString('base64url');
    expect(await verifyAccessToken(`${header}.${payload}.sig`, ISSUER, 'dashboard-bff')).toBeNull();
  });
});

describe('GET /api/auth/me (session validation)', () => {
  it('federated: a verified cookie resolves the user via userinfo', async () => {
    setEnv(FEDERATED);
    const fetchSpy = mockIssuer({ userinfo: { sub: 'sub:abc123', name: 'Asha Verma', email: 'asha@example.com' } });

    const token = mintAccessToken({ aud: ['dashboard-bff'], jti: 'jti-1' });
    const response = await meGet(new Request(`${BASE}/api/auth/me`, { headers: { cookie: sessionCookie(token) } }));

    expect(response.status).toBe(200);
    expect(await response.json()).toEqual({
      id: 'sub:abc123',
      email: 'asha@example.com',
      name: 'Asha Verma',
      // Fail-closed: issuer tokens carry no console role claim.
      role: 'staff',
    });

    // Local verification first (JWKS), then the upstream confirmation.
    const urls = fetchSpy.mock.calls.map((c) => String(c[0]));
    expect(urls.some((u) => u.endsWith('/.well-known/jwks.json'))).toBe(true);
    expect(urls.some((u) => u.endsWith('/oauth/userinfo'))).toBe(true);
    const userinfoCall = fetchSpy.mock.calls.find((c) => String(c[0]).endsWith('/oauth/userinfo'))!;
    expect((userinfoCall[1] as RequestInit).headers).toMatchObject({
      Authorization: `Bearer ${token}`,
    });
  });

  it('federated: without a session cookie there is no user (401, no fetches)', async () => {
    setEnv(FEDERATED);
    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await meGet(new Request(`${BASE}/api/auth/me`));
    expect(response.status).toBe(401);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated: an expired access token is 401 and never reaches userinfo', async () => {
    setEnv(FEDERATED);
    const fetchSpy = mockIssuer({ userinfo: { sub: 'sub:abc123' } });
    const now = Math.floor(Date.now() / 1000);
    const token = mintAccessToken({ aud: ['dashboard-bff'], exp: now - 3600 });

    const response = await meGet(new Request(`${BASE}/api/auth/me`, { headers: { cookie: sessionCookie(token) } }));
    expect(response.status).toBe(401);
    expect(fetchSpy.mock.calls.map((c) => String(c[0])).some((u) => u.endsWith('/oauth/userinfo'))).toBe(false);
  });

  it('federated: a forged cookie dies at signature verification', async () => {
    setEnv(FEDERATED);
    mockIssuer({ userinfo: { sub: 'sub:abc123' } });
    const attacker = generateKeyPairSync('rsa', { modulusLength: 2048 });
    const token = signToken(
      {
        sub: 'sub:attacker',
        aud: ['dashboard-bff'],
        exp: Math.floor(Date.now() / 1000) + 600,
        iat: Math.floor(Date.now() / 1000),
      },
      attacker.privateKey,
      { alg: 'RS256', typ: 'JWT' },
    );

    const response = await meGet(new Request(`${BASE}/api/auth/me`, { headers: { cookie: sessionCookie(token) } }));
    expect(response.status).toBe(401);
  });

  it('federated: a userinfo subject that disagrees with the token fails closed', async () => {
    setEnv(FEDERATED);
    mockIssuer({ userinfo: { sub: 'sub:someone-else', name: 'Not You' } });
    const token = mintAccessToken({ aud: ['dashboard-bff'] });

    const response = await meGet(new Request(`${BASE}/api/auth/me`, { headers: { cookie: sessionCookie(token) } }));
    expect(response.status).toBe(401);
  });

  it('federated: an unreachable userinfo is a typed 502, not a crash', async () => {
    setEnv(FEDERATED);
    mockJwks();
    vi.spyOn(global, 'fetch').mockImplementation(async (input) => {
      const url = String(input);
      if (url.endsWith('/.well-known/jwks.json')) {
        return new Response(JSON.stringify(jwksDocument()), {
          status: 200,
          headers: { 'content-type': 'application/json' },
        });
      }
      throw new Error('ECONNREFUSED');
    });
    const token = mintAccessToken({ aud: ['dashboard-bff'] });

    const response = await meGet(new Request(`${BASE}/api/auth/me`, { headers: { cookie: sessionCookie(token) } }));
    expect(response.status).toBe(502);
    expect((await response.json()).message).toMatch(/Identity provider/);
  });

  it('direct: without a configured backend, me fails closed (503)', async () => {
    setEnv(DIRECT);
    const response = await meGet(new Request(`${BASE}/api/auth/me`));
    expect(response.status).toBe(503);
    expect((await response.json()).message).toMatch(/not configured/);
  });
});

describe('legacy ceremony + logout', () => {
  it('legacy login is absent in federated deployments', async () => {
    setEnv(FEDERATED);
    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await loginPost(
      new Request(`${BASE}/api/auth/login`, {
        method: 'POST',
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify({ email: 'a@b.c', password: 'x' }),
      }),
    );
    expect(response.status).toBe(404);
    expect((await response.json()).message).toMatch(/auth subdomain/);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('direct: login validates its body, then fails closed without a backend', async () => {
    setEnv(DIRECT);
    const bad = await loginPost(
      new Request(`${BASE}/api/auth/login`, {
        method: 'POST',
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify({ email: 'a@b.c' }),
      }),
    );
    expect(bad.status).toBe(400);

    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await loginPost(
      new Request(`${BASE}/api/auth/login`, {
        method: 'POST',
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify({ email: 'a@b.c', password: 'secret' }),
      }),
    );
    expect(response.status).toBe(503);
    expect((await response.json()).message).toMatch(/not configured/);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated logout revokes at the issuer best-effort and clears both cookie names', async () => {
    setEnv(FEDERATED);
    const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue(new Response('', { status: 200 }));

    const response = await logoutPost(
      new Request(`${BASE}/api/auth/logout`, {
        method: 'POST',
        headers: { cookie: sessionCookie('issuer.jwt.token') },
      }),
    );

    expect(response.status).toBe(200);
    const setCookies = response.headers.getSetCookie();
    // Both the dev and __Host- session names are cleared — a session
    // minted under either context must not outlive its logout.
    expect(setCookies.some((l) => l.startsWith('nv_dashboard_token=;'))).toBe(true);
    expect(setCookies.some((l) => l.startsWith('__Host-nv_dashboard_token=;'))).toBe(true);

    expect(fetchSpy).toHaveBeenCalledTimes(1);
    const [url, init] = fetchSpy.mock.calls[0] as unknown as [string, RequestInit];
    expect(url).toBe('http://auth.localhost:8000/oauth/revoke');
    const body = new URLSearchParams(String(init.body));
    expect(body.get('token')).toBe('issuer.jwt.token');
    expect(body.get('token_type_hint')).toBe('access_token');
    // Client auth is client_secret_post: the revoke endpoint validates
    // request input only, so the secret must travel in the body —
    // Basic-only requests are refused before any revocation.
    expect(body.get('client_id')).toBe('dashboard-bff');
    expect(body.get('client_secret')).toBe('dashboard-bff-secret');
    expect((init.headers as Record<string, string>).Authorization).toBeUndefined();
  });

  it('federated logout without a session stays idempotent (200, no revoke)', async () => {
    setEnv(FEDERATED);
    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await logoutPost(new Request(`${BASE}/api/auth/logout`, { method: 'POST' }));
    expect(response.status).toBe(200);
    expect(fetchSpy).not.toHaveBeenCalled();
    expect(response.headers.getSetCookie().length).toBeGreaterThan(0);
  });

  it('direct logout returns the same success shape (no upstream, cookie cleared)', async () => {
    setEnv(DIRECT);
    const fetchSpy = vi.spyOn(global, 'fetch');
    const response = await logoutPost(new Request(`${BASE}/api/auth/logout`, { method: 'POST' }));
    expect(response.status).toBe(200);
    expect((await response.json()).signedOut).toBe(true);
    expect(fetchSpy).not.toHaveBeenCalled();
  });
});

// ---------------------------------------------------------------------------
// Issuer test helpers: one RSA key pair stands in for the issuer's signing
// key; the JWKS and the minted id_tokens always agree on kid 'test-key',
// while ACCESS tokens are minted kid-less (league's shape, A-SEC-002-1).
// ---------------------------------------------------------------------------

const issuerKeys = generateKeyPairSync('rsa', { modulusLength: 2048 });

function jwksDocument() {
  const jwk = issuerKeys.publicKey.export({ format: 'jwk' }) as { kty: string; n: string; e: string };
  return { keys: [{ ...jwk, use: 'sig', alg: 'RS256', kid: 'test-key' }] };
}

function signToken(
  payload: Record<string, unknown>,
  privateKey: typeof issuerKeys.privateKey,
  header: Record<string, unknown> = { alg: 'RS256', typ: 'JWT', kid: 'test-key' },
): string {
  const now = Math.floor(Date.now() / 1000);
  const h = Buffer.from(JSON.stringify(header)).toString('base64url');
  const p = Buffer.from(JSON.stringify({ iat: now, exp: now + 600, ...payload })).toString('base64url');
  const signer = createSign('RSA-SHA256');
  signer.update(`${h}.${p}`);
  const signature = signer.sign(privateKey).toString('base64url');
  return `${h}.${p}.${signature}`;
}

function mintIdToken(
  payload: Record<string, unknown>,
  header: Record<string, unknown> = { alg: 'RS256', typ: 'JWT', kid: 'test-key' },
): string {
  return signToken(payload, issuerKeys.privateKey, header);
}

/** A league-shaped access token: kid-less header, client as single-element aud. */
function mintAccessToken(payload: Record<string, unknown>): string {
  return signToken({ sub: 'sub:abc123', ...payload }, issuerKeys.privateKey, { alg: 'RS256', typ: 'JWT' });
}

/** Mock the issuer: token endpoint returns the given body, JWKS the key. */
function mockTokenEndpoint(body: Record<string, unknown>) {
  vi.spyOn(global, 'fetch').mockImplementation(async (input) => {
    const url = String(input);
    if (url.endsWith('/.well-known/jwks.json')) {
      return new Response(JSON.stringify(jwksDocument()), {
        status: 200,
        headers: { 'content-type': 'application/json' },
      });
    }
    return new Response(JSON.stringify(body), { status: 200, headers: { 'content-type': 'application/json' } });
  });
}

function mockJwks() {
  vi.spyOn(global, 'fetch').mockResolvedValue(
    new Response(JSON.stringify(jwksDocument()), {
      status: 200,
      headers: { 'content-type': 'application/json' },
    }),
  );
}

/** JWKS + userinfo mock (the /me route's two upstreams). */
function mockIssuer({ userinfo }: { userinfo: Record<string, unknown> }) {
  return vi.spyOn(global, 'fetch').mockImplementation(async (input) => {
    const url = String(input);
    if (url.endsWith('/.well-known/jwks.json')) {
      return new Response(JSON.stringify(jwksDocument()), {
        status: 200,
        headers: { 'content-type': 'application/json' },
      });
    }
    if (url.endsWith('/oauth/userinfo')) {
      return new Response(JSON.stringify(userinfo), {
        status: 200,
        headers: { 'content-type': 'application/json' },
      });
    }
    return new Response('unexpected', { status: 500 });
  });
}

/** A callback request with a matching flow cookie (state s, given nonce). */
function callbackRequest({ nonce }: { nonce: string }): Request {
  const flow = buildFlowCookie({ state: 's', verifier: 'v', nonce, redirect: '/' }).split(';')[0];
  return new Request(
    `${BASE}/api/auth/callback?code=x&state=s&iss=${encodeURIComponent(FEDERATED.DASHBOARD_AUTH_ISSUER)}`,
    { headers: { cookie: flow } },
  );
}

function sessionCookie(token: string): string {
  return `${sessionCookieName()}=${encodeURIComponent(token)}`;
}

function sessionCookieSet(response: Response): boolean {
  return response.headers.getSetCookie().some((l) => l.startsWith(`${sessionCookieName()}=`));
}

// readSessionToken is exercised implicitly through the me/logout routes;
// asserted here directly for the dev/prod name coverage.
describe('session cookie (server/session)', () => {
  it('reads either cookie name and returns null on absence', () => {
    expect(readSessionToken('other=x; nv_dashboard_token=abc.def.ghi')).toBe('abc.def.ghi');
    expect(readSessionToken('__Host-nv_dashboard_token=abc.def.ghi')).toBe('abc.def.ghi');
    expect(readSessionToken(null)).toBeNull();
    expect(readSessionToken('other=x')).toBeNull();
  });
});
