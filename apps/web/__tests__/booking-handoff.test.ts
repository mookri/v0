import { buildBookStartUrl, decodeBookingIntent, type BookingIntent } from '@navdhya/booking';

describe('Web Funnel Booking Handoff (WP5)', () => {
  it('constructs valid customer handoff URL from book funnel parameters', () => {
    const customerBase = 'https://customer.navdhya.com';
    const now = new Date('2026-11-09T12:00:00Z');
    const expiresAt = new Date('2026-11-09T12:30:00Z');

    const intent: BookingIntent = {
      v: 1,
      pujaId: 'home-puja',
      panditId: 'pnd_delhi_99',
      durationMinutes: 90,
      timezone: 'Asia/Kolkata',
      source: 'web_book_funnel',
      idempotencyKey: 'ik_web_test_123',
      createdAtIso: now.toISOString(),
      expiresAtIso: expiresAt.toISOString(),
    };

    const targetUrl = buildBookStartUrl(customerBase, intent);
    expect(targetUrl.startsWith('https://customer.navdhya.com/book/start?intent=')).toBe(true);

    const parsed = new URL(targetUrl);
    const rawIntent = parsed.searchParams.get('intent');
    expect(rawIntent).toBeTruthy();

    const decoded = decodeBookingIntent(decodeURIComponent(rawIntent!), '2026-11-09T12:10:00Z');
    expect(decoded.pujaId).toBe('home-puja');
    expect(decoded.panditId).toBe('pnd_delhi_99');
    expect(decoded.durationMinutes).toBe(90);
    expect(decoded.source).toBe('web_book_funnel');
  });

  it('constructs valid pandit consultation handoff URL', () => {
    const customerBase = 'http://localhost:3000';
    const intent: BookingIntent = {
      v: 1,
      pujaId: 'pandit-consultation',
      source: 'web_pandit_consultation',
      idempotencyKey: 'ik_consult_456',
      createdAtIso: '2026-11-09T12:00:00Z',
      expiresAtIso: '2026-11-09T12:30:00Z',
    };

    const targetUrl = buildBookStartUrl(customerBase, intent);
    expect(targetUrl.startsWith('http://localhost:3000/book/start?intent=')).toBe(true);
  });
});
