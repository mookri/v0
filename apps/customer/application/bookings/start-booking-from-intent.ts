import type { BookingIntent } from '@navdhya/booking/src/contracts/BookingIntent';

export interface BookingDraft {
  pujaId: string;
  panditId?: string;
  slotRef?: string;
  idempotencyKey: string;
  source: string;
}

export type IntentResolution =
  | { ok: true; draft: BookingDraft }
  | { ok: false; needs: string[]; reason: string };

// Web entry collects partial data; customer completes it. Never invent a
// schedule or customer — report exactly what is still needed.
export function resolveBookingIntent(intent: BookingIntent): IntentResolution {
  const { pujaId, slotRef } = intent;
  const needs: string[] = [];
  if (!pujaId) needs.push('puja');
  if (!slotRef) needs.push('slot');
  if (!pujaId || !slotRef || needs.length > 0) return { ok: false, needs, reason: 'INTENT_INCOMPLETE' };
  return {
    ok: true,
    draft: {
      pujaId,
      panditId: intent.panditId,
      slotRef,
      idempotencyKey: intent.idempotencyKey,
      source: intent.source,
    },
  };
}
