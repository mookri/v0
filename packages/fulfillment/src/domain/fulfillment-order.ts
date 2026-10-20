export type FulfillmentStatus =
  | 'PENDING' | 'READY' | 'ASSIGNMENT_PENDING' | 'ASSIGNED' | 'SCHEDULED'
  | 'IN_PROGRESS' | 'COMPLETED' | 'FAILED' | 'CANCELLED';

export interface FulfillmentOrder {
  id: string;
  bookingId: string;
  status: FulfillmentStatus;
  serviceDate: string;
  timezone: string;
  recipeVersion: number;
  version: number;
}

const TRANSITIONS: Record<FulfillmentStatus, FulfillmentStatus[]> = {
  PENDING: ['READY', 'CANCELLED', 'FAILED'],
  READY: ['ASSIGNMENT_PENDING', 'CANCELLED', 'FAILED'],
  ASSIGNMENT_PENDING: ['ASSIGNED', 'CANCELLED', 'FAILED'],
  ASSIGNED: ['SCHEDULED', 'ASSIGNMENT_PENDING', 'CANCELLED', 'FAILED'],
  SCHEDULED: ['IN_PROGRESS', 'ASSIGNED', 'CANCELLED', 'FAILED'],
  IN_PROGRESS: ['COMPLETED', 'FAILED', 'CANCELLED'],
  COMPLETED: [],
  FAILED: [],
  CANCELLED: [],
};

export function canTransitionFulfillment(from: FulfillmentStatus, to: FulfillmentStatus): boolean {
  return TRANSITIONS[from]?.includes(to) ?? false;
}
