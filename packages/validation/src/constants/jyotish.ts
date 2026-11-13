/**
 * Canonical Jyotish Vocabularies (T-JY00-14).
 * Mirrored verbatim in platform-navdhya/app/Domain/Jyotish/Support/JyotishVocabularies.php.
 */

export const CANONICAL_AYANAMSA = [
  'LAHIRI',
  'RAMAN',
  'KRISHNAMURTI',
  'YUKTESHWAR',
  'TRUE_CHITRA',
  'FAGAN_BRADLEY',
  'SURYA_SIDDHANTA',
] as const;

export type CanonicalAyanamsa = (typeof CANONICAL_AYANAMSA)[number];

export const CANONICAL_HOUSE_SYSTEMS = [
  'WHOLE_SIGN',
  'PLACIDUS',
  'SRIPATI',
  'EQUAL_HOUSE',
  'KOCH',
  'CAMPANUS',
  'REGIOMONTANUS',
] as const;

export type CanonicalHouseSystem = (typeof CANONICAL_HOUSE_SYSTEMS)[number];

export const CANONICAL_VARGAS = [
  'D1',
  'D2',
  'D3',
  'D4',
  'D7',
  'D9',
  'D10',
  'D12',
  'D16',
  'D20',
  'D24',
  'D27',
  'D30',
  'D40',
  'D45',
  'D60',
] as const;

export type CanonicalVarga = (typeof CANONICAL_VARGAS)[number];

export const CANONICAL_DASHA_SYSTEMS = [
  'VIMSHOTTARI',
  'ASHTOTTARI',
  'YOGINI',
  'CHARA',
  'KALACHAKRA',
] as const;

export type CanonicalDashaSystem = (typeof CANONICAL_DASHA_SYSTEMS)[number];

