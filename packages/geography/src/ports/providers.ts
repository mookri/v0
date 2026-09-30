import { Address, NormalizedAddress, LocationReference } from '@navdhya/geography/src/domain/location';

export interface GeocodingProvider {
  geocode(address: NormalizedAddress): Promise<{ latitude: number; longitude: number; precision: string } | null>;
}

export interface ServiceabilityGateway {
  check(input: { serviceId: string; regionIds: string[]; postalCode?: string; nowIso: string }): Promise<{ result: string }>;
}

export interface RegionRepository {
  getRegion(id: string): Promise<{ id: string } | null>;
}
