import { encodeBookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';

// Instant-book search application layer (B2): the shared result shape
// (BFF route + page + tests agree on one type), the window math, and the
// selection handoff. A selection never posts itself — it hands puja
// (+pandit) + the exact slot to book/start through the versioned intent
// codec, the same entry contract every other surface uses. Browser-safe
// by construction (no Buffer): the codec runs in client components.
export interface InstantSearchPandit {
  id: string;
  display_name: string;
  rating_avg: number;
  rating_count: number;
  specializations: string[];
  base_rate_minor: number | null;
  currency: string;
}

export interface InstantSearchTempleSlot {
  temple_id: string;
  temple_name: string;
  city: string;
  puja_id: string;
  day_of_week: number;
  start: string;
  end: string;
  capacity: number | null;
  price_override_minor: number | null;
}

export interface InstantSearchResult {
  object: 'instant_search';
  date: string;
  start: string;
  end: string;
  puja_id: string | null;
  pandits: InstantSearchPandit[];
  temple_slots: InstantSearchTempleSlot[];
}

/**
 * End of the search window for a 'HH:MM' start (60-minute default — the
 * reservation contract posts duration_minutes: 60). Null when the value
 * is malformed or the window would cross midnight: the backend rejects
 * end-before-start, so we never send a wrapped time.
 */
export function windowEnd(start: string, durationMinutes = 60): string | null {
  const m = /^(\d{1,2}):(\d{2})$/.exec(start);
  if (!m) return null;
  const hour = Number(m[1]);
  const minute = Number(m[2]);
  if (hour > 23 || minute > 59) return null;
  const end = hour * 60 + minute + durationMinutes;
  if (end > 1439) return null;
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(Math.floor(end / 60))}:${pad(end % 60)}`;
}

/**
 * ISO schedule for date + 'HH:MM' (local wall time — the slot values the
 * search surfaces are wall-clock, and book/start converts them the same
 * way the picker does). Null on anything malformed.
 */
export function slotRefFrom(date: string, start: string): string | null {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return null;
  if (!/^\d{1,2}:\d{2}$/.test(start)) return null;
  const iso = new Date(`${date}T${start}`);
  return Number.isNaN(iso.getTime()) ? null : iso.toISOString();
}

export interface InstantSearchSelection {
  pujaId: string;
  date: string;
  start: string;
  panditId?: string;
  idempotencyKey: string;
  nowIso: string;
  ttlMinutes?: number;
}

/**
 * Encoded /book/start handoff for a search selection. Fails closed: a
 * selection without a puja can never reach a ready draft (the intent
 * decoder would ship it to the "we still need: puja" dead end), and a
 * malformed window never becomes a schedule.
 */
export function instantSearchBookStartUrl(selection: InstantSearchSelection): string {
  if (!selection.pujaId) throw new Error('PUJA_REQUIRED');
  const slotRef = slotRefFrom(selection.date, selection.start);
  if (slotRef === null) throw new Error('INTENT_MALFORMED');
  const createdAt = new Date(selection.nowIso).getTime();
  if (Number.isNaN(createdAt)) throw new Error('INTENT_MALFORMED');

  // Instant search can surface today's elapsed slots; the reservation
  // gate rejects them, so fail closed here instead of handing the customer
  // a dead draft.
  if (new Date(slotRef).getTime() <= createdAt) throw new Error('SLOT_IN_PAST');

  const raw = encodeBookingIntent({
    v: 1,
    pujaId: selection.pujaId,
    ...(selection.panditId ? { panditId: selection.panditId } : {}),
    slotRef,
    source: 'instant-search',
    idempotencyKey: selection.idempotencyKey,
    createdAtIso: selection.nowIso,
    expiresAtIso: new Date(createdAt + (selection.ttlMinutes ?? 60) * 60_000).toISOString(),
  });

  return `/book/start?intent=${encodeURIComponent(raw)}`;
}
