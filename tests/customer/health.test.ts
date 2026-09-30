import { test, expect } from 'vitest';
import { GET } from '@apps/customer/app/api/health/route';

test('health endpoint returns ok envelope with no-store', async () => {
  const res = await GET();
  expect(res.status).toBe(200);
  expect(res.headers.get('Cache-Control')).toBe('no-store');
  const body = (await res.json()) as { data: { status: string; product: string }; requestId: string };
  expect(body.data).toEqual({ status: 'ok', product: 'customer' });
  expect(typeof body.requestId).toBe('string');
});
