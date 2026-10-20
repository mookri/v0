export interface ReadinessBlocker {
  code: 'DOCUMENT_EXPIRED' | 'NO_SERVICE' | 'NO_SERVICE_AREA' | 'NO_AVAILABILITY' | 'AGREEMENT_NOT_ACCEPTED' | 'NOT_VERIFIED' | 'NOT_ACTIVE';
}

export interface PartnerReadiness {
  partnerId: string;
  readyForAssignments: boolean;
  blockers: ReadinessBlocker[];
}

export function evaluateReadiness(input: {
  partnerId: string;
  active: boolean;
  verified: boolean;
  services: number;
  serviceAreas: number;
  availabilitySet: boolean;
  agreementAccepted: boolean;
  documentsValid: boolean;
}): PartnerReadiness {
  const blockers: ReadinessBlocker[] = [];
  if (!input.active) blockers.push({ code: 'NOT_ACTIVE' });
  if (!input.verified) blockers.push({ code: 'NOT_VERIFIED' });
  if (input.services === 0) blockers.push({ code: 'NO_SERVICE' });
  if (input.serviceAreas === 0) blockers.push({ code: 'NO_SERVICE_AREA' });
  if (!input.availabilitySet) blockers.push({ code: 'NO_AVAILABILITY' });
  if (!input.agreementAccepted) blockers.push({ code: 'AGREEMENT_NOT_ACCEPTED' });
  if (!input.documentsValid) blockers.push({ code: 'DOCUMENT_EXPIRED' });
  return { partnerId: input.partnerId, readyForAssignments: blockers.length === 0, blockers };
}

export interface PartnerEligibilitySnapshot {
  partnerId: string;
  active: boolean;
  serviceCapabilities: string[];
  verifiedSkills: string[];
  serviceAreas: string[];
  capacity: number;
  sourceVersion: number;
  lastUpdatedAt: string;
}
