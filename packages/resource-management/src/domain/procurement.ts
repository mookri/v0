import { Quantity } from '../domain/resource';

export type PurchaseStatus = 'DRAFT' | 'REQUESTED' | 'APPROVED' | 'ORDERED' | 'CONFIRMED' | 'PARTIALLY_RECEIVED' | 'RECEIVED' | 'CANCELLED' | 'REJECTED';

export interface PurchaseRequest {
  id: string;
  itemId: string;
  requestedQuantity: Quantity;
  neededBy: string;
  destinationLocationId: string;
  sourceType: string;
  sourceId: string;
  status: PurchaseStatus;
}

export interface Supplier {
  id: string;
  name: string;
  status: 'ACTIVE' | 'INACTIVE' | 'BLOCKED';
}

export interface GoodsReceiptLine {
  itemId: string;
  orderedQuantity: Quantity;
  receivedQuantity: Quantity;
  acceptedQuantity: Quantity;
  rejectedQuantity: Quantity;
}

export interface GoodsReceipt {
  id: string;
  purchaseOrderId: string;
  receivedAt: string;
  locationId: string;
  lines: GoodsReceiptLine[];
  status: 'DRAFT' | 'POSTED' | 'REVERSED';
}

export interface ReplenishmentPolicy {
  itemId: string;
  locationId: string;
  reorderPoint: Quantity;
  reorderQuantity: Quantity;
  safetyStock?: Quantity;
  policyVersion: string;
}

export function belowReorderPoint(available: number, policy: { reorderPoint: Quantity }): boolean {
  return available <= policy.reorderPoint.amount;
}

export type ResourceExceptionType =
  | 'SHORTAGE' | 'DAMAGED_STOCK' | 'RECEIPT_DELAY' | 'ALLOCATION_CONFLICT' | 'QUALITY_HOLD' | 'TRANSFER_DELAY';

export interface ResourceException {
  id: string;
  type: ResourceExceptionType;
  itemId?: string;
  fulfillmentId?: string;
  status: 'OPEN' | 'INVESTIGATING' | 'RESOLVED' | 'CLOSED';
  openedAt: string;
}
