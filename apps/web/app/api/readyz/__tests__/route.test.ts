/**
 * @jest-environment node
 */
import { GET } from '@/app/api/readyz/route';

const originalFetch = global.fetch;

function setBackendEnv(enabled: boolean, base = 'http://backend.test') {
  if (enabled) {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = base;
  } else {
    delete process.env.CUSTOMER_BACKEND;
    delete process.env.NAVDHYA_API_BASE;
  }
}

afterEach(() => {
  global.fetch = originalFetch;
  setBackendEnv(false);
});

describe('/api/readyz (readiness)', () => {
  it('is ready without a backend in mock mode', async () => {
    setBackendEnv(false);

    const res = await GET();

    expect(res.status).toBe(200);
    expect((await res.json()).backend).toBe('mock');
  });

  it('probes the kernel /up on the backend origin when wired', async () => {
    setBackendEnv(true);
    const fetchMock = jest.fn().mockResolvedValue({ ok: true, status: 200 });
    global.fetch = fetchMock as unknown as typeof fetch;

    const res = await GET();

    expect(res.status).toBe(200);
    expect((await res.json()).backend).toBe('up');
    expect(fetchMock).toHaveBeenCalledWith('http://backend.test/up', expect.anything());
  });

  it('reports 503 when the backend /up answers not-ok', async () => {
    setBackendEnv(true);
    global.fetch = jest.fn().mockResolvedValue({ ok: false, status: 503 }) as unknown as typeof fetch;

    const res = await GET();

    expect(res.status).toBe(503);
    expect((await res.json()).backend).toBe('up:503');
  });

  it('reports 503 when the backend is unreachable', async () => {
    setBackendEnv(true);
    global.fetch = jest.fn().mockRejectedValue(new Error('ECONNREFUSED')) as unknown as typeof fetch;

    const res = await GET();

    expect(res.status).toBe(503);
    expect((await res.json()).backend).toBe('unreachable');
  });
});
