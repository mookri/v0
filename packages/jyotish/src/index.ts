export interface GeoLocation {
  latitude: number;
  longitude: number;
  elevationMeters?: number;
  timezone: string;
}

export interface PanchangLimb {
  number: number;
  name: string;
  transits_at?: string | null;
  deity?: string;
  ruler?: string;
}

export interface PanchangResponse {
  date: string;
  location: GeoLocation;
  limbs: {
    tithi: PanchangLimb & { paksha: 'SHUKLA' | 'KRISHNA' };
    vara: PanchangLimb;
    nakshatra: PanchangLimb & { pada: number };
    yoga: PanchangLimb;
    karana: PanchangLimb;
  };
  solar_horizon: {
    sunrise: string;
    sunset: string;
    solar_noon: string;
    day_length_seconds: number;
  };
  inauspicious_windows: {
    rahu_kaal: { start: string; end: string };
    yamaghanta: { start: string; end: string };
    gulika_kaal: { start: string; end: string };
  };
  day_choghadiya: Array<{
    period: number;
    name: string;
    nature: 'AMRIT' | 'SHUBH' | 'LABH' | 'CHAR' | 'ROG' | 'KAAL' | 'UDVEG';
    is_auspicious: boolean;
    start: string;
    end: string;
  }>;
  observances: Array<{
    code: string;
    name: string;
    description?: string;
    priority: number;
  }>;
}

export interface PlanetaryPosition {
  graha: string;
  longitude: number;
  speed: number;
  rashi: number;
  rashi_name: string;
  degree_in_rashi: number;
  nakshatra: number;
  nakshatra_name: string;
  pada: number;
  is_retrograde: boolean;
  is_combust: boolean;
  dignity: string;
}

export interface HouseCusp {
  house: number;
  cusp_longitude: number;
  rashi: number;
  degree_in_rashi: number;
}

export interface KundliChart {
  chart_id?: string;
  ayanamsa: string;
  house_system: string;
  ascendant: PlanetaryPosition;
  planets: Record<string, PlanetaryPosition>;
  houses: HouseCusp[];
  shodasha_vargas?: Record<string, Record<string, number>>;
}

export interface DashaPeriod {
  level: number;
  lord: string;
  start_date: string;
  end_date: string;
  sub_periods?: DashaPeriod[];
}

export interface KootaScore {
  name: string;
  points_obtained: number;
  max_points: number;
  description: string;
  is_dosha: boolean;
}

export interface KundliMatchingResult {
  total_score: number;
  max_score: 36;
  kootas: Record<string, KootaScore>;
  nadi_dosha: boolean;
  bhakoot_dosha: boolean;
  manglik_analysis: {
    bride_manglik: boolean;
    groom_manglik: boolean;
    cancellation: boolean;
    cancellation_reason?: string;
  };
}

