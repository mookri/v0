import { describe, expect, it } from 'vitest';
import {
  encodeBookingIntent,
  decodeBookingIntent,
  buildBookStartUrl,
  type BookingIntent,
} from '../contracts/BookingIntent';

describe('BookingIntent contract and handoff (WP5)', () => {
  const sampleIntent: BookingIntent = {
    v: 1,
    pujaId: 'puja_satya_123',
    panditId: 'pnd_varanasi_456',
    slotRef: 'slot_20261110_morning',
    durationMinutes: 120,
    timezone: 'Asia/Kolkata',
    templeId: 'tmp_kashi_01',
    slotType: 'TEMPLE_HOMAM',
    source: 'web_pandit_search',
    idempotencyKey: 'ik_handoff_test_001',
    createdAtIso: '2026-11-09T10:00:00Z',
    expiresAtIso: '2026-11-09T10:30:00Z',
  };

  it('encodes and decodes intent with all ritual metadata intact', () => {
    const encoded = encodeBookingIntent(sampleIntent);
    expect(typeof encoded).toBe('string');
    expect(encoded.length).toBeGreaterThan(0);

    const decoded = decodeBookingIntent(encoded, '2026-11-09T10:15:00Z');
    expect(decoded).toEqual(sampleIntent);
  });

  it('builds canonical book/start handoff URL', () => {
    const url = buildBookStartUrl('https://customer.navdhya.com', sampleIntent);
    expect(url).toContain('https://customer.navdhya.com/book/start?intent=');
    const param = new URL(url).searchParams.get('intent');
    expect(param).toBeTruthy();
    const roundtrip = decodeBookingIntent(decodeURIComponent(param!), '2026-11-09T10:15:00Z');
    expect(roundtrip.pujaId).toBe('puja_satya_123');
    expect(roundtrip.templeId).toBe('tmp_kashi_01');
    expect(roundtrip.durationMinutes).toBe(120);
  });

  it('rejects expired or malformed intent', () => {
    const encoded = encodeBookingIntent(sampleIntent);
    expect(() => decodeBookingIntent(encoded, '2026-11-09T10:35:00Z')).toThrow('INTENT_EXPIRED');
    expect(() => decodeBookingIntent('bad_base64_string', '2026-11-09T10:15:00Z')).toThrow('INTENT_MALFORMED');
  });
});
