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
    payload: { service_sku: string; scheduled_at: string; duration_minutes?: number },
    idempotencyKey: string,
  ) => Promise<unknown>;
}

export async function confirmBookingDraft(
  deps: ConfirmBookingDeps,
  draft: { pujaId: string; slotRef: string; idempotencyKey: string },
): Promise<unknown> {
  const puja = await deps.getPuja(draft.pujaId);
  const sku = typeof puja?.sku === 'string' && puja.sku.length > 0 ? puja.sku : null;
  if (!sku) throw new Error('PUJA_SKU_UNAVAILABLE');
  return deps.createBooking(
    { service_sku: sku, scheduled_at: draft.slotRef, duration_minutes: 60 },
    draft.idempotencyKey,
  );
}
