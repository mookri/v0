export type ResolutionType =
  | 'INFORMATION_PROVIDED' | 'BOOKING_RESCHEDULED' | 'BOOKING_CANCELLED'
  | 'REFUND_INITIATED' | 'REASSIGNMENT_REQUESTED' | 'NO_ACTION_REQUIRED' | 'ESCALATED_OUTCOME';

export interface ActionReference {
  capability: 'booking' | 'payment' | 'fulfillment' | 'partner' | 'trust';
  operation: string;
  referenceId: string;
}

export interface CaseResolution {
  type: ResolutionType;
  summary: string;
  actionReferences?: ActionReference[];
  resolvedAt: string;
  resolvedBy: string;
  reasonCode: string;
}

export type ActionStatus = 'REQUESTED' | 'VALIDATED' | 'DISPATCHED' | 'ACKNOWLEDGED' | 'COMPLETED' | 'REJECTED' | 'FAILED' | 'CANCELLED';

export interface CapabilityAction {
  actionId: string;
  caseId: string;
  capability: ActionReference['capability'];
  operation: string;
  subjectId: string;
  requestedBy: string;
  idempotencyKey: string;
  status: ActionStatus;
}
