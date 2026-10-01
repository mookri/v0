export type ApplicationStatus =
  | 'DRAFT'
  | 'SUBMITTED'
  | 'UNDER_REVIEW'
  | 'APPROVED'
  | 'REJECTED'
  | 'APPEALED'
  | 'WITHDRAWN';

export type ApplicationTrack = 'pandit' | 'partner-vendor' | 'partner-individual';

export interface PartnerApplication {
  id: string;
  identityId: string;
  type: 'PANDIT' | 'VENDOR';
  track: ApplicationTrack;
  // Pinned at creation: rule changes ship as new versions, never mutate
  // in-flight applications.
  trackVersion: string;
  // Tenant scope; absent = default market (legacy/dev records).
  market?: string;
  status: ApplicationStatus;
  // Set when a resubmission supersedes a rejected/withdrawn application.
  supersedes?: string;
  submittedAt?: string;
  reviewedAt?: string;
  reviewerId?: string;
  rejectionCode?: 'DOCUMENT_INCOMPLETE' | 'SKILL_REQUIREMENT_NOT_MET' | 'SERVICE_AREA_UNSUPPORTED' | 'VERIFICATION_FAILED' | 'PROFILE_INCOMPLETE' | 'OTHER';
}

export function canSubmitApplication(status: ApplicationStatus): boolean {
  return status === 'DRAFT' || status === 'WITHDRAWN';
}

const APPLICATION_TRANSITIONS: Record<ApplicationStatus, ApplicationStatus[]> = {
  DRAFT: ['SUBMITTED', 'WITHDRAWN'],
  SUBMITTED: ['UNDER_REVIEW', 'WITHDRAWN'],
  UNDER_REVIEW: ['APPROVED', 'REJECTED', 'DRAFT', 'WITHDRAWN'],
  APPROVED: [],
  REJECTED: ['APPEALED', 'WITHDRAWN'],
  APPEALED: ['UNDER_REVIEW', 'WITHDRAWN'],
  WITHDRAWN: [],
};

export function canTransitionApplication(from: ApplicationStatus, to: ApplicationStatus): boolean {
  return APPLICATION_TRANSITIONS[from]?.includes(to) ?? false;
}

export function assertApplicationTransition(from: ApplicationStatus, to: ApplicationStatus): void {
  if (!canTransitionApplication(from, to)) throw new Error(`ILLEGAL_APPLICATION_TRANSITION:${from}->${to}`);
}

export function applicationTransitionTable(): Record<ApplicationStatus, ApplicationStatus[]> {
  return APPLICATION_TRANSITIONS;
}
