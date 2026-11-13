/**
 * Navdhya Sacred Ember — Locked Brand Palette Tokens (AGENTS.md)
 *
 * Strictly zero green anywhere in Trust, Support, Safety, Feedback, or Incident UI.
 */
export const SACRED_EMBER_TOKENS = {
  warmIvory: '#FFF7ED',
  aubergineInk: '#2C2033',
  navdhyaFlame: '#F16A24',
  softSand: '#EFE3D3',
  deepEmber: '#B74717',
  deepText: '#2A2421',
} as const;

export type ConfidenceTier =
  | 'NEW_PROVIDER'
  | 'LOW_CONFIDENCE'
  | 'ESTABLISHED'
  | 'HIGH_CONFIDENCE';

export type IncidentSeverity = 'SEV_0' | 'SEV_1' | 'SEV_2' | 'SEV_3' | 'SEV_4';
export type EligibilityGateMode = 'FULL_GATES' | 'PARTIAL_GATES' | 'FAILED';

