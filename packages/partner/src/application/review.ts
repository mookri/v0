import {
  PartnerApplication,
  assertApplicationTransition,
  ApplicationStatus,
} from '@navdhya/partner/src/domain/partner-application';

export type ReviewDecision = 'APPROVED' | 'REJECTED' | 'REQUEST_INFO';

export interface ReviewInput {
  decision: ReviewDecision;
  reviewerId: string;
  reasonCode?: 'DOCUMENT_INCOMPLETE' | 'SKILL_REQUIREMENT_NOT_MET' | 'SERVICE_AREA_UNSUPPORTED' | 'VERIFICATION_FAILED' | 'PROFILE_INCOMPLETE' | 'OTHER';
  note?: string;
  nowIso: string;
}

export interface ReviewRecord {
  id: string;
  applicationId: string;
  reviewerId: string;
  decision: ReviewDecision;
  reasonCode?: ReviewInput['reasonCode'];
  note?: string;
  atIso: string;
}

// Review state machine: UNDER_REVIEW/APPEALED → decision. REQUEST_INFO
// returns the application to applicant control with needs preserved
// (caller recomputes needs[]); rejections require a reasonCode (four-eyes
// enforced at the route layer: rejections need a second reviewer — see
// review routes). Every decision returns its audit record.
export function reviewApplication(
  app: PartnerApplication,
  input: ReviewInput,
): { application: PartnerApplication; record: ReviewRecord } {
  if (app.status !== 'UNDER_REVIEW' && app.status !== 'APPEALED') {
    throw new Error(`REVIEW_INVALID_STATE:${app.status}`);
  }
  if (input.decision === 'REJECTED' && !input.reasonCode) throw new Error('REVIEW_REASON_REQUIRED');
  const to: ApplicationStatus =
    input.decision === 'APPROVED' ? 'APPROVED' : input.decision === 'REJECTED' ? 'REJECTED' : 'DRAFT';
  // REQUEST_INFO returns to DRAFT only from UNDER_REVIEW (appeals stay open).
  if (input.decision === 'REQUEST_INFO' && app.status === 'APPEALED') {
    throw new Error('REVIEW_INVALID_STATE:APPEALED');
  }
  assertApplicationTransition(app.status, to);
  const next: PartnerApplication =
    input.decision === 'REQUEST_INFO'
      ? { ...app, status: 'DRAFT' }
      : {
          ...app,
          status: input.decision,
          reviewedAt: input.nowIso,
          reviewerId: input.reviewerId,
          ...(input.decision === 'REJECTED' ? { rejectionCode: input.reasonCode } : {}),
        };
  return {
    application: next,
    record: {
      id: `rev_${app.id}_${Date.parse(input.nowIso)}`,
      applicationId: app.id,
      reviewerId: input.reviewerId,
      decision: input.decision,
      reasonCode: input.reasonCode,
      note: input.note,
      atIso: input.nowIso,
    },
  };
}
