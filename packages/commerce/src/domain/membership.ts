export type MembershipStatus = 'PENDING' | 'ACTIVE' | 'PAUSED' | 'EXPIRED' | 'CANCELLED';

export interface MembershipPlan {
  id: string;
  name: string;
  version: string;
  status: 'DRAFT' | 'ACTIVE' | 'RETIRED';
  benefitIds: string[];
  effectiveFrom: string;
  effectiveTo?: string;
}

export interface Membership {
  id: string;
  customerId: string;
  planId: string;
  planVersion: string;
  status: MembershipStatus;
  startsAt: string;
  endsAt?: string;
  source: 'PURCHASE' | 'GRANT' | 'PROMOTION' | 'ADMIN';
}

export function isMembershipActive(m: Membership, nowIso: string): boolean {
  if (m.status !== 'ACTIVE') return false;
  if (m.endsAt && m.endsAt <= nowIso) return false;
  return m.startsAt <= nowIso;
}
