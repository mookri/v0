export interface BenefitSummary {
  type: string;
  sourceType: string;
  sourceId: string;
  effectDescription: string;
  policyVersion: string;
}

export interface CommerceDecision {
  decisionId: string;
  eligibleBenefits: BenefitSummary[];
  rejectedBenefits: { type: string; reason: string }[];
  policyVersions: string[];
  generatedAt: string;
  expiresAt: string;
}

export interface CustomerCommerceContext {
  customerId: string;
  membershipActive: boolean;
  creditsMinor: number;
  walletAvailableMinor: number;
  loyaltyPoints: number;
  entitlementsAvailable: number;
  currency: string;
}

// Explicit, policy-owned application order. Architecture requires explicitness, not this exact order.
export const BENEFIT_APPLICATION_ORDER = [
  'CREDIT',
  'PROMOTION',
  'COUPON',
  'MEMBERSHIP',
  'LOYALTY',
  'WALLET',
  'GIFT_CARD',
] as const;

export function resolveCommerceDecision(input: {
  decisionId: string;
  candidates: { type: string; sourceType: string; sourceId: string; effectDescription: string; policyVersion: string; eligible: boolean; rejectReason?: string }[];
  policyVersions: string[];
  nowIso: string;
  ttlSeconds: number;
}): CommerceDecision {
  const order: Map<string, number> = new Map(BENEFIT_APPLICATION_ORDER.map((t, i) => [t, i]));
  const sorted = [...input.candidates].sort(
    (a, b) => (order.get(a.type) ?? 99) - (order.get(b.type) ?? 99),
  );
  return {
    decisionId: input.decisionId,
    eligibleBenefits: sorted.filter((c) => c.eligible).map((c) => ({
      type: c.type, sourceType: c.sourceType, sourceId: c.sourceId,
      effectDescription: c.effectDescription, policyVersion: c.policyVersion,
    })),
    rejectedBenefits: sorted.filter((c) => !c.eligible).map((c) => ({ type: c.type, reason: c.rejectReason ?? 'INELIGIBLE' })),
    policyVersions: input.policyVersions,
    generatedAt: input.nowIso,
    expiresAt: new Date(Date.parse(input.nowIso) + input.ttlSeconds * 1000).toISOString(),
  };
}
