import { Partner, PartnerStatus } from '../domain/partner';

export interface PartnerRepository {
  findById(id: string): Promise<Partner | null>;
  findByIdentity(identityId: string): Promise<Partner[]>;
  save(partner: Partner): Promise<void>;
}

const LIFECYCLE: Record<PartnerStatus, PartnerStatus[]> = {
  APPLIED: ['ONBOARDING', 'REJECTED', 'DEACTIVATED'],
  ONBOARDING: ['VERIFICATION_PENDING', 'REJECTED', 'DEACTIVATED'],
  VERIFICATION_PENDING: ['VERIFIED', 'REJECTED', 'DEACTIVATED'],
  VERIFIED: ['ACTIVE', 'REJECTED', 'DEACTIVATED'],
  ACTIVE: ['SUSPENDED', 'PAUSED', 'DEACTIVATED', 'TERMINATED'],
  SUSPENDED: ['ACTIVE', 'TERMINATED', 'DEACTIVATED'],
  PAUSED: ['ACTIVE', 'TERMINATED', 'DEACTIVATED'],
  REJECTED: ['APPLIED', 'DEACTIVATED'],
  DEACTIVATED: [],
  TERMINATED: [],
};

export function canTransitionPartner(from: PartnerStatus, to: PartnerStatus): boolean {
  return LIFECYCLE[from]?.includes(to) ?? false;
}
