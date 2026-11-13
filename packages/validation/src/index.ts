/**
 * Mirrors platform-navdhya/app/Modules/Pandit/Contracts/GotraList.php::CANONICAL
 */
export const CANONICAL_GOTRAS = [
  'KASHYAPA',
  'BHARADWAJA',
  'VASISHTHA',
  'VISHWAMITRA',
  'GAUTAMA',
  'JAMADAGNI',
  'ATRI',
  'AGASTYA',
  'ANGIRASA',
  'HARITA',
  'KOUNDINYA',
  'SANDILYA',
  'VATSAYANA',
  'GARGA',
  'MUDGALA',
  'SHUNAKA',
  'PARASHARA',
  'UPAMANYU',
  'KAUSHIKA',
  'OTHER',
] as const;

export type CanonicalGotra = (typeof CANONICAL_GOTRAS)[number];

export const CANONICAL_NAKSHATRAS = [
  'ASHWINI',
  'BHARANI',
  'KRITTIKA',
  'ROHINI',
  'MRIGASHIRA',
  'ARDRA',
  'PUNARVASU',
  'PUSHYA',
  'ASHLESHA',
  'MAGHA',
  'PURVA_PHALGUNI',
  'UTTARA_PHALGUNI',
  'HASTA',
  'CHITRA',
  'SWATI',
  'VISHAKHA',
  'ANURADHA',
  'JYESHTHA',
  'MULA',
  'PURVA_ASHADHA',
  'UTTARA_ASHADHA',
  'SHRAVANA',
  'DHANISHTA',
  'SHATABHISHA',
  'PURVA_BHADRAPADA',
  'UTTARA_BHADRAPADA',
  'REVATI',
] as const;

export type CanonicalNakshatra = (typeof CANONICAL_NAKSHATRAS)[number];

export const CANONICAL_RASHIS = [
  'MESHA',
  'VRISHABHA',
  'MITHUNA',
  'KARKATAKA',
  'SIMHA',
  'KANYA',
  'TULA',
  'VRISHCHIKA',
  'DHANU',
  'MAKARA',
  'KUMBHA',
  'MEENA',
] as const;

export type CanonicalRashi = (typeof CANONICAL_RASHIS)[number];

export const SERVICE_VENUES = ['home', 'temple', 'online'] as const;
export type ServiceVenue = (typeof SERVICE_VENUES)[number];

export function normalizeCanonicalGotra(value: string): CanonicalGotra | null {
  const upper = value.trim().toUpperCase().replace(/\s+/g, '_');
  return (CANONICAL_GOTRAS as readonly string[]).includes(upper) ? (upper as CanonicalGotra) : null;
}

export function normalizeCanonicalNakshatra(value: string): CanonicalNakshatra | null {
  const upper = value.trim().toUpperCase().replace(/\s+/g, '_');
  return (CANONICAL_NAKSHATRAS as readonly string[]).includes(upper) ? (upper as CanonicalNakshatra) : null;
}

export function isValidServiceVenue(value: string): value is ServiceVenue {
  return (SERVICE_VENUES as readonly string[]).includes(value.trim().toLowerCase());
}

export function normalizeIndianPhone(raw: string): string | null {
  const digits = raw.replace(/[\s\-()]/g, '');
  const match = digits.match(/^(?:\+91|91|0)?([6-9]\d{9})$/);
  return match ? `+91${match[1]}` : null;
}

export function isValidIndianPhone(raw: string): boolean {
  return normalizeIndianPhone(raw) !== null;
}

export function isValidIfsc(raw: string): boolean {
  return /^[A-Z]{4}0[A-Z0-9]{6}$/.test(raw.trim().toUpperCase());
}

export function isValidPinCode(raw: string): boolean {
  return /^[1-9]\d{5}$/.test(raw.trim());
}

export function isValidPan(raw: string): boolean {
  return /^[A-Z]{5}\d{4}[A-Z]$/.test(raw.trim().toUpperCase());
}

export interface RitualParticipantInput {
  service_venue: string;
  gotra?: string | null;
  nakshatra?: string | null;
}

export function validateRitualParticipantInput(input: RitualParticipantInput): string[] {
  const errors: string[] = [];
  if (!isValidServiceVenue(input.service_venue)) {
    errors.push('service_venue');
  }
  if (input.gotra && input.gotra.trim() !== '' && normalizeCanonicalGotra(input.gotra) === null) {
    errors.push('gotra');
  }
  if (input.nakshatra && input.nakshatra.trim() !== '' && normalizeCanonicalNakshatra(input.nakshatra) === null) {
    errors.push('nakshatra');
  }
  return errors;
}

export * from './constants/jyotish';


