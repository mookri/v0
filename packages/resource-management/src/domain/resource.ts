export type ResourceType = 'MATERIAL' | 'CONSUMABLE' | 'KIT' | 'EQUIPMENT' | 'ASSET' | 'VENUE' | 'VEHICLE' | 'CAPACITY';
export type UnitCategory = 'COUNT' | 'WEIGHT' | 'VOLUME' | 'LENGTH' | 'TIME';

export interface ResourceItem {
  id: string;
  sku: string;
  name: string;
  type: ResourceType;
  unit: string;
  unitCategory: UnitCategory;
  batchTracking: boolean;
  status: 'ACTIVE' | 'INACTIVE';
}

export interface Quantity {
  amount: number;
  unit: string;
}

export function sameUnit(a: Quantity, b: Quantity): boolean {
  return a.unit === b.unit;
}
