import { encodeBookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';

export interface BookHandoffFields {
  pujaId?: string;
  panditId?: string;
  slotRef?: string;
  idempotencyKey: string;
  nowIso: string;
  ttlMinutes?: number;
}

// Typed emitter for the web → customer booking handoff. Produces the
// /book/start?intent=… URL the customer receiver decodes. Pure and
// edge-safe (no stores); call from the entry form when its state is ready.
export function buildBookStartUrl(customerOrigin: string, fields: BookHandoffFields): string {
  const createdAt = new Date(fields.nowIso).getTime();
  if (Number.isNaN(createdAt)) throw new Error('INTENT_MALFORMED');
  const ttlMs = (fields.ttlMinutes ?? 60) * 60 * 1000;
  const raw = encodeBookingIntent({
    v: 1,
    pujaId: fields.pujaId,
    panditId: fields.panditId,
    slotRef: fields.slotRef,
    source: 'web-book',
    idempotencyKey: fields.idempotencyKey,
    createdAtIso: fields.nowIso,
    expiresAtIso: new Date(createdAt + ttlMs).toISOString(),
  });
  return `${customerOrigin}/book/start?intent=${raw}`;
}
