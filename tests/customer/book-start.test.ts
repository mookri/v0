import { test, expect } from 'vitest';
import { encodeBookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';
import { resolveBookStart } from '@apps/customer/application/bookings/resolve-book-start';

const NOW = '2026-10-01T00:00:00.000Z';
const base = {
  v: 1 as const,
  source: 'web-book',
  idempotencyKey: 'intent-9',
  createdAtIso: NOW,
  expiresAtIso: '2026-10-01T01:00:00.000Z',
};

test('receiver reports missing, invalid, incomplete, and ready', () => {
  expect(resolveBookStart(undefined, NOW)).toEqual({ status: 'missing' });
  expect(resolveBookStart('!!!', NOW)).toEqual({ status: 'invalid', code: 'INTENT_MALFORMED' });

  const expired = encodeBookingIntent({ ...base, pujaId: 'p', slotRef: 's' });
  expect(resolveBookStart(expired, '2026-10-02T00:00:00.000Z')).toEqual({ status: 'invalid', code: 'INTENT_EXPIRED' });

  const partial = encodeBookingIntent({ ...base, slotRef: 's' });
  const inc = resolveBookStart(partial, NOW);
  expect(inc.status).toBe('incomplete');
  if (inc.status === 'incomplete') expect(inc.needs).toEqual(['puja']);

  const full = encodeBookingIntent({ ...base, pujaId: 'puja_x', panditId: 'pandit_y', slotRef: 'slot_z' });
  const ready = resolveBookStart(full, NOW);
  expect(ready.status).toBe('ready');
  if (ready.status === 'ready') {
    expect(ready.draft).toEqual({
      pujaId: 'puja_x',
      panditId: 'pandit_y',
      slotRef: 'slot_z',
      idempotencyKey: 'intent-9',
      source: 'web-book',
    });
  }
});
