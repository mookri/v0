import { afterEach, describe, expect, test, vi } from 'vitest';

import {
  buildRefreshCookie,
  buildRotationPendingCookie,
  customerRefreshCookieName,
  customerRotationPendingCookieName,
  customerTokenCookieName,
  readCustomerRefreshToken,
  REFRESH_COOKIE_MAX_AGE_SECONDS,
} from '@apps/customer/server/backend-session';
import { ensureFreshSession, mergeSessionCookies } from '@apps/customer/server/session-refresh';

// Federated session continuity, BFF side (A-SEC-004-11, D4 BFF half):
// the customer BFF holds the issuer's refresh token in a second HttpOnly
// cookie and rotates it server-side — single-flight per family, DPoP
// proof from the binding key, Idempotency-Key parked across lost
// responses — so a session survives the access token's expiry without an
// interactive login, and a dead family (OP logout, reuse, lost key) is
// cleared instead of replayed.

const ISSUER = 'http://op.test';
const CLIENT_ID = 'client-customer';

const ENV = {
  CUSTOMER_AUTH_MODE: 'federated',
  CUSTOMER_AUTH_ISSUER: ISSUER,
  CUSTOMER_AUTH_CLIENT_ID: CLIENT_ID,
  CUSTOMER_AUTH_CLIENT_SECRET: 'test-client-secret-1',
  SESSION_COOKIE_SECURE: '0',
};

// Each test rotates a DISTINCT refresh token: the module keeps a
// 30 s in-process replay cache keyed by the consumed token (part of the
// contract — see the single-flight/replay tests), which cross-test
// reuse would otherwise trip.
let rtSeq = 0;
const uniqueRt = (tag: string): string => `rt-${tag}-${++rtSeq}-${Math.random().toString(36).slice(2)}`;

function federatedEnv(): void {
  for (const [k, v] of Object.entries(ENV)) vi.stubEnv(k, v);
}

function fakeAt(expSecondsFromNow: number): string {
  const header = Buffer.from(JSON.stringify({ alg: 'RS256', typ: 'JWT' })).toString('base64url');
  const payload = Buffer.from(
    JSON.stringify({
      sub: 'user-1',
      aud: CLIENT_ID,
      jti: 'jti-1',
      exp: Math.floor(Date.now() / 1000) + expSecondsFromNow,
    }),
  ).toString('base64url');
  return `${header}.${payload}.sig`;
}

function cookies(pairs: Array<[string, string]>): string {
  return pairs.map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('; ');
}

function liveSession(): string {
  return cookies([
    [customerTokenCookieName(), fakeAt(900)],
    [customerRefreshCookieName(), uniqueRt('live')],
  ]);
}

function tokenJson(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

function rotatedBody(rt = uniqueRt('success'), at = `at-${Math.random().toString(36).slice(2)}`) {
  return { access_token: at, refresh_token: rt, expires_in: 900, token_type: 'bearer' };
}

interface RecordedCall {
  url: string;
  headers: Record<string, string>;
  body: URLSearchParams;
}

function recordFetch(...responses: Array<Response | (() => Response)>): RecordedCall[] {
  const calls: RecordedCall[] = [];
  let i = 0;
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const headers = (init?.headers ?? {}) as Record<string, string>;
      calls.push({ url, headers, body: new URLSearchParams(String(init?.body ?? '')) });
      const next = responses[Math.min(i, responses.length - 1)];
      i += 1;
      if (next instanceof Response) return next.clone();
      return next();
    }),
  );
  return calls;
}

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
});

describe('refresh cookie family (backend-session)', () => {
  test('dev naming, HttpOnly + SameSite=Lax, 30-day Max-Age, no Secure on http dev', () => {
    federatedEnv();
    const cookie = buildRefreshCookie('rt-1');
    expect(cookie).toContain(`${customerRefreshCookieName()}=rt-1`);
    expect(cookie).toContain('Path=/; HttpOnly; SameSite=Lax');
    expect(cookie).toContain(`Max-Age=${REFRESH_COOKIE_MAX_AGE_SECONDS}`);
    expect(cookie).not.toContain('Secure');
  });

  test('prod naming adds the __Host- prefix and Secure', () => {
    federatedEnv();
    vi.stubEnv('SESSION_COOKIE_SECURE', '1');
    expect(customerRefreshCookieName()).toBe('__Host-nv_customer_refresh');
    expect(buildRefreshCookie('rt-1')).toContain('Secure');
  });

  test('readCustomerRefreshToken reads both namings and ignores foreign cookies', () => {
    federatedEnv();
    const header = cookies([
      ['nv_customer_token', 'at-x'],
      ['nv_customer_refresh', 'rt-dev'],
      ['other', 'nope'],
    ]);
    expect(readCustomerRefreshToken(header)).toBe('rt-dev');
    expect(readCustomerRefreshToken(cookies([['__Host-nv_customer_refresh', 'rt-prod']]))).toBe('rt-prod');
    expect(readCustomerRefreshToken(header.replace(/nv_customer_refresh=[^;]*/, ''))).toBeNull();
    expect(readCustomerRefreshToken(null)).toBeNull();
  });

  test('the rotation-pending cookie is short-lived and HttpOnly', () => {
    federatedEnv();
    const cookie = buildRotationPendingCookie('key-12345678');
    expect(cookie).toContain(`${customerRotationPendingCookieName()}=key-12345678`);
    expect(cookie).toContain('HttpOnly; SameSite=Lax; Max-Age=60');
  });
});

describe('rotation trigger', () => {
  test('a live access token needs no issuer round-trip', async () => {
    federatedEnv();
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    const result = await ensureFreshSession(liveSession());

    expect(result.status).toBe('fresh');
    expect(result.rewrittenCookieHeader).toBeNull();
    expect(result.setCookies).toEqual([]);
    expect(fetchMock).not.toHaveBeenCalled();
  });

  test('direct-mode deployments never rotate (no refresh lineage there)', async () => {
    federatedEnv();
    vi.stubEnv('CUSTOMER_AUTH_MODE', 'direct');
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    const header = cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), uniqueRt('direct')],
    ]);
    const result = await ensureFreshSession(header);

    expect(result.status).toBe('fresh');
    expect(fetchMock).not.toHaveBeenCalled();
  });

  test('an expired access token without a refresh cookie keeps the old 401 posture', async () => {
    federatedEnv();
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);

    const result = await ensureFreshSession(cookies([[customerTokenCookieName(), fakeAt(-10)]]));

    expect(result.status).toBe('fresh');
    expect(result.setCookies).toEqual([]);
    expect(fetchMock).not.toHaveBeenCalled();
  });

  test('an access token inside the 60 s skew rotates; outside it does not', async () => {
    federatedEnv();

    const nearExpiry = cookies([
      [customerTokenCookieName(), fakeAt(30)],
      [customerRefreshCookieName(), uniqueRt('skew-in')],
    ]);
    recordFetch(tokenJson(rotatedBody()));
    const rotating = await ensureFreshSession(nearExpiry);
    expect(rotating.status).toBe('rotated');

    const comfortablyFresh = cookies([
      [customerTokenCookieName(), fakeAt(120)],
      [customerRefreshCookieName(), uniqueRt('skew-out')],
    ]);
    const fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
    const still = await ensureFreshSession(comfortablyFresh);
    expect(still.status).toBe('fresh');
    expect(fetchMock).not.toHaveBeenCalled();
  });

  test('an idle browser with ONLY the refresh cookie resurrects the session', async () => {
    federatedEnv();
    const rt = uniqueRt('resurrect');
    const calls = recordFetch(tokenJson(rotatedBody(`rt-next-${rt}`, 'at-fresh')));

    const result = await ensureFreshSession(cookies([[customerRefreshCookieName(), rt]]));

    expect(result.status).toBe('resurrected');
    expect(result.rewrittenCookieHeader).toContain(`${customerTokenCookieName()}=at-fresh`);
    expect(calls).toHaveLength(1);
  });
});

describe('rotation exchange', () => {
  test('the exchange is a client-authenticated refresh grant with a DPoP proof and a parkable key', async () => {
    federatedEnv();
    const rt = uniqueRt('exchange');
    const calls = recordFetch(tokenJson(rotatedBody()));

    await ensureFreshSession(cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), rt],
    ]));

    expect(calls[0].url).toBe(`${ISSUER}/oauth/token`);
    expect(calls[0].body.get('grant_type')).toBe('refresh_token');
    expect(calls[0].body.get('refresh_token')).toBe(rt);
    expect(calls[0].body.get('client_id')).toBe(CLIENT_ID);
    expect(calls[0].body.get('client_secret')).toBe('test-client-secret-1');
    // No scope parameter: league defaults to the stored scope, and the
    // issuer fingerprints client_id + refresh_token + scope — a stable
    // body keeps every retry fingerprint-identical to its original.
    expect(calls[0].body.get('scope')).toBeNull();

    const proof = JSON.parse(Buffer.from(calls[0].headers.DPoP.split('.')[1], 'base64url').toString('utf8'));
    expect(proof.htm).toBe('POST');
    expect(proof.htu).toBe(`${ISSUER}/oauth/token`);
    expect(proof.ath).toBeUndefined();

    expect(calls[0].headers['Idempotency-Key']).toMatch(/^[A-Za-z0-9._:-]{8,128}$/);
  });

  test('a successful rotation rewrites the request cookies and re-issues both halves', async () => {
    federatedEnv();
    const rt = uniqueRt('success-cookies');
    recordFetch(tokenJson(rotatedBody('rt-successor', 'at-successor')));

    const result = await ensureFreshSession(cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), rt],
      ['XSRF-TOKEN', 'foreign-value'],
    ]));

    expect(result.status).toBe('rotated');
    // The downstream request carries the fresh pair; the consumed
    // cookies are gone; foreign cookies survive.
    expect(result.rewrittenCookieHeader).toContain(`${customerTokenCookieName()}=at-successor`);
    expect(result.rewrittenCookieHeader).toContain(`${customerRefreshCookieName()}=${encodeURIComponent('rt-successor')}`);
    expect(result.rewrittenCookieHeader).toContain('XSRF-TOKEN=foreign-value');
    expect(result.rewrittenCookieHeader).not.toContain(rt);

    expect(result.setCookies).toHaveLength(3);
    expect(result.setCookies[0]).toContain(`${customerTokenCookieName()}=at-successor`);
    expect(result.setCookies[0]).toContain('Max-Age=900');
    expect(result.setCookies[1]).toContain(`${customerRefreshCookieName()}=${encodeURIComponent('rt-successor')}`);
    expect(result.setCookies[1]).toContain(`Max-Age=${REFRESH_COOKIE_MAX_AGE_SECONDS}`);
    expect(result.setCookies[2]).toContain(`${customerRotationPendingCookieName()}=;`);
  });

  test('invalid_grant (dead family — e.g. OP logout killed the lineage) clears both cookies', async () => {
    federatedEnv();
    recordFetch(tokenJson({ error: 'invalid_grant' }, 400));

    const result = await ensureFreshSession(cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), uniqueRt('dead')],
    ]));

    expect(result.status).toBe('unauthenticated');
    expect(result.rewrittenCookieHeader).toBeNull();
    expect(result.setCookies[0]).toContain(`${customerTokenCookieName()}=;`);
    expect(result.setCookies[0]).toContain('Max-Age=0');
    expect(result.setCookies[1]).toContain(`${customerRefreshCookieName()}=;`);
    expect(result.setCookies[1]).toContain('Max-Age=0');
  });

  test('invalid_dpop_proof (instance lost its key) clears the session the same way', async () => {
    federatedEnv();
    recordFetch(tokenJson({ error: 'invalid_dpop_proof' }, 400));

    const result = await ensureFreshSession(cookies([
      [customerRefreshCookieName(), uniqueRt('lost-key')],
    ]));

    expect(result.status).toBe('unauthenticated');
    expect(result.setCookies.some((c) => c.includes(`${customerRefreshCookieName()}=;`))).toBe(true);
  });

  test('a 200 without a full rotated pair fails closed (the presented token was consumed)', async () => {
    federatedEnv();
    recordFetch(tokenJson({ access_token: 'at-only', expires_in: 900 }));

    const result = await ensureFreshSession(cookies([
      [customerRefreshCookieName(), uniqueRt('half-response')],
    ]));

    expect(result.status).toBe('unauthenticated');
  });
});

describe('failure handling and idempotency parking', () => {
  test('a network failure retries ONCE with the SAME Idempotency-Key, then parks it', async () => {
    federatedEnv();
    const calls = recordFetch(
      () => {
        throw new TypeError('fetch failed');
      },
      () => {
        throw new TypeError('fetch failed');
      },
    );

    const result = await ensureFreshSession(cookies([
      [customerRefreshCookieName(), uniqueRt('park')],
    ]));

    // Two attempts, same key both times; a committed first attempt is
    // then recovered by the issuer instead of replayed as reuse.
    expect(calls).toHaveLength(2);
    expect(calls[0].headers['Idempotency-Key']).toBeTruthy();
    expect(calls[1].headers['Idempotency-Key']).toBe(calls[0].headers['Idempotency-Key']);

    expect(result.status).toBe('retry-later');
    expect(result.rewrittenCookieHeader).toBeNull();
    expect(result.setCookies).toHaveLength(1);
    expect(result.setCookies[0]).toContain(`${customerRotationPendingCookieName()}=${encodeURIComponent(calls[0].headers['Idempotency-Key'])}`);
    expect(result.setCookies[0]).toContain('Max-Age=60');
  });

  test('a thrown fetch (timeout) recovers via the same-key retry', async () => {
    federatedEnv();
    const calls: Array<{ key: string }> = [];
    let first = true;
    vi.stubGlobal(
      'fetch',
      vi.fn(async (_url: string, init?: RequestInit) => {
        const headers = (init?.headers ?? {}) as Record<string, string>;
        calls.push({ key: headers['Idempotency-Key'] });
        if (first) {
          first = false;
          throw new TypeError('fetch failed');
        }
        return tokenJson(rotatedBody());
      }),
    );

    const result = await ensureFreshSession(cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), uniqueRt('retry-ok')],
    ]));

    expect(calls).toHaveLength(2);
    expect(calls[1].key).toBe(calls[0].key);
    expect(result.status).toBe('rotated');
  });

  test('a parked Idempotency-Key from a previous lost response is REUSED, not replaced', async () => {
    federatedEnv();
    const parked = 'parked-key-0001';
    const calls = recordFetch(tokenJson(rotatedBody()));

    const header = cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), uniqueRt('parked-reuse')],
      [customerRotationPendingCookieName(), parked],
    ]);
    const result = await ensureFreshSession(header);

    expect(calls[0].headers['Idempotency-Key']).toBe(parked);
    expect(result.status).toBe('rotated');
  });

  test('a 5xx answer leaves the cookies untouched and parks the key for the next request', async () => {
    federatedEnv();
    const calls = recordFetch(tokenJson({ error: 'server_error' }, 500));

    const result = await ensureFreshSession(cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), uniqueRt('five-hundred')],
    ]));

    // 5xx is NOT retried in place (no double latency on real outages):
    // the parked key makes the next request the safe retry point.
    expect(calls).toHaveLength(1);
    expect(result.status).toBe('retry-later');
    expect(result.rewrittenCookieHeader).toBeNull();
    expect(result.setCookies[0]).toContain(customerRotationPendingCookieName());
    expect(result.setCookies[0]).not.toContain('Max-Age=0');
  });
});

describe('single-flight and replay (reuse-detection defence)', () => {
  test('concurrent requests with the same token share ONE rotation', async () => {
    federatedEnv();
    const rt = uniqueRt('single-flight');
    let resolveExchange: (r: Response) => void = () => {};
    const gate = new Promise<Response>((resolve) => {
      resolveExchange = resolve;
    });
    const calls: unknown[] = [];
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => {
        calls.push(null);
        return gate;
      }),
    );

    const header = cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), rt],
    ]);
    // ensureFreshSession reaches its first await only AFTER the fetch is
    // issued, so all three calls have joined (or started) the rotation
    // before any of them is awaited.
    const pending = [
      ensureFreshSession(header),
      ensureFreshSession(header),
      ensureFreshSession(header),
    ];
    resolveExchange(tokenJson(rotatedBody()));
    const results = await Promise.all(pending);

    // One issuer call for the whole burst: a second rotation of the
    // same token would be textbook reuse, and reuse detection kills the
    // user's entire lineage for this client (SEC-003 §3.2).
    expect(calls).toHaveLength(1);
    for (const result of results) expect(result.status).toBe('rotated');
  });

  test('a late request with the PRE-rotation cookies replays the finished rotation (no second push)', async () => {
    federatedEnv();
    const rt = uniqueRt('replay');
    const calls = recordFetch(tokenJson(rotatedBody('rt-replay-successor', 'at-replay-successor')));

    const header = cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), rt],
    ]);
    const first = await ensureFreshSession(header);
    const late = await ensureFreshSession(header);

    expect(first.status).toBe('rotated');
    expect(calls).toHaveLength(1);
    expect(late.status).toBe('rotated');
    expect(late.rewrittenCookieHeader).toContain('at-replay-successor');
    expect(late.setCookies).toEqual(first.setCookies);
  });

  test('a finished FATAL outcome is replayed too — a dead family is not re-presented', async () => {
    federatedEnv();
    const rt = uniqueRt('replay-fatal');
    const calls = recordFetch(tokenJson({ error: 'invalid_grant' }, 400));

    const header = cookies([[customerRefreshCookieName(), rt]]);
    const first = await ensureFreshSession(header);
    const late = await ensureFreshSession(header);

    expect(first.status).toBe('unauthenticated');
    expect(calls).toHaveLength(1);
    expect(late.status).toBe('unauthenticated');
  });
});

describe('mergeSessionCookies', () => {
  test('strips every session-family name in both casings and keeps foreign cookies', () => {
    federatedEnv();
    vi.stubEnv('SESSION_COOKIE_SECURE', '1');
    const merged = mergeSessionCookies(
      'nv_customer_token=old-at; __Host-nv_customer_refresh=old-rt; nv_customer_rot=k1; XSRF-TOKEN=x; other=y',
      [
        ['__Host-nv_customer_token', 'new at'],
        ['__Host-nv_customer_refresh', 'new rt'],
      ],
    );

    expect(merged).not.toContain('old-at');
    expect(merged).not.toContain('old-rt');
    expect(merged).not.toContain('k1');
    expect(merged).toContain('__Host-nv_customer_token=new%20at');
    expect(merged).toContain('__Host-nv_customer_refresh=new%20rt');
    expect(merged).toContain('XSRF-TOKEN=x');
    expect(merged).toContain('other=y');
  });
});

// The proxy adapter itself (apps/customer/proxy.ts, Next 16 — the
// Node-runtime successor of the deprecated edge middleware). Kept to
// surface-level assertions: the rotation logic above owns the contract;
// this pins that the adapter wires it into the request lifecycle.
describe('proxy adapter', () => {
  async function runProxy(header: string | null) {
    const { NextRequest } = await import('next/server');
    const { proxy } = await import('@apps/customer/proxy');
    const request = new NextRequest('http://customer.test/api/profile', {
      headers: header ? { cookie: header } : {},
    });
    return proxy(request);
  }

  test('an expired-token request is rotated through and answers without a login bounce', async () => {
    federatedEnv();
    recordFetch(tokenJson(rotatedBody('rt-proxy-successor', 'at-proxy-successor')));

    const res = await runProxy(cookies([
      [customerTokenCookieName(), fakeAt(-10)],
      [customerRefreshCookieName(), uniqueRt('proxy-rotate')],
    ]));

    expect(res.status).toBe(200);
    const setCookies = res.headers.getSetCookie();
    expect(setCookies.some((c) => c.includes(`${customerTokenCookieName()}=at-proxy-successor`))).toBe(true);
    expect(setCookies.some((c) => c.includes(customerRefreshCookieName()))).toBe(true);
  });

  test('an anonymous browser is still routed to /login', async () => {
    federatedEnv();
    const res = await runProxy(null);

    expect(res.status).toBe(307);
    expect(res.headers.get('location')).toContain('/login?redirect=%2Fapi%2Fprofile');
  });

  test('public paths pass through untouched', async () => {
    federatedEnv();
    const { NextRequest } = await import('next/server');
    const { proxy } = await import('@apps/customer/proxy');
    const res = await proxy(new NextRequest('http://customer.test/api/health'));

    expect(res.status).toBe(200);
    expect(res.headers.getSetCookie()).toEqual([]);
  });
});
