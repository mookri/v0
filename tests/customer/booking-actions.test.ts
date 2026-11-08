import { test, expect } from 'vitest';
import { rescheduleIsoFromInput } from '@apps/customer/application/bookings/reschedule-from-input';
import { createCustomerApi } from '@apps/customer/adapters/backend/customer-api-client';

// B4 cancel/reschedule UI: the datetime-local value must round-trip to
// an ISO instant the BFF accepts, and anything empty, malformed or in
// the past fails closed before it reaches the network.

const NOW = '2030-05-01T10:00:00.000Z';

test('reschedule sends the displayed booking version and command identity', async () => {
  const calls: Array<{ path: string; init?: RequestInit }> = [];
  const api = createCustomerApi(async (path, init) => {
    calls.push({ path, init });
    return Response.json({ data: { version: 8 } });
  });
  await api.rescheduleBooking('b1', '2030-06-01T10:00:00.000Z', 7, 'command-identity');
  expect(calls).toHaveLength(1);
  expect(calls[0].path).toBe('/api/bookings/b1/reschedule');
  expect(JSON.parse(calls[0].init?.body as string)).toEqual({ newStartTime: '2030-06-01T10:00:00.000Z', version: 7 });
  expect(new Headers(calls[0].init?.headers).get('Idempotency-Key')).toBe('command-identity');
});

test('a future datetime-local value becomes an ISO instant', () => {
  const iso = rescheduleIsoFromInput('2030-05-02T14:30', NOW);
  expect(iso).not.toBeNull();
  expect(new Date(iso as string).getTime()).toBeGreaterThan(new Date(NOW).getTime());

  // Seconds are accepted too.
  expect(rescheduleIsoFromInput('2030-05-02T14:30:15', NOW)).not.toBeNull();
});

test('past, now, empty or malformed values fail closed', () => {
  expect(rescheduleIsoFromInput('', NOW)).toBeNull();
  expect(rescheduleIsoFromInput('   ', NOW)).toBeNull();
  expect(rescheduleIsoFromInput('2030-05-02', NOW)).toBeNull();
  expect(rescheduleIsoFromInput('tomorrow 2pm', NOW)).toBeNull();
  expect(rescheduleIsoFromInput('2030-13-40T99:99', NOW)).toBeNull();

  // Exactly now and the past are never sent.
  expect(rescheduleIsoFromInput('2030-05-01T10:00', NOW)).toBeNull();
  expect(rescheduleIsoFromInput('2030-04-30T23:59', NOW)).toBeNull();
});
