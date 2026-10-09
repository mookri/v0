/**
 * @jest-environment node
 *
 * Contract §8: rate-limit responses must carry Retry-After. The BFF's own
 * throttle sets it directly; a 429 *forwarded from the backend* used to be
 * re-wrapped by `laravelErrorResponse` without the header — an
 * unactionable 429 for the client. These tests pin the relay in both
 * directions (capture on the wire → emit on the envelope) and guard
 * against fabricating a header the backend never sent.
 *
 * Uses the node environment because it exercises `fetch`/`Response`
 * globals (the suite default is jsdom).
 */
import { CustomerApiError } from '@/adapters/backend/customer-api-client';
import { laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';

type EnvKey = 'NAVDHYA_API_BASE' | 'CUSTOMER_TENANT_ID';

function backend429(retryAfter: string | null) {
  return {
    ok: false,
    status: 429,
    json: async () => ({
      error: { code: 'rate_limited', message: 'Too many attempts.', request_id: 'rid-1' },
      // Real Laravel envelope: error.request_id (snake) is picked up via
      // meta.request_id by callLaravel — mirrors the production path.
      meta: { request_id: 'rid-1' },
    }),
    headers: {
      get: (key: string) => (key.toLowerCase() === 'retry-after' ? retryAfter : null),
    },
  };
}

async function captureError(retryAfter: string | null): Promise<CustomerApiError> {
  jest.spyOn(globalThis, 'fetch').mockResolvedValue(backend429(retryAfter) as unknown as Response);
  try {
    await laravelPost(null, '/customers/auth/otp', {});
  } catch (e) {
    return e as CustomerApiError;
  }
  throw new Error('expected laravelPost to reject');
}

describe('forwarded backend 429 keeps Retry-After (contract §8)', () => {
  const saved: Partial<Record<EnvKey, string | undefined>> = {};

  beforeEach(() => {
    (['NAVDHYA_API_BASE', 'CUSTOMER_TENANT_ID'] as EnvKey[]).forEach((k) => {
      saved[k] = process.env[k];
    });
    process.env.NAVDHYA_API_BASE = 'http://laravel.test';
    process.env.CUSTOMER_TENANT_ID = '01TESTTENANT00000000000000';
  });

  afterEach(() => {
    jest.restoreAllMocks();
    (Object.keys(saved) as EnvKey[]).forEach((k) => {
      if (saved[k] === undefined) delete process.env[k];
      else process.env[k] = saved[k];
    });
  });

  it('captures the backend Retry-After on the thrown error', async () => {
    const error = await captureError('42');
    expect(error).toBeInstanceOf(CustomerApiError);
    expect(error.code).toBe('RATE_LIMITED');
    expect(error.status).toBe(429);
    expect(error.retryAfter).toBe('42');
  });

  it('relays Retry-After on the BFF envelope response', async () => {
    const error = await captureError('42');
    const res = laravelErrorResponse(
      error,
      'Too many attempts. Please try again later.',
      'req-1',
    );
    expect(res.status).toBe(429);
    expect(res.headers.get('retry-after')).toBe('42');
    const body = (await res.json()) as { error: { code: string; requestId: string } };
    expect(body.error.code).toBe('RATE_LIMITED');
    // The envelope carries the BFF's own correlation id (the route
    // passes it); the backend's rid-1 rides on the error object for
    // server-side tracing — see error.requestId below.
    expect(body.error.requestId).toBe('req-1');
    expect(error.requestId).toBe('rid-1');
  });

  it('does not fabricate a Retry-After the backend never sent', async () => {
    const error = await captureError(null);
    expect(error.retryAfter).toBeUndefined();
    const res = laravelErrorResponse(error, 'Signup is unavailable.', 'req-2');
    expect(res.headers.get('retry-after')).toBeNull();
    expect(res.status).toBe(429);
  });

  it('relays a backend Retry-After on any status it arrived with', async () => {
    // 503 is the other legitimate Retry-After carrier. The BFF is a
    // relay: it emits exactly what the backend sent and fabricates
    // nothing — no status-based filtering that could drop a real signal.
    jest.spyOn(globalThis, 'fetch').mockResolvedValue({
      ok: false,
      status: 503,
      json: async () => ({
        error: { code: 'service_unavailable', request_id: 'rid-3' },
      }),
      headers: { get: (key: string) => (key.toLowerCase() === 'retry-after' ? '7' : null) },
    } as unknown as Response);
    let caught: CustomerApiError | undefined;
    try {
      await laravelPost(null, '/customers/auth/otp', {});
    } catch (e) {
      caught = e as CustomerApiError;
    }
    expect(caught?.status).toBe(503);
    expect(caught?.retryAfter).toBe('7');
    const res = laravelErrorResponse(caught, 'Dependency unavailable.', 'req-3');
    expect(res.headers.get('retry-after')).toBe('7');
  });
});

describe('upstream timeout renders an envelope (contract §8)', () => {
  const saved: Partial<Record<EnvKey, string | undefined>> = {};

  beforeEach(() => {
    (['NAVDHYA_API_BASE', 'CUSTOMER_TENANT_ID'] as EnvKey[]).forEach((k) => {
      saved[k] = process.env[k];
    });
    process.env.NAVDHYA_API_BASE = 'http://laravel.test';
    process.env.CUSTOMER_TENANT_ID = '01TESTTENANT00000000000000';
  });

  afterEach(() => {
    jest.restoreAllMocks();
    (Object.keys(saved) as EnvKey[]).forEach((k) => {
      if (saved[k] === undefined) delete process.env[k];
      else process.env[k] = saved[k];
    });
  });

  it('maps AbortSignal TimeoutError to a 504 CustomerApiError carrying the rid', async () => {
    // Exactly what fetch rejects with when AbortSignal.timeout fires.
    jest
      .spyOn(globalThis, 'fetch')
      .mockRejectedValue(
        new DOMException('The operation was aborted due to timeout', 'TimeoutError'),
      );
    let caught: unknown;
    try {
      await laravelPost(null, '/customers/auth/signup', {}, {}, { requestId: 'req_t1' });
    } catch (e) {
      caught = e;
    }
    expect(caught).toBeInstanceOf(CustomerApiError);
    const timeout = caught as CustomerApiError;
    expect(timeout.status).toBe(504);
    expect(timeout.code).toBe('DEPENDENCY_UNAVAILABLE');
    // The envelope must carry the BFF's correlation id — the old bare 500
    // carried nothing, breaking §8 and log correlation.
    expect(timeout.requestId).toBe('req_t1');
    const res = laravelErrorResponse(timeout, 'Signup timed out.', 'req_t1');
    expect(res.status).toBe(504);
    const body = (await res.json()) as { error: { code: string; requestId: string } };
    expect(body.error.code).toBe('DEPENDENCY_UNAVAILABLE');
    expect(body.error.requestId).toBe('req_t1');
  });

  it('non-timeout failures keep propagating raw (BFF convention)', async () => {
    const boom = new TypeError('fetch failed');
    jest.spyOn(globalThis, 'fetch').mockRejectedValue(boom);
    let caught: unknown;
    try {
      await laravelPost(null, '/customers/auth/otp', {});
    } catch (e) {
      caught = e;
    }
    expect(caught).toBe(boom);
    expect(caught).not.toBeInstanceOf(CustomerApiError);
  });
});
