export interface Coordinate {
  latitude: number;
  longitude: number;
}

export function isValidCoordinate(c: Coordinate): boolean {
  return (
    Number.isFinite(c.latitude) && Number.isFinite(c.longitude) &&
    c.latitude >= -90 && c.latitude <= 90 && c.longitude >= -180 && c.longitude <= 180
  );
}

export interface Address {
  line1?: string;
  line2?: string;
  locality?: string;
  city?: string;
  district?: string;
  state?: string;
  postalCode?: string;
  countryCode: string;
}

export interface NormalizedAddress {
  countryCode: string;
  administrativeArea?: string;
  district?: string;
  city?: string;
  locality?: string;
  postalCode?: string;
  lines: string[];
  coordinate?: Coordinate;
}

export type LocationType = 'ADDRESS' | 'PLACE' | 'COORDINATE' | 'POSTAL_CODE' | 'REGION';

export interface LocationReference {
  id: string;
  type: LocationType;
  version: number;
}

export function normalizeAddress(input: Address): NormalizedAddress {
  const trim = (v?: string): string | undefined => {
    const t = v?.trim().replace(/\s+/g, ' ');
    return t ? t : undefined;
  };
  const lines = [input.line1, input.line2].map(trim).filter((l): l is string => !!l);
  return {
    countryCode: (input.countryCode || 'IN').toUpperCase(),
    administrativeArea: trim(input.state),
    district: trim(input.district),
    city: trim(input.city),
    locality: trim(input.locality),
    postalCode: trim(input.postalCode)?.replace(/\s+/g, ''),
    lines,
  };
}
