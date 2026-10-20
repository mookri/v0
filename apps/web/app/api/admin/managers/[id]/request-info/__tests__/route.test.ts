/**
 * @jest-environment node
 *
 * W3 — onboarding review "request more information" action.
 */
import { POST } from '@/app/api/admin/managers/[id]/request-info/route';

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
const BACKEND_ON = { CUSTOMER_BACKEND: 'laravel', NAVDHYA_API_BASE: 'http://127.0.0.1:9', CUSTOMER_TENANT_ID: 't_1' };
const BACKEND_OFF = { CUSTOMER_BACKEND: undefined, NAVDHYA_API_BASE: undefined, CUSTOMER_TENANT_ID: undefined };

afterEach(restoreEnv);

function call(id: string, headers: Record<string, string> = {}) {
  return POST(new Request(`http://localhost/api/admin/managers/${id}/request-info`, { method: 'POST', headers }), {
    params: Promise.resolve({ id }),
  });
}

describe('POST /api/admin/managers/{id}/request-info', () => {
  it('falls back when the backend is disabled', async () => {
    setEnv(BACKEND_OFF);
    const response = await call('app_1');
    expect(response.status).toBe(200);
    const body = await response.json();
    expect(body.data.meta.fallback).toBe(true);
  });

  it('rejects without a session once the backend is enabled', async () => {
    setEnv(BACKEND_ON);
    expect((await call('app_1')).status).toBe(401);
  });

  it('answers 503 when no tenant is configured', async () => {
    setEnv({ CUSTOMER_BACKEND: 'laravel', NAVDHYA_API_BASE: 'http://127.0.0.1:9', CUSTOMER_TENANT_ID: undefined });
    const response = await call('app_1', { cookie: SESSION_COOKIE });
    expect(response.status).toBe(503);
    expect((await response.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('forwards the decision to the onboarding review endpoint', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    const seen: string[] = [];
    global.fetch = jest.fn(async (input: RequestInfo | URL) => {
      seen.push(String(input));
      return new Response(JSON.stringify({ data: { id: 'app_1', status: 'under_review' }, meta: { request_id: 'req_backend' } }), {
        status: 200,
        headers: { 'content-type': 'application/json' },
      });
    }) as typeof fetch;

    try {
      const response = await call('app_1', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(200);
      expect(seen.some((url) => url.includes('/api/v1/onboarding/review/app_1:request-info'))).toBe(true);
    } finally {
      global.fetch = originalFetch;
    }
  });

  it('maps a backend denial to its BFF code', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    global.fetch = jest.fn(async () =>
      new Response(JSON.stringify({ error: { code: 'authorization_denied', requestId: 'req_backend' } }), {
        status: 403,
        headers: { 'content-type': 'application/json' },
      }),
    ) as typeof fetch;

    try {
      const response = await call('app_1', { cookie: SESSION_COOKIE });
      expect(response.status).toBe(403);
      expect((await response.json()).error.code).toBe('FORBIDDEN');
    } finally {
      global.fetch = originalFetch;
    }
  });
});
