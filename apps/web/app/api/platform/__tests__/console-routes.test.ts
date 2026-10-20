/**
 * @jest-environment node
 *
 * W1a console BFF routes — `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`.
 *
 * These are thin forwarders: Laravel stays the sole authorization and data
 * authority (the caller's own session token is what gets proxied), so what the
 * BFF must get right is the contract around them:
 *
 *   - backend disabled  → honest 200 `fallback` (dashboards degrade, no 5xx)
 *   - no session        → 401 UNAUTHENTICATED
 *   - no console tenant → 503 DEPENDENCY_UNAVAILABLE naming CUSTOMER_TENANT_ID
 *   - a real tenant     → forwarded as BOTH `tenant_id` (query) and
 *                         `X-Tenant-Id` (header). `tenant_id: 'all'` never
 *                         names a row and is the bug these routes used to ship.
 *   - backend error     → mapped via laravelErrorResponse (backend status +
 *                         code survive the hop)
 */
import { GET as rolesRoute } from '@/app/api/platform/roles/route';
import { GET as usersRoute } from '@/app/api/platform/users/route';
import { GET as apiRoute } from '@/app/api/platform/api/route';
import { GET as logsRoute } from '@/app/api/platform/logs/route';
import { GET as statsRoute } from '@/app/api/platform/stats/route';
import { GET as settingsRoute } from '@/app/api/platform/settings/route';
import { GET as billingRoute } from '@/app/api/platform/billing/route';
import { GET as securityRoute } from '@/app/api/platform/security/route';
import { GET as systemRoute } from '@/app/api/platform/system/route';

type RouteFn = (request: Request) => Promise<Response>;

const ENV_KEYS = ['CUSTOMER_BACKEND', 'NAVDHYA_API_BASE', 'CUSTOMER_TENANT_ID'] as const;
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

const SESSION_COOKIE = 'navdhya-session=test-session-token';

function call(route: RouteFn, path: string, headers: Record<string, string> = {}): Promise<Response> {
  return route(new Request(`http://localhost${path}`, { headers }));
}

function ok200(data: unknown, meta: unknown = { request_id: 'req_backend' }) {
  return new Response(JSON.stringify({ data, meta }), {
    status: 200,
    headers: { 'content-type': 'application/json' },
  });
}

function fail(status: number, code: string) {
  return new Response(JSON.stringify({ error: { code, requestId: 'req_backend' } }), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

const BACKEND_OFF = { CUSTOMER_BACKEND: undefined, NAVDHYA_API_BASE: undefined, CUSTOMER_TENANT_ID: undefined };
const BACKEND_ON = { CUSTOMER_BACKEND: 'laravel', NAVDHYA_API_BASE: 'http://127.0.0.1:9', CUSTOMER_TENANT_ID: 't_1' };
const BACKEND_NO_TENANT = { CUSTOMER_BACKEND: 'laravel', NAVDHYA_API_BASE: 'http://127.0.0.1:9', CUSTOMER_TENANT_ID: undefined };

afterEach(restoreEnv);

describe('GET /api/platform/roles', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(rolesRoute, '/api/platform/roles');
    expect(response.status).toBe(200);
    const body = await response.json();
    expect(body.data).toEqual({ data: [], meta: { fallback: true } });
    expect(body.requestId).toBeTruthy();
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    const response = await call(rolesRoute, '/api/platform/roles');
    expect(response.status).toBe(401);
    expect((await response.json()).error.code).toBe('UNAUTHENTICATED');
  });

  it('answers 503 naming CUSTOMER_TENANT_ID when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(rolesRoute, '/api/platform/roles', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    const body = await response.json();
    expect(body.error.code).toBe('DEPENDENCY_UNAVAILABLE');
    expect(body.error.message).toContain('CUSTOMER_TENANT_ID');
  });

  it('forwards a real tenant as query AND header, never `all`', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200([{ id: 'r1', code: 'admin', name: 'Admin', state: 'active' }]);
    }) as typeof fetch;

    try {
      const response = await call(rolesRoute, '/api/platform/roles', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen).toHaveLength(1);
      expect(seen[0].url).toContain('/api/v1/platform/roles');
      expect(seen[0].url).toContain('tenant_id=t_1');
      expect(seen[0].url).not.toContain('tenant_id=all');
      expect(seen[0].headers['X-Tenant-Id']).toBe('t_1');
      expect(seen[0].headers.Authorization).toBe('Bearer test-session-token');
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('keeps the backend status and code through the proxy (404 stays 404)', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(404, 'not_found')) as typeof fetch;

    try {
      const response = await call(rolesRoute, '/api/platform/roles', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(404);
      expect((await response.json()).error.code).toBe('NOT_FOUND');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/users', () => {
  it('falls back with an empty page when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(usersRoute, '/api/platform/users');
    expect(response.status).toBe(200);
    const body = await response.json();
    expect(body.data).toEqual({
      data: [],
      meta: { page: 1, limit: 50, total: 0, fallback: true },
    });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(usersRoute, '/api/platform/users')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(usersRoute, '/api/platform/users', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('forwards the console tenant and reports the real row count', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200([
        { id: 'm1', state: 'active', user: { id: 'u1', name: 'Asha', email: 'a@x.dev', status: 'active' } },
        { id: 'm2', state: 'active', user: { id: 'u2', name: 'Bik', email: 'b@x.dev', status: 'suspended' } },
      ]);
    }) as typeof fetch;

    try {
      const response = await call(usersRoute, '/api/platform/users?limit=50', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('tenant_id=t_1');
      expect(seen[0].headers['X-Tenant-Id']).toBe('t_1');

      const body = await response.json();
      expect(body.data.data).toHaveLength(2);
      expect(body.data.meta.total).toBe(2);
      expect(body.data.meta.limit).toBe(50);
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/api', () => {
  it('falls back to empty collections when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(apiRoute, '/api/platform/api');
    expect(response.status).toBe(200);
    expect((await response.json()).data).toEqual({
      api_keys: [],
      oauth_clients: [],
      meta: { fallback: true },
    });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(apiRoute, '/api/platform/api')).status).toBe(401);
  });

  it('reads the two endpoints that exist instead of the missing /platform/api', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: string[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL) => {
      const url = String(input);
      seen.push(url);
      if (url.includes('/platform/api-keys')) {
        return ok200([{ id: 'k1', name: 'warehouse', prefix: 'ndk_live_ab', rate_tier: 'standard', revoked_at: null }]);
      }
      return ok200([{ id: 'c1', client_id: 'web-app', name: 'Web app', revoked: false, require_pkce: true }]);
    }) as typeof fetch;

    try {
      const response = await call(apiRoute, '/api/platform/api', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);

      expect(seen.some((url) => url.includes('/api/v1/platform/api-keys'))).toBe(true);
      expect(seen.some((url) => url.includes('/api/v1/platform/oauth/clients'))).toBe(true);
      // The route this panel used to call never existed and must stay gone.
      expect(seen.some((url) => /\/platform\/api(\?|$)/.test(url))).toBe(false);

      const body = await response.json();
      expect(body.data.api_keys).toHaveLength(1);
      expect(body.data.oauth_clients).toHaveLength(1);
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('surfaces one denied leg instead of reporting it as "no clients exist"', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async (input: RequestInfo | URL) => {
      const url = String(input);
      // Machine keys resolve, the federation leg is refused.
      return url.includes('/platform/api-keys')
        ? ok200([{ id: 'k1', name: 'warehouse', prefix: 'ndk_live_ab', revoked_at: null }])
        : fail(403, 'permission_missing');
    }) as typeof fetch;

    try {
      const response = await call(apiRoute, '/api/platform/api', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/logs', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(logsRoute, '/api/platform/logs');
    expect(response.status).toBe(200);
    expect((await response.json()).data).toEqual({ data: [], meta: { fallback: true, limit: 50 } });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(logsRoute, '/api/platform/logs')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(logsRoute, '/api/platform/logs', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('re-points from the never-existing /platform/logs to /platform/audit/logs', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: string[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL) => {
      const url = String(input);
      seen.push(url);
      return ok200([{ id: 'l1', action: 'authz.denied', occurred_at: '2026-10-01T10:00:00Z' }]);
    }) as typeof fetch;

    try {
      const response = await call(logsRoute, '/api/platform/logs?limit=25', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen.some((url) => url.includes('/api/v1/platform/audit/logs'))).toBe(true);
      expect(seen.some((url) => /\/platform\/logs(\?|$)/.test(url))).toBe(false);
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(403, 'authorization_denied')) as typeof fetch;

    try {
      const response = await call(logsRoute, '/api/platform/logs', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/stats', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(statsRoute, '/api/platform/stats');
    expect(response.status).toBe(200);
    expect((await response.json()).data.meta.fallback).toBe(true);
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(statsRoute, '/api/platform/stats')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(statsRoute, '/api/platform/stats', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the aggregate endpoint with a real tenant', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200({ users: { total: 5, active: 3 }, roles: { total: 2 }, audit: { total: 10, denied_today: 1 }, tenant: { code: 'acme' } });
    }) as typeof fetch;

    try {
      const response = await call(statsRoute, '/api/platform/stats', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/platform/stats');
      expect(seen[0].url).toContain('tenant_id=t_1');
      expect(seen[0].headers['X-Tenant-Id']).toBe('t_1');
      const body = await response.json();
      expect(body.data.data.users.total).toBe(5);
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(403, 'authorization_denied')) as typeof fetch;

    try {
      const response = await call(statsRoute, '/api/platform/stats', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/settings', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(settingsRoute, '/api/platform/settings');
    expect(response.status).toBe(200);
    expect((await response.json()).data.meta.fallback).toBe(true);
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(settingsRoute, '/api/platform/settings')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(settingsRoute, '/api/platform/settings', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the settings endpoint with a real tenant', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200({ code: 'acme', name: 'Acme', timezone: 'Asia/Kolkata', locale: 'en-IN', plan_code: 'pro', base_currency: 'INR', state: 'active', status: 'active' });
    }) as typeof fetch;

    try {
      const response = await call(settingsRoute, '/api/platform/settings', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/platform/settings');
      expect(seen[0].url).toContain('tenant_id=t_1');
      expect(seen[0].headers['X-Tenant-Id']).toBe('t_1');
      const body = await response.json();
      expect(body.data.data.code).toBe('acme');
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(403, 'authorization_denied')) as typeof fetch;

    try {
      const response = await call(settingsRoute, '/api/platform/settings', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/billing', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(billingRoute, '/api/platform/billing');
    expect(response.status).toBe(200);
    expect((await response.json()).data.meta.fallback).toBe(true);
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(billingRoute, '/api/platform/billing')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(billingRoute, '/api/platform/billing', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the billing endpoint with a real tenant', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200({ plan_code: 'pro', status: 'active', base_currency: 'INR', invoices: [] });
    }) as typeof fetch;

    try {
      const response = await call(billingRoute, '/api/platform/billing', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/platform/billing');
      expect(seen[0].url).toContain('tenant_id=t_1');
      expect(seen[0].headers['X-Tenant-Id']).toBe('t_1');
      const body = await response.json();
      expect(body.data.data.plan_code).toBe('pro');
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(403, 'authorization_denied')) as typeof fetch;

    try {
      const response = await call(billingRoute, '/api/platform/billing', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/security', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(securityRoute, '/api/platform/security');
    expect(response.status).toBe(200);
    expect((await response.json()).data.meta.fallback).toBe(true);
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(securityRoute, '/api/platform/security')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(securityRoute, '/api/platform/security', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the security endpoint with a real tenant', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200({ mfa: { enrolled: 3, total_users: 10 }, authz: { denied_today: 2, denied_total: 5 }, audit: { total: 100 } });
    }) as typeof fetch;

    try {
      const response = await call(securityRoute, '/api/platform/security', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/platform/security');
      expect(seen[0].url).toContain('tenant_id=t_1');
      expect(seen[0].headers['X-Tenant-Id']).toBe('t_1');
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(403, 'authorization_denied')) as typeof fetch;

    try {
      const response = await call(securityRoute, '/api/platform/security', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/platform/system', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(systemRoute, '/api/platform/system');
    expect(response.status).toBe(200);
    expect((await response.json()).data.meta.fallback).toBe(true);
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(systemRoute, '/api/platform/system')).status).toBe(401);
  });

  it('proxies the system endpoint', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: string[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL) => {
      seen.push(String(input));
      return ok200({ status: 'ready', checks: { central_database: 'ok', cache: 'ok' } });
    }) as typeof fetch;

    try {
      const response = await call(systemRoute, '/api/platform/system', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen.some((url) => url.includes('/api/v1/platform/system'))).toBe(true);
      const body = await response.json();
      expect(body.data.data.status).toBe('ready');
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(403, 'authorization_denied')) as typeof fetch;

    try {
      const response = await call(systemRoute, '/api/platform/system', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});
