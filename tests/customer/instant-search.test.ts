import { test, expect } from 'vitest';
import {
  instantSearchBookStartUrl,
  slotRefFrom,
  windowEnd,
} from '@apps/customer/application/instant-search';
import { decodeBookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';

// Instant-book search (B2): window math must never send an end before
// the start or wrap past midnight, and every selection hands book/start
// a versioned intent that decodes into a ready draft (puja + slot).

test('windowEnd computes the 60-minute window and refuses to wrap midnight', () => {
  expect(windowEnd('10:00')).toBe('11:00');
  expect(windowEnd('09:30')).toBe('10:30');
  expect(windowEnd('22:59')).toBe('23:59');
  // Crosses midnight — never sent as an end time.
  expect(windowEnd('23:30')).toBeNull();
  expect(windowEnd('23:00')).toBeNull();
  // Malformed input fails closed.
  expect(windowEnd('')).toBeNull();
  expect(windowEnd('25:00')).toBeNull();
  expect(windowEnd('10:61')).toBeNull();
  expect(windowEnd('10:00:00')).toBeNull();
});

test('slotRefFrom converts wall-clock date+time to an ISO schedule', () => {
  const iso = slotRefFrom('2030-05-01', '10:00');
  expect(iso).not.toBeNull();
  const parsed = new Date(iso as string);
  expect(parsed.getFullYear()).toBe(2030);
  expect(parsed.getHours()).toBe(10); // local wall time round-trips
  expect(parsed.getMinutes()).toBe(0);

  expect(slotRefFrom('yesterday', '10:00')).toBeNull();
  expect(slotRefFrom('2030-05-01', '99:99')).toBeNull();
  expect(slotRefFrom('2030-05-01', '')).toBeNull();
});

test('a selection encodes into a ready book/start intent', () => {
  const now = '2030-04-30T12:00:00.000Z';
  const url = instantSearchBookStartUrl({
    pujaId: 'PUJA123',
    date: '2030-05-01',
    start: '10:00',
    panditId: 'PD1',
    idempotencyKey: 'key-1',
    nowIso: now,
    ttlMinutes: 60,
  });

  expect(url.startsWith('/book/start?intent=')).toBe(true);

  const intent = decodeBookingIntent(decodeURIComponent(url.slice('/book/start?intent='.length)), now);
  expect(intent.v).toBe(1);
  expect(intent.pujaId).toBe('PUJA123');
  expect(intent.panditId).toBe('PD1');
  expect(intent.source).toBe('instant-search');
  expect(intent.idempotencyKey).toBe('key-1');
  expect(intent.slotRef).toBe(new Date('2030-05-01T10:00').toISOString());
  expect(intent.expiresAtIso).toBe('2030-04-30T13:00:00.000Z');
});

test('temple-slot selections carry puja + slot with no pandit', () => {
  const now = '2030-04-30T12:00:00.000Z';
  const url = instantSearchBookStartUrl({
    pujaId: 'PUJA123',
    date: '2030-05-01',
    start: '06:30',
    idempotencyKey: 'key-2',
    nowIso: now,
  });

  const intent = decodeBookingIntent(decodeURIComponent(url.slice('/book/start?intent='.length)), now);
  expect(intent.pujaId).toBe('PUJA123');
  expect(intent.panditId).toBeUndefined();
  expect(intent.slotRef).not.toBeUndefined();
});

test('selections fail closed without a puja or with a malformed window', () => {
  const now = '2030-04-30T12:00:00.000Z';

  expect(() =>
    instantSearchBookStartUrl({
      pujaId: '',
      date: '2030-05-01',
      start: '10:00',
      idempotencyKey: 'key-3',
      nowIso: now,
    }),
  ).toThrow('PUJA_REQUIRED');

  expect(() =>
    instantSearchBookStartUrl({
      pujaId: 'PUJA123',
      date: 'not-a-date',
      start: '10:00',
      idempotencyKey: 'key-4',
      nowIso: now,
    }),
  ).toThrow('INTENT_MALFORMED');

  expect(() =>
    instantSearchBookStartUrl({
      pujaId: 'PUJA123',
      date: '2030-05-01',
      start: '10:00',
      idempotencyKey: 'key-5',
      nowIso: 'garbage',
    }),
  ).toThrow('INTENT_MALFORMED');
});
