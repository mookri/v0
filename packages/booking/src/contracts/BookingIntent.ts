export const BOOKING_INTENT_VERSION = 1;
const MAX_ENCODED_LENGTH = 2048;

export interface BookingIntent {
  v: 1;
  pujaId?: string;
  panditId?: string;
  slotRef?: string;
  source: string;
  idempotencyKey: string;
  createdAtIso: string;
  expiresAtIso: string;
}

function toBase64Url(json: string): string {
  return Buffer.from(json, 'utf8').toString('base64url');
}

function fromBase64Url(raw: string): string {
  return Buffer.from(raw, 'base64url').toString('utf8');
}

export function encodeBookingIntent(intent: BookingIntent): string {
  const raw = toBase64Url(JSON.stringify(intent));
  if (raw.length > MAX_ENCODED_LENGTH) throw new Error('INTENT_TOO_LARGE');
  return raw;
}

export function decodeBookingIntent(raw: string, nowIso: string): BookingIntent {
  if (!raw || raw.length > MAX_ENCODED_LENGTH) throw new Error('INTENT_MALFORMED');
  let parsed: unknown;
  try {
    parsed = JSON.parse(fromBase64Url(raw));
  } catch {
    throw new Error('INTENT_MALFORMED');
  }
  const intent = parsed as Partial<BookingIntent>;
  if (intent.v !== BOOKING_INTENT_VERSION) throw new Error('INTENT_VERSION');
  if (!intent.idempotencyKey || !intent.source || !intent.createdAtIso || !intent.expiresAtIso) {
    throw new Error('INTENT_MALFORMED');
  }
  if (intent.expiresAtIso <= nowIso) throw new Error('INTENT_EXPIRED');
  for (const field of [intent.pujaId, intent.panditId, intent.slotRef] as const) {
    if (field !== undefined && (typeof field !== 'string' || field.length === 0)) throw new Error('INTENT_MALFORMED');
  }
  return intent as BookingIntent;
}
