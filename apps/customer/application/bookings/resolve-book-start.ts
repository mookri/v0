import { decodeBookingIntent, BookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';
import { resolveBookingIntent, BookingDraft } from '@/application/bookings/start-booking-from-intent';

export type BookStartResult =
  | { status: 'missing' }
  | { status: 'invalid'; code: string }
  | { status: 'incomplete'; needs: string[]; intent: BookingIntent }
  | { status: 'ready'; draft: BookingDraft; intent: BookingIntent };

// Pure receiver for GET /book/start?intent=… — decodes the web entry intent
// and reports readiness. Never throws for user-facing cases.
export function resolveBookStart(intentRaw: string | undefined, nowIso: string): BookStartResult {
  if (!intentRaw) return { status: 'missing' };
  let intent: BookingIntent;
  try {
    intent = decodeBookingIntent(intentRaw, nowIso);
  } catch (e) {
    return { status: 'invalid', code: e instanceof Error ? e.message : 'INTENT_MALFORMED' };
  }
  const resolution = resolveBookingIntent(intent);
  if (!resolution.ok) return { status: 'incomplete', needs: resolution.needs, intent };
  return { status: 'ready', draft: resolution.draft, intent };
}
