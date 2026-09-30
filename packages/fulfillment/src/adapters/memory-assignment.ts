import { AssignmentOffer, isOfferAcceptable } from '@navdhya/fulfillment/src/assignment/assignment';
import { AssignmentGateway } from '@navdhya/fulfillment/src/ports/gateways';

// Single-instance staging adapter (same limitation as the events memory
// bus): offers live in process memory. A shared store replaces this behind
// the AssignmentGateway port without touching callers.
export class MemoryAssignmentGateway implements AssignmentGateway {
  private readonly offers = new Map<string, AssignmentOffer>();
  private seq = 0;

  constructor(private readonly now: () => string = () => new Date().toISOString()) {}

  async offer(input: { fulfillmentId: string; partnerId: string; expiresAt: string }): Promise<{ offerId: string }> {
    this.seq += 1;
    const offer: AssignmentOffer = {
      id: `offer_${this.seq}`,
      fulfillmentId: input.fulfillmentId,
      partnerId: input.partnerId,
      expiresAt: input.expiresAt,
      status: 'PENDING',
    };
    this.offers.set(offer.id, offer);
    return { offerId: offer.id };
  }

  async get(offerId: string): Promise<AssignmentOffer | null> {
    return this.offers.get(offerId) ?? null;
  }

  async accept(offerId: string): Promise<boolean> {
    const offer = this.offers.get(offerId);
    if (!offer || !isOfferAcceptable(offer, this.now())) return false;
    this.offers.set(offerId, { ...offer, status: 'ACCEPTED' });
    return true;
  }
}
