import { test, expect } from 'vitest';
import { buildBookStartUrl } from '@apps/web/lib/booking-handoff/handoff-url';
import { decodeBookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';

const NOW = '2026-10-01T00:00:00.000Z';

test('emitter produces a URL the receiver decodes', () => {
  const url = buildBookStartUrl('https://app.navdhya.com', {
    pujaId: 'puja_x',
    slotRef: 'slot_z',
    idempotencyKey: 'intent-e1',
    nowIso: NOW,
  });
  expect(url.startsWith('https://app.navdhya.com/book/start?intent=')).toBe(true);
  const raw = new URL(url).searchParams.get('intent')!;
  const intent = decodeBookingIntent(raw, NOW);
  expect(intent).toMatchObject({ v: 1, pujaId: 'puja_x', slotRef: 'slot_z', source: 'web-book' });
  expect(intent.expiresAtIso).toBe('2026-10-01T01:00:00.000Z');
});

test('emitter rejects bad timestamps before encoding', () => {
  expect(() =>
    buildBookStartUrl('https://app.navdhya.com', { idempotencyKey: 'k', nowIso: 'not-a-date' }),
  ).toThrow('INTENT_MALFORMED');
});
