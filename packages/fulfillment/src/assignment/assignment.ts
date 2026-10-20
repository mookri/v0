export type AssignmentStatus =
  | 'UNASSIGNED' | 'CANDIDATE_FOUND' | 'OFFERED' | 'ACCEPTED' | 'REJECTED' | 'EXPIRED' | 'REASSIGNMENT_REQUIRED';

export interface AssignmentOffer {
  id: string;
  fulfillmentId: string;
  partnerId: string;
  expiresAt: string;
  status: 'PENDING' | 'ACCEPTED' | 'REJECTED' | 'EXPIRED' | 'CANCELLED';
}

export function isOfferAcceptable(offer: AssignmentOffer, nowIso: string): boolean {
  return offer.status === 'PENDING' && offer.expiresAt > nowIso;
}

export function rankCandidates<T extends { id: string; score: number }>(candidates: T[]): T[] {
  return [...candidates].sort((a, b) => b.score - a.score || a.id.localeCompare(b.id));
}
