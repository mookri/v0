import { Quantity } from './resource';

export type ReservationStatus = 'HELD' | 'CONFIRMED' | 'RELEASED' | 'EXPIRED';

export interface InventoryReservation {
  id: string;
  itemId: string;
  locationId: string;
  quantity: Quantity;
  referenceType: string;
  referenceId: string;
  status: ReservationStatus;
  expiresAt?: string;
  version: number;
}

export function isReservationLive(reservation: InventoryReservation, nowIso: string): boolean {
  if (reservation.status !== 'HELD' && reservation.status !== 'CONFIRMED') return false;
  if (reservation.expiresAt && reservation.expiresAt <= nowIso) return false;
  return true;
}

export type AllocationStatus = 'ALLOCATED' | 'PICKED' | 'ISSUED' | 'CONSUMED' | 'RELEASED';

export interface InventoryAllocation {
  id: string;
  reservationId: string;
  itemId: string;
  locationId: string;
  batchId?: string;
  quantity: Quantity;
  status: AllocationStatus;
}

export type ConsumptionOutcome = 'CONSUMED' | 'RETURNED' | 'WASTED' | 'DAMAGED' | 'UNUSED';

export interface ResourceConsumption {
  id: string;
  fulfillmentId: string;
  itemId: string;
  locationId: string;
  planned: Quantity;
  actual: Quantity;
  outcome: ConsumptionOutcome;
  batchId?: string;
  consumedAt: string;
}

export function consumptionVariance(planned: Quantity, actual: Quantity): number | null {
  if (!planned || !actual || planned.unit !== actual.unit) return null;
  return actual.amount - planned.amount;
}
