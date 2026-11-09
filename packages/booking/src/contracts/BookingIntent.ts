export const BOOKING_INTENT_VERSION = 1;
const MAX_ENCODED_LENGTH = 2048;

export interface BookingIntent {
  v: 1;
  pujaId?: string;
  panditId?: string;
  slotRef?: string;
  durationMinutes?: number;
  timezone?: string;
  templeId?: string;
  slotType?: string;
  source: string;
  idempotencyKey: string;
  createdAtIso: string;
  expiresAtIso: string;
}

// Browser- and node-safe base64url (RFC 4648 §5, unpadded). This module
// runs in client components — book/start decodes inside an effect, where
// the node-only buffer global does not exist — so the codec is built from
// web standards (TextEncoder/btoa) available in every runtime. Output is
// byte-identical to node's `base64url` encoding, so previously shared
// links keep decoding, and atob throws on malformed input, which the
// decoder maps to INTENT_MALFORMED (same outcome lenient node decoding
// produced before, via JSON.parse failing one step later).
function safeBtoa(binary: string): string {
  if (typeof btoa === 'function') return btoa(binary);
  return Buffer.from(binary, 'binary').toString('base64');
}

function safeAtob(base64: string): string {
  if (typeof atob === 'function') return atob(base64);
  return Buffer.from(base64, 'base64').toString('binary');
}

function encodeUtf8(json: string): Uint8Array {
  if (typeof TextEncoder !== 'undefined') {
    return new TextEncoder().encode(json);
  }
  return Uint8Array.from(Buffer.from(json, 'utf-8'));
}

function decodeUtf8(bytes: Uint8Array): string {
  if (typeof TextDecoder !== 'undefined') {
    return new TextDecoder().decode(bytes);
  }
  return Buffer.from(bytes).toString('utf-8');
}

function toBase64Url(json: string): string {
  const bytes = encodeUtf8(json);
  let binary = '';
  for (const byte of bytes) binary += String.fromCharCode(byte);
  return safeBtoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function fromBase64Url(raw: string): string {
  const base64 = raw.replace(/-/g, '+').replace(/_/g, '/');
  const binary = safeAtob(base64);
  const bytes = Uint8Array.from(binary, (ch) => ch.charCodeAt(0));
  return decodeUtf8(bytes);
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

  if (intent.durationMinutes !== undefined && (typeof intent.durationMinutes !== 'number' || intent.durationMinutes <= 0 || !Number.isFinite(intent.durationMinutes))) {
    throw new Error('INTENT_MALFORMED');
  }

  for (const field of [intent.pujaId, intent.panditId, intent.slotRef, intent.timezone, intent.templeId, intent.slotType] as const) {
    if (field !== undefined && (typeof field !== 'string' || field.length === 0)) throw new Error('INTENT_MALFORMED');
  }
  return intent as BookingIntent;
}

/**
 * Builds safe customer application handoff URL with encoded booking intent (WP5).
 */
export function buildBookStartUrl(baseCustomerUrl: string, intent: BookingIntent): string {
  const encoded = encodeBookingIntent(intent);
  const base = baseCustomerUrl.replace(/\/$/, '');
  return `${base}/book/start?intent=${encodeURIComponent(encoded)}`;
}
