export interface Territory {
  id: string;
  name: string;
  type: 'SALES' | 'OPERATIONS' | 'FULFILLMENT' | 'MARKETING' | 'CUSTOM';
  status: 'ACTIVE' | 'INACTIVE';
  regionIds: string[];
}

export type ZoneType = 'PRICING' | 'SERVICE' | 'FULFILLMENT' | 'OPERATIONS' | 'CUSTOM';

export interface Zone {
  id: string;
  name: string;
  type: ZoneType;
  parentId?: string;
  status: 'ACTIVE' | 'INACTIVE';
}

export interface ZoneMembership {
  zoneId: string;
  regionId?: string;
  postalCode?: string;
}
