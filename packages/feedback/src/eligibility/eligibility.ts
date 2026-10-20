export interface ReviewEligibility {
  eligible: boolean;
  reasonCode?: 'NOT_OWNER' | 'NOT_COMPLETED' | 'WINDOW_CLOSED' | 'ALREADY_SUBMITTED' | 'DISPUTE_BLOCKED';
  expiresAt?: string;
  reviewTarget?: { type: 'partner' | 'service' | 'booking'; id: string };
}

export function evaluateEligibility(input: {
  ownsBooking: boolean;
  fulfillmentCompleted: boolean;
  nowIso: string;
  windowEndsAt?: string;
  alreadySubmitted: boolean;
}): ReviewEligibility {
  if (!input.ownsBooking) return { eligible: false, reasonCode: 'NOT_OWNER' };
  if (!input.fulfillmentCompleted) return { eligible: false, reasonCode: 'NOT_COMPLETED' };
  if (input.alreadySubmitted) return { eligible: false, reasonCode: 'ALREADY_SUBMITTED' };
  if (input.windowEndsAt && input.nowIso >= input.windowEndsAt) {
    return { eligible: false, reasonCode: 'WINDOW_CLOSED' };
  }
  return { eligible: true, expiresAt: input.windowEndsAt };
}
