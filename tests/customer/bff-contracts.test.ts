import { test, expect } from 'vitest';
import { ok, err } from '../../apps/customer/application/api-envelope';
import { anonymousContext } from '../../apps/customer/application/request-context';
import { apiRoutes } from '../../apps/customer/application/api-routes';

test('error envelope never leaks internals', () => {
  const e = err('BOOKING_NOT_FOUND', 'Booking could not be found.', 'req1');
  expect(e).toEqual({ error: { code: 'BOOKING_NOT_FOUND', message: 'Booking could not be found.', requestId: 'req1' } });
  expect(JSON.stringify(e)).not.toMatch(/postgres|stack|prisma/i);
});

test('anonymous context carries product identity without actor', () => {
  const ctx = anonymousContext('customer.bookings', 'req1');
  expect(ctx.product).toBe('customer');
  expect(ctx.actor).toBeNull();
  expect(ctx.routeId).toBe('customer.bookings');
});

test('api route registry covers all customer endpoints with methods', () => {
  const paths = apiRoutes.map((r) => `${r.method} ${r.path}`);
  expect(paths).toContain('GET /api/home');
  expect(paths).toContain('GET /api/bookings');
  expect(paths).toContain('GET /api/bookings/:bookingId');
  expect(paths).toContain('POST /api/bookings/:bookingId/cancel');
  expect(paths).toContain('POST /api/bookings/:bookingId/reschedule');
});

test('ok envelope wraps view models, not domain entities', () => {
  const wrapped = ok({ items: [], pagination: { hasMore: false, nextCursor: null } }, 'req1');
  expect(wrapped.requestId).toBe('req1');
  expect(wrapped.data).toHaveProperty('items');
});
