export interface PartnerOfferView {
  id: string;
  fulfillmentId: string;
  partnerId: string;
  expiresAt: string;
  status: 'PENDING' | 'ACCEPTED' | 'REJECTED' | 'EXPIRED' | 'CANCELLED';
}

export type OfferResult =
  | { ok: true; offer: PartnerOfferView }
  | { ok: false; reason: 'NOT_FOUND' | 'NOT_OWNED' | 'EXPIRED' | 'INVALID_STATE' };

export function acceptOffer(
  offer: PartnerOfferView | null,
  actorPartnerId: string,
  nowIso: string,
  idempotencyKey: string,
): OfferResult {
  void idempotencyKey;
  if (!offer) return { ok: false, reason: 'NOT_FOUND' };
  if (offer.partnerId !== actorPartnerId) return { ok: false, reason: 'NOT_OWNED' };
  if (offer.status !== 'PENDING') return { ok: false, reason: 'INVALID_STATE' };
  if (offer.expiresAt <= nowIso) return { ok: false, reason: 'EXPIRED' };
  return { ok: true, offer: { ...offer, status: 'ACCEPTED' } };
}

export type RejectReason = 'SCHEDULE_CONFLICT' | 'TRAVEL_ISSUE' | 'SERVICE_MISMATCH' | 'PERSONAL' | 'OTHER';

export function rejectOffer(
  offer: PartnerOfferView | null,
  actorPartnerId: string,
  reason: RejectReason,
): OfferResult {
  void reason;
  if (!offer) return { ok: false, reason: 'NOT_FOUND' };
  if (offer.partnerId !== actorPartnerId) return { ok: false, reason: 'NOT_OWNED' };
  if (offer.status !== 'PENDING') return { ok: false, reason: 'INVALID_STATE' };
  return { ok: true, offer: { ...offer, status: 'REJECTED' } };
}
