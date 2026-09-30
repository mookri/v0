import { test, expect } from 'vitest';
import { POST } from '@apps/partner/app/api/assignments/[assignmentId]/accept/route';
import { GET as health } from '@apps/partner/app/api/health/route';

const post = (headers: Record<string, string> = {}) =>
  POST(new Request('https://partner.navdhya.com/api/assignments/a1/accept', { method: 'POST', headers }), {
    params: { assignmentId: 'a1' },
  });

test('accept without actor is unauthenticated', async () => {
  const res = await post();
  expect(res.status).toBe(401);
  const body = (await res.json()) as { error: { code: string } };
  expect(body.error.code).toBe('UNAUTHENTICATED');
});

test('accept maps stub store miss to stable not-found envelope', async () => {
  const res = await post({ 'x-partner-id': 'p1', 'Idempotency-Key': 'ik-1' });
  expect(res.status).toBe(404);
  const body = (await res.json()) as { error: { code: string; message: string; requestId: string } };
  expect(body.error.code).toBe('ASSIGNMENT_NOT_FOUND');
  expect(typeof body.error.requestId).toBe('string');
});

test('partner health returns ok envelope with no-store', async () => {
  const res = await health();
  expect(res.status).toBe(200);
  expect(res.headers.get('Cache-Control')).toBe('no-store');
  const body = (await res.json()) as { data: { status: string; product: string } };
  expect(body.data).toEqual({ status: 'ok', product: 'partner' });
});
