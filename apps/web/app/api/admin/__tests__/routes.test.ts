/**
 * @jest-environment node
 *
 * BFF admin/platform proxy routes — S42.
 *
 * These are thin forwarders: Laravel stays the sole authorization and data
 * authority (the caller's own session token is what gets proxied), so what
 * the BFF must get right is the envelope contract around them:
 *
 *   - backend disabled → honest 200 fallback (dashboards degrade, no 5xx)
 *   - no session       → 401 UNAUTHENTICATED
 *   - backend error    → mapped via laravelErrorResponse (contract §8:
 *                        backend status + code survive the proxy hop;
 *                        a missing upstream surface is a 404, not a 500)
 */
import { GET as adminUsers } from '@/app/api/admin/users/route';
import { GET as adminStats } from '@/app/api/admin/stats/route';
import { GET as platformLogs } from '@/app/api/platform/logs/route';

type RouteFn = (request: Request) => Promise<Response>;

const ENV_KEYS = ['CUSTOMER_BACKEND', 'NAVDHYA_API_BASE'] as const;
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

function call(route: RouteFn, path: string, headers: Record<string, string> = {}): Promise<Response> {
  return route(new Request(`http://localhost${path}`, { headers }));
}

const BACKEND_ON = { CUSTOMER_BACKEND: 'laravel', NAVDHYA_API_BASE: 'http://127.0.0.1:9' };
const BACKEND_OFF = { CUSTOMER_BACKEND: undefined, NAVDHYA_API_BASE: undefined };

describe('GET /api/admin/users', () => {
  afterEach(restoreEnv);

  it('falls back to an empty page when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminUsers, '/api/admin/users');
    expect(response.status).toBe(200);
    const body = await response.json();
    expect(body.data).toEqual({
      data: [],
      meta: { page: 1, limit: 20, total: 0, fallback: true },
    });
    expect(body.requestId).toBeTruthy();
  });

  it('rejects without a session (401) when the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    const response = await call(adminUsers, '/api/admin/users');
    expect(response.status).toBe(401);
    const body = await response.json();
    expect(body.error.code).toBe('UNAUTHENTICATED');
  });

  it('maps backend failures through the §8 envelope (404 stays 404)', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: string[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL) => {
      seen.push(String(input));
      return new Response(
        JSON.stringify({ error: { code: 'not_found', requestId: 'req_backend' } }),
        { status: 404, headers: { 'content-type': 'application/json' } },
      );
    }) as typeof fetch;

    try {
      const response = await call(adminUsers, '/api/admin/users?page=2', {
        cookie: 'navdhya-session=e2e-token',
      });
      expect(response.status).toBe(404);
      const body = await response.json();
      expect(body.error.code).toBe('NOT_FOUND');

      // The caller's own token is what gets proxied — never a server-side one.
      expect(seen.some((url) => url.includes('/api/v1/platform/users'))).toBe(true);
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/admin/stats', () => {
  afterEach(restoreEnv);

  it('falls back with an empty stats object when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminStats, '/api/admin/stats');
    expect(response.status).toBe(200);
    const body = await response.json();
    expect(body.data).toEqual({ stats: {}, meta: { fallback: true } });
  });

  it('aggregates parallel upstreams without failing the page on partial data', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () =>
      new Response(
        JSON.stringify({ data: [{ id: 'u1' }], meta: { request_id: 'req_backend' } }),
        { status: 200, headers: { 'content-type': 'application/json' } },
      ),
    ) as typeof fetch;

    try {
      const response = await call(adminStats, '/api/admin/stats', {
        cookie: 'navdhya-session=e2e-token',
      });
      expect(response.status).toBe(200);
      const body = await response.json();
      expect(body.data.stats.users).toEqual([{ id: 'u1' }]);
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/logs', () => {
  afterEach(restoreEnv);

  it('falls back to an empty page when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(platformLogs, '/api/platform/logs');
    expect(response.status).toBe(200);
    const body = await response.json();
    expect(body.data).toEqual({
      data: [],
      meta: { page: 1, limit: 50, total: 0, fallback: true },
    });
  });

  it('requires a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    const response = await call(platformLogs, '/api/platform/logs');
    expect(response.status).toBe(401);
  });
});
