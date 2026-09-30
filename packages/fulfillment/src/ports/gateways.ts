import { FulfillmentOrder, canTransitionFulfillment } from '@navdhya/fulfillment/src/domain/fulfillment-order';

export interface FulfillmentRepository {
  getById(id: string): Promise<FulfillmentOrder | null>;
  save(order: FulfillmentOrder): Promise<void>;
  updateIfVersion(order: FulfillmentOrder, expectedVersion: number): Promise<boolean>;
}

export interface SchedulingGateway {
  commit(input: { resourceId: string; workOrderId: string; startsAt: string; endsAt: string; timezone: string }): Promise<{ id: string } | { conflict: true }>;
  release(scheduleId: string): Promise<void>;
}

export interface AssignmentGateway {
  offer(input: { fulfillmentId: string; partnerId: string; expiresAt: string }): Promise<{ offerId: string }>;
  accept(offerId: string): Promise<boolean>;
}

export function transitionOrder(order: FulfillmentOrder, to: FulfillmentOrder['status']): FulfillmentOrder | null {
  if (!canTransitionFulfillment(order.status, to)) return null;
  return { ...order, status: to, version: order.version + 1 };
}
