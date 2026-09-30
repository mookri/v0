import { Quantity } from './resource';

export type StockLocationType = 'WAREHOUSE' | 'STORE' | 'PARTNER' | 'HUB' | 'TRANSIT' | 'SUPPLIER';

export interface StockLocation {
  id: string;
  name: string;
  type: StockLocationType;
  geographyReference?: string;
  status: 'ACTIVE' | 'INACTIVE';
}

export interface StockPosition {
  itemId: string;
  locationId: string;
  onHand: number;
  reserved: number;
  allocated: number;
  version: number;
}

export function availableOf(position: Pick<StockPosition, 'onHand' | 'reserved' | 'allocated'>): number {
  return position.onHand - position.reserved - position.allocated;
}

export type MovementType =
  | 'RECEIPT' | 'TRANSFER_IN' | 'TRANSFER_OUT' | 'CONSUMPTION'
  | 'ADJUSTMENT' | 'RETURN' | 'DAMAGE' | 'RESERVATION' | 'RELEASE' | 'ALLOCATION';

export interface InventoryMovement {
  id: string;
  itemId: string;
  locationId: string;
  type: MovementType;
  quantity: Quantity;
  referenceType?: string;
  referenceId?: string;
  occurredAt: string;
}

export function projectBalance(movements: InventoryMovement[]): number {
  let balance = 0;
  for (const m of movements) {
    switch (m.type) {
      case 'RECEIPT':
      case 'TRANSFER_IN':
      case 'RETURN':
        balance += m.quantity.amount;
        break;
      case 'TRANSFER_OUT':
      case 'CONSUMPTION':
      case 'DAMAGE':
        balance -= m.quantity.amount;
        break;
      case 'ADJUSTMENT':
        balance += m.quantity.amount;
        break;
      case 'RESERVATION':
      case 'RELEASE':
      case 'ALLOCATION':
        break;
    }
  }
  return balance;
}
