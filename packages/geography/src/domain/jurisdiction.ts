export type JurisdictionType = 'TAX' | 'LEGAL' | 'REGULATORY' | 'SERVICE' | 'CUSTOM';
export type JurisdictionPurpose = 'TAX' | 'LEGAL' | 'SERVICEABILITY' | 'OPERATIONS' | 'MARKETING';

export interface Jurisdiction {
  id: string;
  type: JurisdictionType;
  code?: string;
  parentId?: string;
  status: string;
}

export interface JurisdictionResolution {
  purpose: JurisdictionPurpose;
  jurisdictionIds: string[];
  datasetVersion: string;
  resolvedAt: string;
}

export interface TimezoneContext {
  timezone: string;
  source: string;
  resolvedAt: string;
}

export interface GeographicContext {
  locationId: string;
  regionIds: string[];
  territoryIds: string[];
  zoneIds: string[];
  timezone?: string;
  jurisdictions: { purpose: JurisdictionPurpose; jurisdictionIds: string[] }[];
}
