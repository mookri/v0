import {
  isValidServiceVenue,
  normalizeCanonicalGotra,
  normalizeCanonicalNakshatra,
  type ServiceVenue,
} from '@navdhya/validation';

// The review page holds the catalog item id (intent `pujaId`), but the
// reservation API validates `service_sku` against the catalog's SKUs —
// posting the id is a guaranteed 422 (VALIDATION_ERROR). Resolve id → SKU
// through the puja detail read (the one stable source for it) and fail
// closed when the item carries none: never post the id as a SKU.
// Injected deps keep the rule pure and testable; the page wires its API
// client in.
export interface ConfirmBookingDeps {
  getPuja: (id: string) => Promise<{ sku?: string }>;
  createBooking: (
    payload: {
      service_sku: string;
      scheduled_at: string;
      duration_minutes?: number;
      service_venue?: ServiceVenue;
      metadata?: Record<string, unknown>;
    },
    idempotencyKey: string,
  ) => Promise<unknown>;
}

export async function confirmBookingDraft(
  deps: ConfirmBookingDeps,
  draft: {
    pujaId: string;
    slotRef: string;
    idempotencyKey: string;
    serviceVenue?: string;
    gotra?: string;
    nakshatra?: string;
  },
): Promise<unknown> {
  const venue = draft.serviceVenue ? draft.serviceVenue.trim().toLowerCase() : 'home';
  if (!isValidServiceVenue(venue)) {
    throw new Error('INVALID_SERVICE_VENUE');
  }

  const canonicalGotra =
    draft.gotra && draft.gotra.trim() !== '' ? normalizeCanonicalGotra(draft.gotra) : undefined;
  if (draft.gotra && draft.gotra.trim() !== '' && !canonicalGotra) {
    throw new Error('INVALID_GOTRA');
  }

  const canonicalNakshatra =
    draft.nakshatra && draft.nakshatra.trim() !== ''
      ? normalizeCanonicalNakshatra(draft.nakshatra)
      : undefined;
  if (draft.nakshatra && draft.nakshatra.trim() !== '' && !canonicalNakshatra) {
    throw new Error('INVALID_NAKSHATRA');
  }

  const puja = await deps.getPuja(draft.pujaId);
  const sku = typeof puja?.sku === 'string' && puja.sku.length > 0 ? puja.sku : null;
  if (!sku) throw new Error('PUJA_SKU_UNAVAILABLE');

  return deps.createBooking(
    {
      service_sku: sku,
      scheduled_at: draft.slotRef,
      duration_minutes: 60,
      service_venue: venue,
      metadata:
        canonicalGotra || canonicalNakshatra
          ? {
              ...(canonicalGotra ? { gotra: canonicalGotra } : {}),
              ...(canonicalNakshatra ? { nakshatra: canonicalNakshatra } : {}),
            }
          : undefined,
    },
    draft.idempotencyKey,
  );
}
