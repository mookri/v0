import { afterAll, beforeAll, beforeEach, afterEach, describe, expect, test, vi } from 'vitest';

import { GET as customerAuthorize } from '@apps/customer/app/api/auth/authorize/route';
import { GET as partnerAuthorize } from '@apps/partner/app/api/auth/authorize/route';
import { GET as dashboardAuthorize } from '@apps/dashboard/app/api/auth/authorize/route';
import { GET as webAuthorize } from '@apps/web/app/api/auth/authorize/route';

// RFC 9126 federation push (A-SEC-004-8): the four BFFs can operate
// under the issuer's per-client PAR mandate (the N2 cohort — the N=4
// drill proved the OP denies mandate clients that present front-
// channel parameters, and surfaced that no BFF could satisfy it).
// With {PREFIX}_AUTH_PAR=required the authorize route pushes the full
// parameter set over the authenticated back channel (client_secret_post)
// and the browser presents only the returned request_uri; a failed
// push FAILS CLOSED — never a front-channel fallback. par=off (default)
// is byte-identical to the unpushed flow. Four-app parallel-copy
// battery, same harness style as rp-logout-landing.test.ts.

const ISSUER = 'http://op.test';

const REQUEST_URI = 'urn:navdhya:par:test-request-uri';

interface ParHarness {
  name: string;
  modeEnv: string;
  issuerEnv: string;
  clientIdEnv: string;
  clientSecretEnv: string;
  parEnv: string;
  /** Dev-context flow cookie the login round-trip parks state in. */
  flowCookie: string;
  /** Direct-mode browser handoff target (partner has no /login). */
  directTarget: string;
  authorize(req: Request): Promise<Response>;
}

const APPS: ParHarness[] = [
  {
    name: 'customer',
    modeEnv: 'CUSTOMER_AUTH_MODE',
    issuerEnv: 'CUSTOMER_AUTH_ISSUER',
    clientIdEnv: 'CUSTOMER_AUTH_CLIENT_ID',
    clientSecretEnv: 'CUSTOMER_AUTH_CLIENT_SECRET',
    parEnv: 'CUSTOMER_AUTH_PAR',
    flowCookie: 'nv-auth-flow',
    directTarget: '/login',
    authorize: customerAuthorize,
  },
  {
    name: 'partner',
    modeEnv: 'PARTNER_AUTH_MODE',
    issuerEnv: 'PARTNER_AUTH_ISSUER',
    clientIdEnv: 'PARTNER_AUTH_CLIENT_ID',
    clientSecretEnv: 'PARTNER_AUTH_CLIENT_SECRET',
    parEnv: 'PARTNER_AUTH_PAR',
    flowCookie: 'nv-partner-auth-flow',
    directTarget: '/',
    authorize: partnerAuthorize,
  },
  {
    name: 'dashboard',
    modeEnv: 'DASHBOARD_AUTH_MODE',
    issuerEnv: 'DASHBOARD_AUTH_ISSUER',
    clientIdEnv: 'DASHBOARD_AUTH_CLIENT_ID',
    clientSecretEnv: 'DASHBOARD_AUTH_CLIENT_SECRET',
    parEnv: 'DASHBOARD_AUTH_PAR',
    flowCookie: 'nv-dashboard-auth-flow',
    directTarget: '/login',
    authorize: dashboardAuthorize,
  },
  {
    // web reuses the customer env names (shared federation contract).
    name: 'web',
    modeEnv: 'CUSTOMER_AUTH_MODE',
    issuerEnv: 'CUSTOMER_AUTH_ISSUER',
    clientIdEnv: 'CUSTOMER_AUTH_CLIENT_ID',
    clientSecretEnv: 'CUSTOMER_AUTH_CLIENT_SECRET',
    parEnv: 'CUSTOMER_AUTH_PAR',
    flowCookie: 'navdhya-auth-flow',
    directTarget: '/login',
    authorize: webAuthorize,
  },
];

const MANAGED_ENV = new Set<string>(
  [
    ...APPS.flatMap((a) => [a.modeEnv, a.issuerEnv, a.clientIdEnv, a.clientSecretEnv, a.parEnv]),
    'SESSION_COOKIE_SECURE',
  ],
);
const originalEnv = new Map<string, string | undefined>();

const base = (h: ParHarness): string => `http://${h.name}.bff.test`;
const clientIdOf = (h: ParHarness): string => `client-${h.name}`;

/** What the stubbed OP answers the next PAR push. */
let parPush: { status: number; body: unknown };
/** Every push the route made, in order (init in beforeAll). */
let parFetch!: ReturnType<typeof vi.fn>;

function setFederated(h: ParHarness): void {
  process.env[h.modeEnv] = 'federated';
  process.env[h.issuerEnv] = ISSUER;
  process.env[h.clientIdEnv] = clientIdOf(h);
  process.env[h.clientSecretEnv] = 'test-client-secret-1';
}

function setDirect(h: ParHarness): void {
  process.env[h.modeEnv] = 'direct';
}

function setPar(h: ParHarness, mode: 'off' | 'required' | null): void {
  if (mode === null) delete process.env[h.parEnv];
  else process.env[h.parEnv] = mode;
}

function authorizeRequest(h: ParHarness): Request {
  return new Request(`${base(h)}/api/auth/authorize?redirect=/dashboard`);
}

const setCookieLines = (res: Response): string[] => res.headers.getSetCookie();

beforeAll(() => {
  for (const key of MANAGED_ENV) originalEnv.set(key, process.env[key]);
  // Pin the dev cookie-name context (no __Host- prefix) for every app.
  process.env.SESSION_COOKIE_SECURE = '0';

  parFetch = vi.fn(async (input: unknown, init?: { method?: string }) => {
    if (String(input) === `${ISSUER}/api/oauth/par` && init?.method === 'POST') {
      return { ok: parPush.status < 400, status: parPush.status, json: async () => parPush.body };
    }
    return { ok: false, status: 404, json: async () => ({}) };
  });
  vi.stubGlobal('fetch', parFetch);
});

afterAll(() => {
  vi.unstubAllGlobals();
  for (const [key, value] of originalEnv) {
    if (value === undefined) delete process.env[key];
    else process.env[key] = value;
  }
});

beforeEach(() => {
  parPush = { status: 201, body: { request_uri: REQUEST_URI, expires_in: 120 } };
  parFetch.mockClear();
});

afterEach(() => {
  for (const h of APPS) setPar(h, null);
});

describe.each(APPS)('$name PAR federation push', (h) => {
  beforeEach(() => {
    setFederated(h);
  });

  test('par=off (default) is the unpushed front-channel flow, fetch untouched', async () => {
    setPar(h, 'off');
    const res = await h.authorize(authorizeRequest(h));

    expect(res.status).toBe(302);
    const loc = new URL(res.headers.get('Location')!);
    expect(`${loc.origin}${loc.pathname}`).toBe(`${ISSUER}/oauth/authorize`);
    expect(loc.searchParams.get('response_type')).toBe('code');
    expect(loc.searchParams.get('client_id')).toBe(clientIdOf(h));
    expect(loc.searchParams.get('redirect_uri')).toBe(`${base(h)}/api/auth/callback`);
    expect(loc.searchParams.get('scope')).toBe('openid');
    expect(loc.searchParams.get('code_challenge_method')).toBe('S256');
    expect(loc.searchParams.get('state')).toBeTruthy();
    expect(loc.searchParams.get('nonce')).toBeTruthy();
    expect(loc.searchParams.get('request_uri')).toBeNull();
    expect(parFetch).not.toHaveBeenCalled();
  });

  test('unset par env is also the unpushed flow', async () => {
    setPar(h, null);
    const res = await h.authorize(authorizeRequest(h));

    expect(res.status).toBe(302);
    expect(new URL(res.headers.get('Location')!).searchParams.get('request_uri')).toBeNull();
    expect(parFetch).not.toHaveBeenCalled();
  });

  test('par=required pushes the full set over client_secret_post', async () => {
    setPar(h, 'required');
    const res = await h.authorize(authorizeRequest(h));

    expect(res.status).toBe(302);
    expect(parFetch).toHaveBeenCalledTimes(1);
    expect(String(parFetch.mock.calls[0][0])).toBe(`${ISSUER}/api/oauth/par`);
    expect((parFetch.mock.calls[0][1] as { method?: string }).method).toBe('POST');

    const body = new URLSearchParams(String((parFetch.mock.calls[0][1] as { body?: string }).body));
    expect(body.get('client_id')).toBe(clientIdOf(h));
    expect(body.get('client_secret')).toBe('test-client-secret-1');
    expect(body.get('response_type')).toBe('code');
    expect(body.get('redirect_uri')).toBe(`${base(h)}/api/auth/callback`);
    expect(body.get('scope')).toBe('openid');
    expect(body.get('code_challenge_method')).toBe('S256');
    expect(body.get('state')).toMatch(/^[A-Za-z0-9_-]{32,}$/);
    expect(body.get('nonce')).toMatch(/^[A-Za-z0-9_-]{32,}$/);
    expect(body.get('code_challenge')).toMatch(/^[A-Za-z0-9_-]{43}$/);
  });

  test('the browser presents ONLY client_id + request_uri', async () => {
    setPar(h, 'required');
    const res = await h.authorize(authorizeRequest(h));

    const loc = new URL(res.headers.get('Location')!);
    expect(`${loc.origin}${loc.pathname}`).toBe(`${ISSUER}/oauth/authorize`);
    expect(loc.searchParams.get('client_id')).toBe(clientIdOf(h));
    expect(loc.searchParams.get('request_uri')).toBe(REQUEST_URI);
    expect([...loc.searchParams.keys()].sort()).toEqual(['client_id', 'request_uri']);
    // No parameter leakage, and never the secret.
    expect(res.headers.get('Location')).not.toMatch(/access_token|refresh_token|client_secret|code_challenge|nonce|scope|redirect_uri/);
  });

  test('the flow cookie still parks the pushed state (same bytes the push authenticated)', async () => {
    setPar(h, 'required');
    const res = await h.authorize(authorizeRequest(h));

    const pushedState = new URLSearchParams(
      String((parFetch.mock.calls[0][1] as { body?: string }).body),
    ).get('state')!;

    const line = setCookieLines(res).find((c) => c.startsWith(`${h.flowCookie}=`));
    expect(line).toBeDefined();
    expect(line).toContain('HttpOnly');
    expect(line).toContain('SameSite=Lax');

    const value = decodeURIComponent(line!.slice(h.flowCookie.length + 1).split(';')[0]);
    // state.verifier.nonce.redirect — the state is the exact value the
    // issuer stored at the push, so the callback can correlate it.
    expect(value.split('.')[0]).toBe(pushedState);
  });

  test('a failed push fails CLOSED: the handler throws, never a front-channel redirect', async () => {
    setPar(h, 'required');
    parPush = { status: 401, body: { error: 'invalid_client' } };

    await expect(h.authorize(authorizeRequest(h))).rejects.toThrow(/status 401/);
  });

  test('a 201 without a request_uri is a failed push (throws)', async () => {
    setPar(h, 'required');
    parPush = { status: 201, body: { expires_in: 120 } };

    await expect(h.authorize(authorizeRequest(h))).rejects.toThrow(/no request_uri/);
  });

  test('direct mode ignores par entirely (mode gate first, no fetch)', async () => {
    setDirect(h);
    setPar(h, 'required');
    const res = await h.authorize(authorizeRequest(h));

    expect(res.status).toBe(302);
    expect(res.headers.get('Location')).toBe(`${h.directTarget}?redirect=%2Fdashboard`);
    expect(parFetch).not.toHaveBeenCalled();
  });

  test('an unknown par value fails closed at the gate', async () => {
    setPar(h, 'sometimes');
    parPush = { status: 201, body: { request_uri: REQUEST_URI, expires_in: 120 } };

    await expect(h.authorize(authorizeRequest(h))).rejects.toThrow(
      new RegExp(`${h.parEnv} must be 'off' or 'required'`),
    );
    expect(parFetch).not.toHaveBeenCalled();
  });
});
