export type LifecycleState =
  | 'VISITOR' | 'IDENTIFIED' | 'REGISTERED' | 'PROFILE_STARTED'
  | 'FIRST_BOOKING' | 'ACTIVE_CUSTOMER' | 'REPEAT_CUSTOMER'
  | 'INACTIVE' | 'REACTIVATION';

export interface LifecycleInputs {
  registered: boolean;
  profileStarted: boolean;
  completedBookings: number;
  lastActivityAt?: string;
  inactivityDays: number;
  nowIso: string;
}

export function resolveLifecycle(input: LifecycleInputs): { state: LifecycleState; policyVersion: string; evaluatedAt: string } {
  const daysSince = input.lastActivityAt
    ? (Date.parse(input.nowIso) - Date.parse(input.lastActivityAt)) / 86_400_000
    : Number.POSITIVE_INFINITY;
  let state: LifecycleState = 'VISITOR';
  if (input.registered) state = 'REGISTERED';
  if (input.profileStarted) state = 'PROFILE_STARTED';
  if (input.completedBookings >= 1) state = 'FIRST_BOOKING';
  if (input.completedBookings >= 1 && daysSince <= input.inactivityDays) state = 'ACTIVE_CUSTOMER';
  if (input.completedBookings >= 2 && daysSince <= input.inactivityDays) state = 'REPEAT_CUSTOMER';
  if (daysSince > input.inactivityDays && input.completedBookings >= 1) state = 'INACTIVE';
  return { state, policyVersion: 'lifecycle-v1', evaluatedAt: input.nowIso };
}

export interface CustomerSegment {
  id: string;
  name: string;
  version: string;
  status: 'DRAFT' | 'ACTIVE' | 'RETIRED';
  purpose: 'ANALYTICS' | 'RECOMMENDATION' | 'COMMUNICATION' | 'OPERATIONS';
}

export interface SegmentMembership {
  customerId: string;
  segmentId: string;
  assignedAt: string;
  expiresAt?: string;
  source: 'RULE' | 'MODEL' | 'IMPORT';
}
