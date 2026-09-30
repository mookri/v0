export type PreferenceKey =
  | 'LANGUAGE' | 'TIMEZONE' | 'DEFAULT_LOCATION'
  | 'PREFERRED_CONTACT_CHANNEL' | 'PREFERRED_BOOKING_TIME' | 'CONTENT_LANGUAGE';

export type PreferenceSource = 'EXPLICIT_CUSTOMER' | 'IMPORTED' | 'DEFAULT' | 'SYSTEM_DERIVED';

export interface Preference {
  key: PreferenceKey;
  value: string;
  source: PreferenceSource;
  updatedAt: string;
  version: number;
}

export interface InferredPreference {
  key: PreferenceKey;
  value: string;
  confidence: number;
  observedAt: string;
  expiresAt?: string;
}

// Explicit choice always wins; inference never overwrites it.
export function resolvePreference(explicit: Preference | null, inferred: InferredPreference | null, nowIso: string): string | null {
  if (explicit) return explicit.value;
  if (inferred && (!inferred.expiresAt || inferred.expiresAt > nowIso)) return inferred.value;
  return null;
}
