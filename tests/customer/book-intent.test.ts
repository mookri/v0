import { test, expect } from 'vitest';
import {
  encodeBookingIntent,
  decodeBookingIntent,
  BookingIntent,
} from '@navdhya/booking/src/contracts/BookingIntent';
import { resolveBookingIntent } from '@apps/customer/application/bookings/start-booking-from-intent';

const NOW = '2026-10-01T00:00:00.000Z';
const LATER = '2026-10-02T00:00:00.000Z';

const intent = (over: Partial<BookingIntent> = {}): BookingIntent => ({
  v: 1,
  pujaId: 'puja_lakshmi',
  panditId: 'pandit_7',
  slotRef: 'slot_42',
  source: 'web-book',
  idempotencyKey: 'intent-1',
  createdAtIso: NOW,
  expiresAtIso: '2026-10-01T01:00:00.000Z',
  ...over,
});

test('intent round-trips through URL-safe encoding', () => {
  const raw = encodeBookingIntent(intent());
  expect(raw).not.toMatch(/[+/=]/);
  expect(decodeBookingIntent(raw, NOW)).toEqual(intent());
});

test('intent rejects tampered, expired, and oversized payloads', () => {
  expect(() => decodeBookingIntent('!!!not-base64!!!', NOW)).toThrow('INTENT_MALFORMED');
  expect(() => decodeBookingIntent(encodeBookingIntent(intent()), LATER)).toThrow('INTENT_EXPIRED');
  expect(() =>
    decodeBookingIntent(encodeBookingIntent(intent({ pujaId: 'x'.repeat(3000) })), NOW),
  ).toThrow('INTENT_TOO_LARGE');
  const wrongVersion = encodeBookingIntent({ ...intent(), v: 2 as unknown as 1 });
  expect(() => decodeBookingIntent(wrongVersion, NOW)).toThrow('INTENT_VERSION');
});

test('resolver maps complete intents to drafts, reports gaps otherwise', () => {
  const full = resolveBookingIntent(intent());
  expect(full).toEqual({
    ok: true,
    draft: {
      pujaId: 'puja_lakshmi',
      panditId: 'pandit_7',
      slotRef: 'slot_42',
      idempotencyKey: 'intent-1',
      source: 'web-book',
    },
  });
  const partial = resolveBookingIntent(intent({ pujaId: undefined, slotRef: undefined }));
  expect(partial).toEqual({ ok: false, needs: ['puja', 'slot'], reason: 'INTENT_INCOMPLETE' });
});
