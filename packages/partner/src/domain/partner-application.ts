export type ApplicationStatus = 'DRAFT' | 'SUBMITTED' | 'UNDER_REVIEW' | 'APPROVED' | 'REJECTED' | 'WITHDRAWN';

export interface PartnerApplication {
  id: string;
  identityId: string;
  type: 'PANDIT' | 'VENDOR';
  status: ApplicationStatus;
  submittedAt?: string;
  reviewedAt?: string;
  reviewerId?: string;
  rejectionCode?: 'DOCUMENT_INCOMPLETE' | 'SKILL_REQUIREMENT_NOT_MET' | 'SERVICE_AREA_UNSUPPORTED' | 'VERIFICATION_FAILED' | 'PROFILE_INCOMPLETE' | 'OTHER';
}

export function canSubmitApplication(status: ApplicationStatus): boolean {
  return status === 'DRAFT' || status === 'WITHDRAWN';
}
