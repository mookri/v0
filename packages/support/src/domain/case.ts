export type CaseType =
  | 'INFORMATION_REQUEST'
  | 'SERVICE_COMPLAINT'
  | 'FINANCIAL_DISPUTE'
  | 'PROVIDER_DISPUTE'
  | 'INSTITUTIONAL_DISPUTE'
  | 'SAFETY_REPORT'
  | 'FORMAL_GRIEVANCE';

export type CaseCategory =
  | 'billing_refund'
  | 'booking_schedule'
  | 'punctuality_no_show'
  | 'ceremony_quality_chanting'
  | 'additional_charges_extortion'
  | 'temple_access_denial'
  | 'prasad_fulfillment'
  | 'streaming_failure'
  | 'harassment_safety'
  | 'privacy_media_violation'
  | 'provider_misconduct'
  | 'account_auth'
  | 'other';

export type CaseStatus =
  | 'NEW'
  | 'ACKNOWLEDGED'
  | 'CLASSIFIED'
  | 'TRIAGED'
  | 'UNDER_INVESTIGATION'
  | 'WAITING_FOR_CUSTOMER'
  | 'PENDING_THIRD_PARTY'
  | 'RESOLUTION_PROPOSED'
  | 'REFUND_PENDING'
  | 'REFUND_COMPLETED'
  | 'RESOLVED'
  | 'CLOSED'
  | 'REOPENED'
  | 'WITHDRAWN'
  | 'REFERRED_EXTERNALLY';

export type CasePriority = 'LOW' | 'NORMAL' | 'HIGH' | 'URGENT' | 'CRITICAL';
export type CaseVisibility = 'PARTICIPANT' | 'PROVIDER_REDACTED' | 'REPORTER_PRIVATE' | 'INTERNAL_STAFF' | 'RESTRICTED_SAFETY';

export interface SupportCase {
  id: string;
  caseReference: string;
  type: CaseType;
  category: CaseCategory;
  status: CaseStatus;
  urgency: CasePriority;
  visibilityScope: CaseVisibility;
  reporterUserId: string;
  bookingId?: string;
  incidentId?: string;
  assigneeId?: string;
  reopenCount: number;
  version: number;
  createdAt: string;
  acknowledgedAt?: string;
  dueAt?: string;
  resolvedAt?: string;
  closedAt?: string;
}

const TRANSITIONS: Record<CaseStatus, CaseStatus[]> = {
  NEW: ['ACKNOWLEDGED', 'CLASSIFIED', 'WITHDRAWN'],
  ACKNOWLEDGED: ['CLASSIFIED', 'TRIAGED', 'WITHDRAWN'],
  CLASSIFIED: ['TRIAGED', 'UNDER_INVESTIGATION', 'WITHDRAWN', 'REFERRED_EXTERNALLY'],
  TRIAGED: ['UNDER_INVESTIGATION', 'WAITING_FOR_CUSTOMER', 'PENDING_THIRD_PARTY', 'RESOLUTION_PROPOSED', 'WITHDRAWN', 'REFERRED_EXTERNALLY'],
  UNDER_INVESTIGATION: ['WAITING_FOR_CUSTOMER', 'PENDING_THIRD_PARTY', 'RESOLUTION_PROPOSED', 'REFUND_PENDING', 'WITHDRAWN', 'REFERRED_EXTERNALLY'],
  WAITING_FOR_CUSTOMER: ['UNDER_INVESTIGATION', 'RESOLUTION_PROPOSED', 'WITHDRAWN'],
  PENDING_THIRD_PARTY: ['UNDER_INVESTIGATION', 'RESOLUTION_PROPOSED', 'REFERRED_EXTERNALLY'],
  RESOLUTION_PROPOSED: ['REFUND_PENDING', 'RESOLVED', 'UNDER_INVESTIGATION', 'REFERRED_EXTERNALLY'],
  REFUND_PENDING: ['REFUND_COMPLETED', 'UNDER_INVESTIGATION', 'REFERRED_EXTERNALLY'],
  REFUND_COMPLETED: ['RESOLVED', 'CLOSED'],
  RESOLVED: ['CLOSED', 'REOPENED', 'REFERRED_EXTERNALLY'],
  CLOSED: ['REOPENED', 'REFERRED_EXTERNALLY'],
  REOPENED: ['TRIAGED', 'UNDER_INVESTIGATION', 'RESOLUTION_PROPOSED'],
  WITHDRAWN: [],
  REFERRED_EXTERNALLY: ['UNDER_INVESTIGATION', 'RESOLVED', 'CLOSED'],
};

export function canTransitionCase(from: CaseStatus, to: CaseStatus): boolean {
  return TRANSITIONS[from]?.includes(to) ?? false;
}
