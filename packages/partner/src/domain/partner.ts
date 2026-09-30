export type PartnerType = 'PANDIT' | 'VENDOR' | 'SERVICE_PROVIDER';
export type PartnerStatus =
  | 'APPLIED' | 'ONBOARDING' | 'VERIFICATION_PENDING' | 'VERIFIED'
  | 'ACTIVE' | 'SUSPENDED' | 'PAUSED' | 'REJECTED' | 'DEACTIVATED' | 'TERMINATED';

export interface Partner {
  id: string;
  identityId: string;
  type: PartnerType;
  lifecycleStatus: PartnerStatus;
  agreementVersion: number;
  capabilityVersion: number;
}

export function isPartnerWorkEligible(status: PartnerStatus): boolean {
  return status === 'ACTIVE';
}
