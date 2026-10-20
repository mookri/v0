/**
 * @jest-environment node
 *
 * W1b admin console BFF routes — `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`.
 *
 * Thin forwarders: Laravel stays the sole authority; what the BFF must get
 * right is the envelope contract and (for the module routes) that the tenant
 * travels on `X-Tenant-Id` rather than `tenant_id`, since those endpoints are
 * tenant-scoped through `ResolveTenantContext` (header-only) and never take a
 * body/query tenant.
 */
import { GET as adminUsers } from '@/app/api/admin/users/route';
import { GET as adminPandits } from '@/app/api/admin/pandits/route';
import { GET as adminVendors } from '@/app/api/admin/vendors/route';
import { GET as adminManagers } from '@/app/api/admin/managers/route';
import { GET as adminSupport } from '@/app/api/admin/support/route';
import { GET as adminBookings } from '@/app/api/admin/bookings/route';
import { GET as adminContent } from '@/app/api/admin/content/route';
import { GET as adminSeo } from '@/app/api/admin/seo/route';
import { GET as adminPayouts } from '@/app/api/admin/payouts/route';
import { GET as adminCatalog } from '@/app/api/admin/catalog/route';

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

describe('GET /api/admin/users', () => {
  it('falls back with an empty page when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminUsers, '/api/admin/users');
    expect(response.status).toBe(200);
    const body = await response.json();
    expect(body.data).toEqual({ data: [], meta: { page: 1, limit: 20, total: 0, fallback: true } });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(adminUsers, '/api/admin/users')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(adminUsers, '/api/admin/users', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('forwards the console tenant (no more tenant_id: empty)', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200([{ id: 'm1', state: 'active', user: { id: 'u1', name: 'Asha', email: 'a@x.dev', status: 'active' } }]);
    }) as typeof fetch;

    try {
      const response = await call(adminUsers, '/api/admin/users', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('tenant_id=t_1');
      expect(seen[0].headers['X-Tenant-Id']).toBe('t_1');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/admin/pandits', () => {
  it('falls back to an empty page when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminPandits, '/api/admin/pandits');
    expect(response.status).toBe(200);
    expect((await response.json()).data).toEqual({ data: [], meta: { fallback: true, limit: 50 } });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(adminPandits, '/api/admin/pandits')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(adminPandits, '/api/admin/pandits', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the module endpoint and carries the tenant on X-Tenant-Id', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200([{ id: 'p1', display_name: 'Pandit A', status: 'ACTIVE', verification_status: 'VERIFIED' }]);
    }) as typeof fetch;

    try {
      const response = await call(adminPandits, '/api/admin/pandits?limit=20&status=ACTIVE', {
        cookie: SESSION_COOKIE,
      });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/pandit/admin/profiles');
      expect(seen[0].url).toContain('limit=20');
      expect(seen[0].url).toContain('status=ACTIVE');
      // Module routes are tenant-scoped via the header, never the query.
      expect(seen[0].url).not.toContain('tenant_id=');
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
      const response = await call(adminPandits, '/api/admin/pandits', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/admin/vendors', () => {
  it('falls back to an empty page when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminVendors, '/api/admin/vendors');
    expect(response.status).toBe(200);
    expect((await response.json()).data).toEqual({ data: [], meta: { fallback: true, limit: 50 } });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(adminVendors, '/api/admin/vendors')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(adminVendors, '/api/admin/vendors', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the module endpoint and carries the tenant on X-Tenant-Id', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200([{ id: 'v1', name: 'Flora & Co', city: 'Pune', status: 'ACTIVE', verification_status: 'VERIFIED' }]);
    }) as typeof fetch;

    try {
      const response = await call(adminVendors, '/api/admin/vendors', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/vendor/admin/vendors');
      expect(seen[0].url).not.toContain('tenant_id=');
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
      const response = await call(adminVendors, '/api/admin/vendors', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/admin/managers', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminManagers, '/api/admin/managers');
    expect(response.status).toBe(200);
    expect((await response.json()).data).toEqual({ data: [], meta: { fallback: true, limit: 50 } });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(adminManagers, '/api/admin/managers')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(adminManagers, '/api/admin/managers', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the onboarding review queue and carries the tenant on X-Tenant-Id', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200([{ id: 'a1', track: 'pandit', status: 'SUBMITTED', sla_breached: false }]);
    }) as typeof fetch;

    try {
      const response = await call(adminManagers, '/api/admin/managers?status=SUBMITTED', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/onboarding/review/queue');
      expect(seen[0].url).toContain('status=SUBMITTED');
      expect(seen[0].url).not.toContain('tenant_id=');
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
      const response = await call(adminManagers, '/api/admin/managers', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/admin/support', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminSupport, '/api/admin/support');
    expect(response.status).toBe(200);
    expect((await response.json()).data).toEqual({ data: [], meta: { fallback: true } });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(adminSupport, '/api/admin/support')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(adminSupport, '/api/admin/support', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('re-points from the never-existing /platform/support to the module endpoint', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: string[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL) => {
      const url = String(input);
      seen.push(url);
      return ok200([{ id: 't1', subject: 'Refund', category: 'billing', status: 'OPEN' }]);
    }) as typeof fetch;

    try {
      const response = await call(adminSupport, '/api/admin/support', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen.some((url) => url.includes('/api/v1/customers/support/staff/tickets'))).toBe(true);
      expect(seen.some((url) => /\/platform\/support(\?|$)/.test(url))).toBe(false);
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () => fail(403, 'authorization_denied')) as typeof fetch;

    try {
      const response = await call(adminSupport, '/api/admin/support', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

describe('GET /api/admin/catalog', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call(adminCatalog, '/api/admin/catalog');
    expect(response.status).toBe(200);
    expect((await response.json()).data).toEqual({ data: [], meta: { fallback: true, limit: 50 } });
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call(adminCatalog, '/api/admin/catalog')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv(BACKEND_NO_TENANT);
    const response = await call(adminCatalog, '/api/admin/catalog', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies the catalog endpoint and carries the tenant on X-Tenant-Id', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: { url: string; headers: Record<string, string> }[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      seen.push({ url: String(input), headers: (init?.headers ?? {}) as Record<string, string> });
      return ok200([{ id: 'i1', sku: 'puja-griha', name: 'Griha Pravesh', status: 'DRAFT', currency: 'INR', price_minor: 120000 }]);
    }) as typeof fetch;

    try {
      const response = await call(adminCatalog, '/api/admin/catalog', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen[0].url).toContain('/api/v1/catalog/items');
      expect(seen[0].url).not.toContain('tenant_id=');
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
      const response = await call(adminCatalog, '/api/admin/catalog', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});

