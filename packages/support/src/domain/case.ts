export type CaseType =
  | 'QUESTION' | 'REQUEST' | 'COMPLAINT' | 'INCIDENT' | 'DISPUTE' | 'SAFETY'
  | 'PAYMENT_ISSUE' | 'BOOKING_ISSUE' | 'FULFILLMENT_ISSUE' | 'PARTNER_ISSUE' | 'TECHNICAL_ISSUE';

export type CaseStatus =
  | 'NEW' | 'TRIAGED' | 'OPEN' | 'PENDING_CUSTOMER' | 'PENDING_PARTNER' | 'PENDING_INTERNAL'
  | 'RESOLVED' | 'CLOSED' | 'REOPENED' | 'ESCALATED' | 'CANCELLED';

export type CasePriority = 'LOW' | 'NORMAL' | 'HIGH' | 'URGENT' | 'CRITICAL';
export type CaseVisibility = 'CUSTOMER' | 'PARTNER' | 'INTERNAL' | 'RESTRICTED';

export interface SupportCase {
  id: string;
  reference: string;
  type: CaseType;
  category: string;
  status: CaseStatus;
  priority: CasePriority;
  customerId: string;
  bookingId?: string;
  queueId?: string;
  agentId?: string;
  version: number;
  createdAt: string;
  resolvedAt?: string;
  closedAt?: string;
}

const TRANSITIONS: Record<CaseStatus, CaseStatus[]> = {
  NEW: ['TRIAGED', 'CANCELLED'],
  TRIAGED: ['OPEN', 'CANCELLED'],
  OPEN: ['PENDING_CUSTOMER', 'PENDING_PARTNER', 'PENDING_INTERNAL', 'RESOLVED', 'ESCALATED', 'CANCELLED'],
  PENDING_CUSTOMER: ['OPEN', 'RESOLVED', 'ESCALATED', 'CANCELLED'],
  PENDING_PARTNER: ['OPEN', 'RESOLVED', 'ESCALATED', 'CANCELLED'],
  PENDING_INTERNAL: ['OPEN', 'RESOLVED', 'ESCALATED', 'CANCELLED'],
  RESOLVED: ['CLOSED', 'REOPENED'],
  CLOSED: ['REOPENED'],
  REOPENED: ['OPEN'],
  ESCALATED: ['OPEN', 'RESOLVED', 'CANCELLED'],
  CANCELLED: [],
};

export function canTransitionCase(from: CaseStatus, to: CaseStatus): boolean {
  return TRANSITIONS[from]?.includes(to) ?? false;
}
