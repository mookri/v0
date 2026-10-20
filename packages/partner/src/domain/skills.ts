export type SkillLevel = 'BASIC' | 'INTERMEDIATE' | 'ADVANCED';
export type SkillStatus = 'SELF_DECLARED' | 'SUBMITTED' | 'VERIFIED' | 'EXPIRED' | 'REJECTED';

export interface PartnerSkill {
  skillId: string;
  level?: SkillLevel;
  status: SkillStatus;
  verifiedAt?: string;
}

export type ServiceAreaType = 'CITY' | 'ZONE' | 'PINCODE' | 'RADIUS';

export interface ServiceArea {
  id: string;
  partnerId: string;
  type: ServiceAreaType;
  reference: string;
  active: boolean;
}

export type ServiceCapabilityStatus = 'ELIGIBLE' | 'ACTIVE' | 'PAUSED' | 'SUSPENDED';

export interface PartnerServiceCapability {
  partnerId: string;
  serviceId: string;
  status: ServiceCapabilityStatus;
  languages: string[];
}
