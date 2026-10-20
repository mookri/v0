/**
 * @jest-environment node
 *
 * AUTH_MODE cutover (ADR-0020) + 9a OIDC (ADR-0021) — BFF federation flow.
 *
 * The cutover invariants that are automatable here:
 *   - direct deployments are untouched (authorize bounces to /login,
 *     the legacy ceremony keeps working, callback does not exist)
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
 *   - logout revokes at the issuer best-effort and always clears
 *
 * The customer app carries a parallel copy of server/federation.ts +
 * server/oidc.ts and the same two routes (the apps share an env
 * contract, not modules); this suite pins the contract for both.
 */
import { GET as authorizeGet } from '@/app/api/auth/authorize/route';
import { GET as callbackGet } from '@/app/api/auth/callback/route';
import { POST as loginPost } from '@/app/api/auth/login/route';
import { POST as logoutPost } from '@/app/api/auth/logout/route';
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
import { verifyIdToken, clearOidcJwksCache } from '@/server/oidc';
import { sessionCookieName } from '@/server/session-cookie';
import { createSign, generateKeyPairSync } from 'node:crypto';

const ENV_KEYS = [
  'CUSTOMER_AUTH_MODE',
  'CUSTOMER_AUTH_ISSUER',
  'CUSTOMER_AUTH_CLIENT_ID',
  'CUSTOMER_AUTH_CLIENT_SECRET',
  'CUSTOMER_BACKEND',
  'NAVDHYA_API_BASE',
  'CUSTOMER_TENANT_ID',
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
  CUSTOMER_AUTH_MODE: 'federated',
  CUSTOMER_AUTH_ISSUER: 'http://auth.localhost:8000',
  CUSTOMER_AUTH_CLIENT_ID: 'web-bff',
  CUSTOMER_AUTH_CLIENT_SECRET: 'web-bff-secret',
  CUSTOMER_BACKEND: 'laravel',
  NAVDHYA_API_BASE: 'http://127.0.0.1:8000',
  CUSTOMER_TENANT_ID: 'tenant-1',
};

const DIRECT = {
  CUSTOMER_AUTH_MODE: undefined,
  CUSTOMER_AUTH_ISSUER: undefined,
  CUSTOMER_AUTH_CLIENT_ID: undefined,
  CUSTOMER_AUTH_CLIENT_SECRET: undefined,
  CUSTOMER_BACKEND: undefined,
  NAVDHYA_API_BASE: undefined,
  CUSTOMER_TENANT_ID: undefined,
};

function findCookie(headers: Headers, name: string): string | null {
  for (const line of headers.getSetCookie()) {
    const match = line.match(new RegExp(`(?:^|;\\s*)${name}=([^;]*)`));
    if (match) return decodeURIComponent(match[1]);
  }
  return null;
}

afterEach(() => {
  restoreEnv();
  jest.restoreAllMocks();
  clearOidcJwksCache();
});

describe('federation module', () => {
  it('defaults to direct and rejects unknown modes', () => {
    setEnv(DIRECT);
    expect(authMode()).toBe('direct');

    process.env.CUSTOMER_AUTH_MODE = 'halfway';
    expect(() => authMode()).toThrow(/CUSTOMER_AUTH_MODE/);
  });

  it('fails closed when federated config is incomplete', () => {
    setEnv({ ...FEDERATED, CUSTOMER_AUTH_CLIENT_ID: undefined });
    expect(() => federationConfig()).toThrow(/CUSTOMER_AUTH_MODE=federated/);
  });

  it('trims trailing slashes off the issuer', () => {
    setEnv({ ...FEDERATED, CUSTOMER_AUTH_ISSUER: 'http://auth.localhost:8000///' });
    expect(federationConfig().issuer).toBe('http://auth.localhost:8000');
  });

  it('safeNext fences every open-redirect shape', () => {
    expect(safeNext('/bookings')).toBe('/bookings');
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
    expect(header).toContain('navdhya-auth-flow='); // dev (test) name — no __Host- over HTTP
    expect(header).toContain('HttpOnly');
    expect(header).toContain('SameSite=Lax');

    const flow = readFlowCookie(`other=x; ${header.split(';')[0]}`);
    expect(flow).toEqual({ state: 'state-1', verifier: 'verifier-1', nonce: 'nonce-1', redirect: '/next' });

    // A redirect path containing dots survives (only the first three
    // segments are state/verifier/nonce; the rest rejoins).
    const dotted = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/pujas/a.b' });
    expect(readFlowCookie(dotted.split(';')[0])?.redirect).toBe('/pujas/a.b');

    const bare = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: null });
    expect(readFlowCookie(bare.split(';')[0])).toEqual({ state: 's', verifier: 'v', nonce: 'n', redirect: null });
  });

  it('a pre-9a cookie (no nonce segment) is not a flow', () => {
    // 9a: the id_token binding needs a nonce to check against — a
    // legacy three-segment cookie fails closed instead of degrading.
    const legacy = encodeURIComponent(['s', 'v', encodeURIComponent('/')].join('.'));
    expect(readFlowCookie(`navdhya-auth-flow=${legacy}`)).toBeNull();
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
    const response = await authorizeGet(
      new Request('http://localhost:3000/api/auth/authorize?redirect=/bookings'),
    );
    expect(response.status).toBe(302);
    expect(response.headers.get('location')).toBe('/login?redirect=%2Fbookings');
  });

  it('direct: an open-redirect target falls back to /login', async () => {
    setEnv(DIRECT);
    const response = await authorizeGet(
      new Request('http://localhost:3000/api/auth/authorize?redirect=//evil.example'),
    );
    expect(response.headers.get('location')).toBe('/login');
  });

  it('federated: hands the browser to the issuer with matching PKCE + nonce', async () => {
    setEnv(FEDERATED);
    const response = await authorizeGet(
      new Request('http://localhost:3000/api/auth/authorize?redirect=/bookings'),
    );
    expect(response.status).toBe(302);

    const location = new URL(response.headers.get('location')!);
    expect(location.origin + location.pathname).toBe('http://auth.localhost:8000/oauth/authorize');

    const flowCookie = findCookie(response.headers, 'navdhya-auth-flow');
    expect(flowCookie).toBeTruthy();
    const flow = readFlowCookie(`navdhya-auth-flow=${flowCookie}`);

    expect(location.searchParams.get('state')).toBe(flow!.state);
    // The nonce travels in the URL AND the flow cookie — the callback
    // exact-matches the id_token's nonce against the cookie's copy.
    expect(location.searchParams.get('scope')).toBe('openid');
    expect(location.searchParams.get('nonce')).toBe(flow!.nonce);
    expect(location.searchParams.get('redirect_uri')).toBe('http://localhost:3000/api/auth/callback');
    expect(location.searchParams.get('code_challenge')).toBe(pkceChallenge(flow!.verifier));
    expect(flow!.redirect).toBe('/bookings');
  });
});

describe('GET /api/auth/callback', () => {
  it('direct: the callback surface does not exist', async () => {
    setEnv(DIRECT);
    const response = await callbackGet(new Request('http://localhost:3000/api/auth/callback?code=x&state=y'));
    expect(response.status).toBe(404);
  });

  it('federated: without a flow cookie there is no session (401)', async () => {
    setEnv(FEDERATED);
    const response = await callbackGet(new Request('http://localhost:3000/api/auth/callback?code=x&state=y'));
    expect(response.status).toBe(401);
    const body = await response.json();
    expect(body.error.code).toBe('UNAUTHENTICATED');
  });

  it('federated: state mismatch never exchanges the code', async () => {
    setEnv(FEDERATED);
    const fetchSpy = jest.spyOn(global, 'fetch');
    const response = await callbackGet(
      new Request('http://localhost:3000/api/auth/callback?code=x&state=tampered', {
        headers: {
          cookie: buildFlowCookie({ state: 'expected', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0],
        },
      }),
    );
    expect(response.status).toBe(401);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated: issuer refusal is uniform and reflects nothing', async () => {
    setEnv(FEDERATED);
    const response = await callbackGet(
      new Request('http://localhost:3000/api/auth/callback?error=access_denied&error_description=secret-detail', {
        headers: { cookie: buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0] },
      }),
    );
    expect(response.status).toBe(401);
    const text = await response.text();
    expect(text).not.toContain('secret-detail');
  });

  it('federated: a response WITHOUT iss is rejected before any exchange', async () => {
    setEnv(FEDERATED);
    const fetchSpy = jest.spyOn(global, 'fetch');
    const response = await callbackGet(
      new Request('http://localhost:3000/api/auth/callback?code=x&state=s', {
        headers: { cookie: buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0] },
      }),
    );
    expect(response.status).toBe(401);
    // Mix-up defence (9a D2): no iss, no exchange — not even attempted.
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated: a foreign iss is rejected before any exchange', async () => {
    setEnv(FEDERATED);
    const fetchSpy = jest.spyOn(global, 'fetch');
    const response = await callbackGet(
      new Request(
        'http://localhost:3000/api/auth/callback?code=x&state=s&iss=http://evil.example',
        {
          headers: {
            cookie: buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0],
          },
        },
      ),
    );
    expect(response.status).toBe(401);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated: happy path verifies the id_token and sets the session', async () => {
    setEnv(FEDERATED);
    const authorize = await authorizeGet(
      new Request('http://localhost:3000/api/auth/authorize?redirect=/bookings'),
    );
    const flowCookieHeader = authorize.headers.getSetCookie().find((l) => l.startsWith('navdhya-auth-flow='))!;
    const location = new URL(authorize.headers.get('location')!);
    const state = location.searchParams.get('state')!;
    const nonce = location.searchParams.get('nonce')!;

    const fetchSpy = jest.spyOn(global, 'fetch').mockImplementation(async (input) => {
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
          id_token: mintIdToken({ iss: FEDERATED.CUSTOMER_AUTH_ISSUER, aud: 'web-bff', nonce }),
        }),
        { status: 200, headers: { 'content-type': 'application/json' } },
      );
    });

    const response = await callbackGet(
      new Request(
        `http://localhost:3000/api/auth/callback?code=authcode-1&state=${encodeURIComponent(state)}&iss=${encodeURIComponent(FEDERATED.CUSTOMER_AUTH_ISSUER)}`,
        { headers: { cookie: flowCookieHeader.split(';')[0] } },
      ),
    );

    expect(response.status).toBe(302);
    expect(response.headers.get('location')).toBe('/bookings');

    const sessionCookie = response.headers.getSetCookie().find((l) => l.startsWith(`${sessionCookieName()}=`))!;
    expect(sessionCookie).toContain('issuer.jwt.token');
    expect(sessionCookie).toContain('HttpOnly');
    // The flow cookie is cleared in the same response.
    expect(
      response.headers.getSetCookie().some((l) => l.startsWith('navdhya-auth-flow=') && l.includes('Max-Age=0')),
    ).toBe(true);

    expect(fetchSpy).toHaveBeenCalledTimes(2);
    const [url, init] = fetchSpy.mock.calls[0] as unknown as [string, RequestInit];
    expect(url).toBe('http://auth.localhost:8000/oauth/token');
    const body = new URLSearchParams(String(init.body));
    expect(body.get('grant_type')).toBe('authorization_code');
    expect(body.get('code')).toBe('authcode-1');
    expect(body.get('client_id')).toBe('web-bff');
    expect(body.get('client_secret')).toBe('web-bff-secret');
    expect(body.get('redirect_uri')).toBe('http://localhost:3000/api/auth/callback');
    // The verifier sent back is the one the authorize step parked.
    const flow = readFlowCookie(flowCookieHeader.split(';')[0])!;
    expect(body.get('code_verifier')).toBe(flow.verifier);
    // The secret never appears anywhere the browser could read it.
    expect(response.headers.getSetCookie().join('\n')).not.toContain('web-bff-secret');
  });

  it('federated: an id_token with a foreign nonce never mints a session', async () => {
    setEnv(FEDERATED);
    mockTokenEndpoint({
      access_token: 'issuer.jwt.token',
      expires_in: 900,
      id_token: mintIdToken({
        iss: FEDERATED.CUSTOMER_AUTH_ISSUER,
        aud: 'web-bff',
        nonce: 'some-other-nonce',
      }),
    });

    const response = await callbackGet(callbackRequest({ nonce: 'flow-nonce' }));
    expect(response.status).toBe(401);
    expect(sessionCookieSet(response)).toBe(false);
  });

  it('federated: an id_token signed by another key never mints a session', async () => {
    setEnv(FEDERATED);
    // The JWKS publishes our public key; the token is signed by a
    // different (attacker) key under the same kid.
    const attacker = generateKeyPairSync('rsa', { modulusLength: 2048 });
    mockTokenEndpoint({
      access_token: 'issuer.jwt.token',
      expires_in: 900,
      id_token: signIdToken(
        { iss: FEDERATED.CUSTOMER_AUTH_ISSUER, aud: 'web-bff', nonce: 'n-1' },
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
      id_token: mintIdToken({ iss: FEDERATED.CUSTOMER_AUTH_ISSUER, aud: 'web-bff', nonce: 'n-1', exp: now - 3600 }),
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
      id_token: mintIdToken({ iss: FEDERATED.CUSTOMER_AUTH_ISSUER, aud: 'customer-bff', nonce: 'n-1' }),
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
      id_token: mintIdToken({ iss: 'http://rogue-op.example', aud: 'web-bff', nonce: 'n-1' }),
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
    jest.spyOn(global, 'fetch').mockResolvedValue(new Response('nope', { status: 400 }));

    const flow = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0];
    const response = await callbackGet(
      new Request(
        `http://localhost:3000/api/auth/callback?code=x&state=s&iss=${encodeURIComponent(FEDERATED.CUSTOMER_AUTH_ISSUER)}`,
        { headers: { cookie: flow } },
      ),
    );
    expect(response.status).toBe(502);
    const body = await response.json();
    expect(body.error.code).toBe('DEPENDENCY_UNAVAILABLE');
    expect(response.headers.getSetCookie().some((l) => l.includes('navdhya-auth-flow='))).toBe(true);
  });

  it('federated: an unreachable issuer is a typed failure, not a 500', async () => {
    setEnv(FEDERATED);
    jest.spyOn(global, 'fetch').mockRejectedValue(new Error('ECONNREFUSED'));

    const flow = buildFlowCookie({ state: 's', verifier: 'v', nonce: 'n', redirect: '/' }).split(';')[0];
    const response = await callbackGet(
      new Request(
        `http://localhost:3000/api/auth/callback?code=x&state=s&iss=${encodeURIComponent(FEDERATED.CUSTOMER_AUTH_ISSUER)}`,
        { headers: { cookie: flow } },
      ),
    );
    expect(response.status).toBe(502);
  });
});

describe('verifyIdToken (server/oidc)', () => {
  const ISSUER = 'http://auth.localhost:8000';

  function mockJwks() {
    jest.spyOn(global, 'fetch').mockResolvedValue(
      new Response(JSON.stringify(jwksDocument()), {
        status: 200,
        headers: { 'content-type': 'application/json' },
      }),
    );
  }

  it('accepts a well-formed token and returns the claims', async () => {
    mockJwks();
    const claims = await verifyIdToken(
      mintIdToken({ iss: ISSUER, aud: 'web-bff', nonce: 'n-1' }),
      ISSUER,
      'web-bff',
      'n-1',
    );
    expect(claims).not.toBeNull();
    expect(claims!.nonce).toBe('n-1');
  });

  it('rejects alg confusion (HS256 header on an RSA-signed body)', async () => {
    mockJwks();
    // Build a token whose header claims HS256 — the verifier pins
    // RS256 and must refuse before touching the signature.
    const header = Buffer.from(JSON.stringify({ alg: 'HS256', typ: 'JWT', kid: 'test-key' })).toString('base64url');
    const payload = Buffer.from(
      JSON.stringify({ iss: ISSUER, aud: 'web-bff', nonce: 'n-1', exp: Math.floor(Date.now() / 1000) + 600 }),
    ).toString('base64url');
    const claims = await verifyIdToken(`${header}.${payload}.sig`, ISSUER, 'web-bff', 'n-1');
    expect(claims).toBeNull();
  });

  it('rejects a token whose kid is absent from the JWKS', async () => {
    mockJwks();
    const claims = await verifyIdToken(
      mintIdToken({ iss: ISSUER, aud: 'web-bff', nonce: 'n-1' }, { kid: 'unknown-key' }),
      ISSUER,
      'web-bff',
      'n-1',
    );
    expect(claims).toBeNull();
  });

  it('rejects a tampered payload', async () => {
    mockJwks();
    const token = mintIdToken({ iss: ISSUER, aud: 'web-bff', nonce: 'n-1' });
    const [header, , signature] = token.split('.');
    const forged = Buffer.from(
      JSON.stringify({ iss: ISSUER, aud: 'web-bff', nonce: 'n-1', exp: Math.floor(Date.now() / 1000) + 9999 }),
    ).toString('base64url');
    const claims = await verifyIdToken(`${header}.${forged}.${signature}`, ISSUER, 'web-bff', 'n-1');
    expect(claims).toBeNull();
  });
});

// ---------------------------------------------------------------------------
// 9a OIDC test helpers: one RSA key pair stands in for the issuer's signing
// key; the JWKS and the minted tokens always agree on kid 'test-key'.
// ---------------------------------------------------------------------------

const issuerKeys = generateKeyPairSync('rsa', { modulusLength: 2048 });

function jwksDocument() {
  const jwk = issuerKeys.publicKey.export({ format: 'jwk' }) as { kty: string; n: string; e: string };
  return { keys: [{ ...jwk, use: 'sig', alg: 'RS256', kid: 'test-key' }] };
}

function signIdToken(
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
  return signIdToken(payload, issuerKeys.privateKey, header);
}

/** Mock the issuer: token endpoint returns the given body, JWKS the key. */
function mockTokenEndpoint(body: Record<string, unknown>) {
  jest.spyOn(global, 'fetch').mockImplementation(async (input) => {
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

/** A callback request with a matching flow cookie (state s, given nonce). */
function callbackRequest({ nonce }: { nonce: string }): Request {
  const flow = buildFlowCookie({ state: 's', verifier: 'v', nonce, redirect: '/' }).split(';')[0];
  return new Request(
    `http://localhost:3000/api/auth/callback?code=x&state=s&iss=${encodeURIComponent(FEDERATED.CUSTOMER_AUTH_ISSUER)}`,
    { headers: { cookie: flow } },
  );
}

function sessionCookieSet(response: Response): boolean {
  return response.headers.getSetCookie().some((l) => l.startsWith(`${sessionCookieName()}=`));
}

describe('legacy ceremony + logout', () => {
  it('legacy login is absent in federated deployments', async () => {
    setEnv(FEDERATED);
    const fetchSpy = jest.spyOn(global, 'fetch');
    const response = await loginPost(
      new Request('http://localhost:3000/api/auth/login', {
        method: 'POST',
        headers: { 'content-type': 'application/json' },
        body: JSON.stringify({ challengeId: 'c', token: 't' }),
      }),
    );
    expect(response.status).toBe(404);
    const body = await response.json();
    expect(body.error.code).toBe('NOT_FOUND');
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('federated logout revokes at the issuer best-effort and clears the cookie', async () => {
    setEnv(FEDERATED);
    const fetchSpy = jest.spyOn(global, 'fetch').mockResolvedValue(new Response('', { status: 200 }));

    const response = await logoutPost(
      new Request('http://localhost:3000/api/auth/logout', {
        method: 'POST',
        headers: { cookie: `${sessionCookieName()}=issuer.jwt.token` },
      }),
    );

    expect(response.status).toBe(200);
    expect(response.headers.getSetCookie().join('\n')).toContain(`${sessionCookieName()}=;`);

    expect(fetchSpy).toHaveBeenCalledTimes(1);
    const [url, init] = fetchSpy.mock.calls[0] as unknown as [string, RequestInit];
    expect(url).toBe('http://auth.localhost:8000/oauth/revoke');
    const body = new URLSearchParams(String(init.body));
    expect(body.get('token')).toBe('issuer.jwt.token');
    expect(body.get('token_type_hint')).toBe('access_token');
    // Client auth is client_secret_post: the revoke endpoint validates
    // request input only, so the secret must travel in the body —
    // Basic-only requests are refused before any revocation.
    expect(body.get('client_id')).toBe('web-bff');
    expect(body.get('client_secret')).toBe('web-bff-secret');
    expect((init.headers as Record<string, string>).Authorization).toBeUndefined();
  });

  it('federated logout without a session stays idempotent (200, no revoke)', async () => {
    setEnv(FEDERATED);
    const fetchSpy = jest.spyOn(global, 'fetch');
    const response = await logoutPost(new Request('http://localhost:3000/api/auth/logout', { method: 'POST' }));
    expect(response.status).toBe(200);
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it('direct logout keeps today\'s behavior', async () => {
    setEnv(DIRECT);
    const fetchSpy = jest.spyOn(global, 'fetch');
    const response = await logoutPost(new Request('http://localhost:3000/api/auth/logout', { method: 'POST' }));
    expect(response.status).toBe(200);
    // No laravel backend: no upstream call, cookie still cleared.
    expect(fetchSpy).not.toHaveBeenCalled();
  });
});
