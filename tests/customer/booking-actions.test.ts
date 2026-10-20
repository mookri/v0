import { test, expect } from 'vitest';
import { rescheduleIsoFromInput } from '@apps/customer/application/bookings/reschedule-from-input';

// B4 cancel/reschedule UI: the datetime-local value must round-trip to
// an ISO instant the BFF accepts, and anything empty, malformed or in
// the past fails closed before it reaches the network.

const NOW = '2030-05-01T10:00:00.000Z';

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
