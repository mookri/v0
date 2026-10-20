export type DisputeStatus = 'OPEN' | 'EVIDENCE_COLLECTION' | 'UNDER_REVIEW' | 'DECISION_PENDING' | 'DECIDED' | 'IMPLEMENTING' | 'RESOLVED' | 'ESCALATED' | 'WITHDRAWN' | 'REJECTED';

export interface Dispute {
  id: string;
  caseId: string;
  status: DisputeStatus;
  reasonCode: string;
  decidedBy?: string;
  decidedAt?: string;
}

const DISPUTE_TRANSITIONS: Record<DisputeStatus, DisputeStatus[]> = {
  OPEN: ['EVIDENCE_COLLECTION', 'WITHDRAWN'],
  EVIDENCE_COLLECTION: ['UNDER_REVIEW', 'WITHDRAWN'],
  UNDER_REVIEW: ['DECISION_PENDING', 'ESCALATED', 'WITHDRAWN'],
  DECISION_PENDING: ['DECIDED', 'ESCALATED', 'WITHDRAWN'],
  DECIDED: ['IMPLEMENTING', 'REJECTED'],
  IMPLEMENTING: ['RESOLVED'],
  RESOLVED: [],
  ESCALATED: ['UNDER_REVIEW', 'DECIDED'],
  WITHDRAWN: [],
  REJECTED: [],
};

export function canTransitionDispute(from: DisputeStatus, to: DisputeStatus): boolean {
  return DISPUTE_TRANSITIONS[from]?.includes(to) ?? false;
}
