export type SettingsOwner = 'user' | 'identity' | 'notification' | 'i18n';

export interface SettingsSection {
  id: string;
  title: string;
  owner: SettingsOwner;
  href: string;
}

export const customerSettingsSections: SettingsSection[] = [
  { id: 'profile', title: 'Profile', owner: 'user', href: '/profile' },
  { id: 'language', title: 'Language', owner: 'i18n', href: '/settings' },
  { id: 'notifications', title: 'Notifications', owner: 'notification', href: '/notifications' },
  { id: 'security', title: 'Security', owner: 'identity', href: '/settings' },
];

// Closed key set - the BFF mirrors `CustomerSetting::KEYS` (Laravel)
// one for one: flat keys, same names, so GET `values` and PATCH keys
// round-trip. The backend stays the authority (hostile-BFF posture);
// this list only rejects unknown keys early with a field-level 422.
export const KNOWN_SETTINGS_KEYS = [
  'locale',
  'notifications_email',
  'notifications_sms',
  'marketing_opt_in',
] as const;

export type KnownSettingsKey = (typeof KNOWN_SETTINGS_KEYS)[number];

export type SettingsValidationResult =
  | { ok: true; values: Record<string, unknown> }
  | { ok: false; reason: 'VALIDATION_ERROR'; fieldErrors: Record<string, string> };

export function validateSettingsUpdate(input: Record<string, unknown>): SettingsValidationResult {
  const fieldErrors: Record<string, string> = {};

  for (const [key, value] of Object.entries(input)) {
    if (!KNOWN_SETTINGS_KEYS.includes(key as KnownSettingsKey)) {
      fieldErrors[key] = 'Unknown setting key';
      continue;
    }

    // Type validation per key - mirrors the backend rules
    // (CustomerSettingController::rules: locale is a string of at most
    // 8 chars, everything else a boolean).
    switch (key) {
      case 'locale':
        if (typeof value !== 'string' || value.length === 0 || value.length > 8) {
          fieldErrors[key] = 'Must be a non-empty string of at most 8 characters';
        }
        break;
      case 'notifications_email':
      case 'notifications_sms':
      case 'marketing_opt_in':
        if (typeof value !== 'boolean') {
          fieldErrors[key] = 'Must be a boolean';
        }
        break;
    }
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  }

  return { ok: true, values: input };
}