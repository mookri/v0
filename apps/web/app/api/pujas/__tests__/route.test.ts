/**
 * @jest-environment node
 */
import { GET } from '@/app/api/pujas/route';

const originalFetch = global.fetch;

afterEach(() => {
  global.fetch = originalFetch;
  delete process.env.CUSTOMER_BACKEND;
  delete process.env.NAVDHYA_API_BASE;
  delete process.env.CUSTOMER_TENANT_ID;
});

describe('/api/pujas (public catalog for Quick Book)', () => {
  it('serves an empty list in mock mode — the sample state QuickBook supports', async () => {
    delete process.env.CUSTOMER_BACKEND;

    const res = await GET();

    expect(res.status).toBe(200);
    const body = await res.json();
    expect(body.data.items).toEqual([]);
    expect(res.headers.get('Cache-Control')).toContain('max-age=60');
  });

  it('forwards to the public catalog with the tenant but NO bearer', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://backend.test';
    process.env.CUSTOMER_TENANT_ID = 'tenant_abc';

    const fetchMock = jest.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({
        data: [
          {
            id: 'cat_1',
            object: 'catalog_item',
            sku: 'PUJA-LAKSHMI',
            name: 'Lakshmi Puja',
            description: 'Evening puja',
            currency: 'INR',
            price_minor: 49900,
          },
        ],
        meta: { request_id: 'req_1' },
      }),
    });
    global.fetch = fetchMock as unknown as typeof fetch;

    const res = await GET();

    expect(res.status).toBe(200);
    const body = await res.json();
    expect(body.data.items).toEqual([
      {
        id: 'cat_1',
        sku: 'PUJA-LAKSHMI',
        name: 'Lakshmi Puja',
        description: 'Evening puja',
        currency: 'INR',
        price_minor: 49900,
      },
    ]);

    const [url, init] = fetchMock.mock.calls[0] as [string, RequestInit];
    expect(url).toContain('/api/v1/catalog/public/items?tenant_id=tenant_abc');
    const headers = (init.headers ?? {}) as Record<string, string>;
    expect(headers.Authorization).toBeUndefined();
  });

  it('degrades to an empty list when the backend fails — never an error surface', async () => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://backend.test';
    process.env.CUSTOMER_TENANT_ID = 'tenant_abc';
    global.fetch = jest.fn().mockRejectedValue(new Error('down')) as unknown as typeof fetch;

    const res = await GET();

    expect(res.status).toBe(200);
    expect((await res.json()).data.items).toEqual([]);
  });
});
