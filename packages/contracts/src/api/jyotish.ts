/**
 * Canonical Jyotish API Contracts & DTOs (T-JY00-14).
 * Mirrors backend calculation requests and responses.
 */

import {
  CanonicalAyanamsa,
  CanonicalHouseSystem,
  CanonicalVarga,
  CanonicalDashaSystem,
} from '@navdhya/validation';

export interface JyotishCoordinates {
  longitude: number;
  latitude: number;
  distance: number;
  speed_longitude: number;
  speed_latitude: number;
  speed_distance: number;
}

export interface JyotishEphemerisSnapshotDto {
  jd_tt: number;
  jd_ut1: number;
  grahas: Record<string, JyotishCoordinates>;
}

export interface JyotishCalculationRequestDto {
  birth_datetime_utc: string;
  latitude: number;
  longitude: number;
  elevation_m?: number;
  ayanamsa?: CanonicalAyanamsa;
  house_system?: CanonicalHouseSystem;
  vargas?: CanonicalVarga[];
  dasha_system?: CanonicalDashaSystem;
}

export interface JyotishChartResponseDto {
  chart_id: string;
  ayanamsa: CanonicalAyanamsa;
  house_system: CanonicalHouseSystem;
  lagna_degrees: number;
  planetary_positions: Record<string, {
    rashi: number;
    degrees_in_rashi: number;
    total_degrees: number;
    nakshatra: string;
    pada: number;
    is_retrograde: boolean;
  }>;
  houses: Array<{
    house_number: number;
    rashi: number;
    cusp_degrees: number;
  }>;
  varga_charts?: Partial<Record<CanonicalVarga, unknown>>;
}

export interface JyotishResultEnvelopeDto<T = unknown> {
  data: T;
  meta: {
    request_id: string;
    schema: string;
    calculated_at: string;
    calculation_profile?: {
      profile_code: string;
      profile_hash: string;
      ayanamsa_code: string;
      ephemeris_version: string;
    };
  };
}

