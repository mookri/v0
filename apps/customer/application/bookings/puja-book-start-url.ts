import { encodeBookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';

// Client-side entry builder for the detail page's "Book this puja" action.
// The receiver (book/start) only understands a versioned, encoded intent —
// raw JSON in the `intent` param dies at decode — so every field the
// decoder requires is filled here: source + idempotency key + timestamps.
// TTL mirrors the web→customer handoff (60 minutes). Built at click time
// (not render) so the timestamps stay fresh without hydration drift.
// Runs in the browser: the shared codec must stay Buffer-free.
export function pujaBookStartUrl(
  pujaId: string,
  idempotencyKey: string,
  nowIso: string,
  ttlMinutes = 60,
): string {
  const createdAt = new Date(nowIso).getTime();
  if (Number.isNaN(createdAt)) throw new Error('INTENT_MALFORMED');
  const raw = encodeBookingIntent({
    v: 1,
    pujaId,
    source: 'puja-detail',
    idempotencyKey,
    createdAtIso: nowIso,
    expiresAtIso: new Date(createdAt + ttlMinutes * 60_000).toISOString(),
  });
  return `/book/start?intent=${encodeURIComponent(raw)}`;
}
