/**
 * @jest-environment node
 *
 * T-TR01-04 (T-07): verify admin reject route fails closed without countersignature
 * and forwards structured payload to Laravel onboarding review reject.
 */
import { POST } from '@/app/api/admin/managers/[id]/reject/route';

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

function call(id: string, body: unknown, headers: Record<string, string> = {}) {
  return POST(
    new Request(`http://localhost/api/admin/managers/${id}/reject`, {
      method: 'POST',
      headers: { 'content-type': 'application/json', ...headers },
      body: JSON.stringify(body),
    }),
    { params: Promise.resolve({ id }) },
  );
}

describe('POST /api/admin/managers/{id}/reject', () => {
  it('fails closed with 422 when second_reviewer is empty even in fallback mode', async () => {
    setEnv(BACKEND_OFF);
    const response = await call('app_1', { reason_code: 'incomplete_identity', second_reviewer: '' });
    expect(response.status).toBe(422);
    const json = await response.json();
    expect(json.error.code).toBe('VALIDATION_ERROR');
  });

  it('forwards structured reason_code, note, and non-empty second_reviewer to Laravel', async () => {
    setEnv(BACKEND_ON);
    const originalFetch = global.fetch;
    let capturedBody = '';
    let capturedUrl = '';
    global.fetch = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      capturedUrl = String(input);
      capturedBody = String(init?.body ?? '');
      return new Response(JSON.stringify({ data: { id: 'app_1', status: 'REJECTED' }, meta: { request_id: 'req_1' } }), {
        status: 200,
        headers: { 'content-type': 'application/json' },
      });
    }) as typeof fetch;

    try {
      const response = await call(
        'app_1',
        { reason_code: 'unverifiable_credential', note: 'Missing seal', second_reviewer: 'rev_2' },
        { cookie: SESSION_COOKIE },
      );
      expect(response.status).toBe(200);
      expect(capturedUrl).toContain('/api/v1/onboarding/review/app_1:reject');
      expect(JSON.parse(capturedBody)).toEqual({
        reason_code: 'unverifiable_credential',
        note: 'Missing seal',
        second_reviewer: 'rev_2',
      });
    } finally {
      global.fetch = originalFetch;
    }
  });
});
