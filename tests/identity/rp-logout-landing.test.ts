import { afterAll, beforeAll, beforeEach, describe, expect, test } from 'vitest';

import { GET as customerStart } from '@apps/customer/app/api/auth/logout/start/route';
import { GET as customerLanding } from '@apps/customer/app/api/auth/logout/landing/route';
import { GET as partnerStart } from '@apps/partner/app/api/auth/logout/start/route';
import { GET as partnerLanding } from '@apps/partner/app/api/auth/logout/landing/route';
import { GET as dashboardStart } from '@apps/dashboard/app/api/auth/logout/start/route';
import { GET as dashboardLanding } from '@apps/dashboard/app/api/auth/logout/landing/route';
import { GET as webStart } from '@apps/web/app/api/auth/logout/start/route';
import { GET as webLanding } from '@apps/web/app/api/auth/logout/landing/route';

// RP-initiated logout loop (OIDC RP-Initiated Logout 1.0, A-SEC-004-6):
// /logout/start is the browser-facing hand-off to the OP end-session
// (client_id resolves the RP — no token material rides the URL), and
// /logout/landing is the registered post_logout_redirect_uri target:
// it correlates the returned state against the start-route cookie,
// clears every local session cookie idempotently, and acknowledges
// only a correlated return. Four-app parallel-copy battery, same
// harness style as backchannel-logout.test.ts.

const ISSUER = 'http://op.test';

interface LogoutHarness {
  name: string;
  modeEnv: string;
  issuerEnv: string;
  clientIdEnv: string;
  clientSecretEnv: string;
  /** Dev-context session cookie names this app clears (dashboard clears both). */
  sessionCookies: string[];
  /** Dev-context logout-state correlation cookie name. */
  stateCookie: string;
  start(req: Request): Promise<Response>;
  landing(req: Request): Promise<Response>;
}

const APPS: LogoutHarness[] = [
  {
    name: 'customer',
    modeEnv: 'CUSTOMER_AUTH_MODE',
    issuerEnv: 'CUSTOMER_AUTH_ISSUER',
    clientIdEnv: 'CUSTOMER_AUTH_CLIENT_ID',
    clientSecretEnv: 'CUSTOMER_AUTH_CLIENT_SECRET',
    sessionCookies: ['nv_customer_token'],
    stateCookie: 'nv-logout-state',
    start: customerStart,
    landing: customerLanding,
  },
  {
    name: 'partner',
    modeEnv: 'PARTNER_AUTH_MODE',
    issuerEnv: 'PARTNER_AUTH_ISSUER',
    clientIdEnv: 'PARTNER_AUTH_CLIENT_ID',
    clientSecretEnv: 'PARTNER_AUTH_CLIENT_SECRET',
    sessionCookies: ['__session'],
    stateCookie: 'nv-partner-logout-state',
    start: partnerStart,
    landing: partnerLanding,
  },
  {
    name: 'dashboard',
    modeEnv: 'DASHBOARD_AUTH_MODE',
    issuerEnv: 'DASHBOARD_AUTH_ISSUER',
    clientIdEnv: 'DASHBOARD_AUTH_CLIENT_ID',
    clientSecretEnv: 'DASHBOARD_AUTH_CLIENT_SECRET',
    sessionCookies: ['nv_dashboard_token', '__Host-nv_dashboard_token'],
    stateCookie: 'nv-dashboard-logout-state',
    start: dashboardStart,
    landing: dashboardLanding,
  },
  {
    // web reuses the customer env names (shared contract).
    name: 'web',
    modeEnv: 'CUSTOMER_AUTH_MODE',
    issuerEnv: 'CUSTOMER_AUTH_ISSUER',
    clientIdEnv: 'CUSTOMER_AUTH_CLIENT_ID',
    clientSecretEnv: 'CUSTOMER_AUTH_CLIENT_SECRET',
    sessionCookies: ['navdhya-session'],
    stateCookie: 'nv-web-logout-state',
    start: webStart,
    landing: webLanding,
  },
];

const MANAGED_ENV = new Set<string>(
  [...APPS.flatMap((a) => [a.modeEnv, a.issuerEnv, a.clientIdEnv, a.clientSecretEnv]), 'SESSION_COOKIE_SECURE'],
);
const originalEnv = new Map<string, string | undefined>();

const base = (h: LogoutHarness): string => `http://${h.name}.bff.test`;
const clientIdOf = (h: LogoutHarness): string => `client-${h.name}`;

function setFederated(h: LogoutHarness): void {
  process.env[h.modeEnv] = 'federated';
  process.env[h.issuerEnv] = ISSUER;
  process.env[h.clientIdEnv] = clientIdOf(h);
  process.env[h.clientSecretEnv] = 'test-client-secret-1';
}

function setDirect(h: LogoutHarness): void {
  process.env[h.modeEnv] = 'direct';
}

function startRequest(h: LogoutHarness): Request {
  return new Request(`${base(h)}/api/auth/logout/start`);
}

function landingRequest(
  h: LogoutHarness,
  opts: { query?: string; cookies?: string[] } = {},
): Request {
  const url = `${base(h)}/api/auth/logout/landing${opts.query ?? ''}`;
  return new Request(url, {
    headers: opts.cookies && opts.cookies.length ? { cookie: opts.cookies.join('; ') } : {},
  });
}

const setCookieLines = (res: Response): string[] => res.headers.getSetCookie();

beforeAll(() => {
  for (const key of MANAGED_ENV) originalEnv.set(key, process.env[key]);
  // Pin the dev cookie-name context (no __Host- prefix) for every app.
  process.env.SESSION_COOKIE_SECURE = '0';
});

afterAll(() => {
  for (const [key, value] of originalEnv) {
    if (value === undefined) delete process.env[key];
    else process.env[key] = value;
  }
});

describe.each(APPS)('$name RP-initiated logout (start + landing)', (h) => {
  beforeEach(() => {
    setFederated(h);
  });

  describe('GET /api/auth/logout/start', () => {
    test('redirects the browser to the OP end-session with a correlation state', async () => {
      const res = await h.start(startRequest(h));

      expect(res.status).toBe(307);
      expect(res.headers.get('cache-control')).toBe('no-store');

      const loc = new URL(res.headers.get('Location')!);
      expect(`${loc.origin}${loc.pathname}`).toBe(`${ISSUER}/oauth/logout`);
      expect(loc.searchParams.get('client_id')).toBe(clientIdOf(h));
      expect(loc.searchParams.get('post_logout_redirect_uri')).toBe(
        `${base(h)}/api/auth/logout/landing`,
      );

      const state = loc.searchParams.get('state');
      expect(state).toBeTruthy();
      expect(state!.length).toBeGreaterThanOrEqual(32);
    });

    test('parks the same state in a short-lived HttpOnly cookie', async () => {
      const res = await h.start(startRequest(h));
      const state = new URL(res.headers.get('Location')!).searchParams.get('state')!;

      const line = setCookieLines(res).find((c) => c.startsWith(`${h.stateCookie}=`));
      expect(line).toBeDefined();
      expect(line).toContain(`=${state};`);
      expect(line).toContain('Path=/');
      expect(line).toContain('HttpOnly');
      expect(line).toContain('SameSite=Lax');
      expect(line).toContain('Max-Age=600');
    });

    test('no token material ever rides the redirect URL', async () => {
      const res = await h.start(startRequest(h));
      const loc = res.headers.get('Location')!;

      expect(loc).not.toMatch(/access_token|refresh_token|id_token|client_secret/);
    });

    test('404 outside federation (direct-mode logout is the POST route)', async () => {
      setDirect(h);
      const res = await h.start(startRequest(h));

      expect(res.status).toBe(404);
      expect(res.headers.get('Location')).toBeNull();
      expect(setCookieLines(res)).toHaveLength(0);
    });
  });

  describe('GET /api/auth/logout/landing', () => {
    const correlatedCookies = (h: LogoutHarness, state: string): string[] => [
      ...h.sessionCookies.map((name) => `${name}=dead-token`),
      `${h.stateCookie}=${state}`,
    ];

    test('correlated return clears every cookie and acknowledges the sign-out', async () => {
      const state = 'st-'.concat('a'.repeat(40));
      const res = await h.landing(
        landingRequest(h, { query: `?state=${encodeURIComponent(state)}`, cookies: correlatedCookies(h, state) }),
      );

      expect(res.status).toBe(200);
      expect(res.headers.get('content-type')).toContain('text/html');
      expect(res.headers.get('cache-control')).toBe('no-store');
      const body = await res.text();
      expect(body).toContain('You are signed out');

      const lines = setCookieLines(res);
      for (const name of h.sessionCookies) {
        expect(
          lines.some((c) => c.startsWith(`${name}=;`) && c.includes('Max-Age=0')),
          `session cookie ${name} cleared`,
        ).toBe(true);
      }
      expect(
        lines.some((c) => c.startsWith(`${h.stateCookie}=;`) && c.includes('Max-Age=0')),
        'state cookie cleared',
      ).toBe(true);
    });

    test('mismatched state cleans up but never acknowledges (redirect home)', async () => {
      const res = await h.landing(
        landingRequest(h, { query: '?state=some-other-state', cookies: correlatedCookies(h, 'parked-state') }),
      );

      expect(res.status).toBe(307);
      expect(res.headers.get('Location')).toBe(`${base(h)}/`);
      expect(res.headers.get('cache-control')).toBe('no-store');
      const lines = setCookieLines(res);
      expect(lines.some((c) => c.startsWith(`${h.sessionCookies[0]}=;`) && c.includes('Max-Age=0'))).toBe(true);
      expect(lines.some((c) => c.startsWith(`${h.stateCookie}=;`) && c.includes('Max-Age=0'))).toBe(true);
    });

    test('absent state query cleans up but never acknowledges', async () => {
      const res = await h.landing(landingRequest(h, { cookies: correlatedCookies(h, 'parked-state') }));

      expect(res.status).toBe(307);
      expect(res.headers.get('Location')).toBe(`${base(h)}/`);
      expect(setCookieLines(res).some((c) => c.startsWith(`${h.sessionCookies[0]}=;`))).toBe(true);
    });

    test('missing state cookie (cold visit) still cleans up, never acknowledges', async () => {
      const res = await h.landing(landingRequest(h, { query: '?state=present-but-unknown' }));

      expect(res.status).toBe(307);
      expect(res.headers.get('Location')).toBe(`${base(h)}/`);
      const lines = setCookieLines(res);
      expect(lines.some((c) => c.startsWith(`${h.sessionCookies[0]}=;`) && c.includes('Max-Age=0'))).toBe(true);
      expect(lines.some((c) => c.startsWith(`${h.stateCookie}=;`))).toBe(true);
    });

    test('404 outside federation', async () => {
      setDirect(h);
      const res = await h.landing(landingRequest(h, { query: '?state=x' }));

      expect(res.status).toBe(404);
      expect(setCookieLines(res)).toHaveLength(0);
    });
  });
});
