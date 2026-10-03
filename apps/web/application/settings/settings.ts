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

// Closed key set - backend validates, BFF mirrors for UX
export const KNOWN_SETTINGS_KEYS = [
  'locale',
  'timezone',
  'currency',
  'email_notifications',
  'push_notifications',
  'sms_notifications',
  'marketing_emails',
  'security_alerts',
  'two_factor_enabled',
  'theme',
] as const;

export type KnownSettingsKey = (typeof KNOWN_SETTINGS_KEYS)[number];

export interface SettingsValidationResult {
  ok: true;
  values: Record<string, unknown>;
} | {
  ok: false;
  reason: 'VALIDATION_ERROR';
  fieldErrors: Record<string, string>;
}

export function validateSettingsUpdate(input: Record<string, unknown>): SettingsValidationResult {
  const fieldErrors: Record<string, string> = {};

  for (const [key, value] of Object.entries(input)) {
    if (!KNOWN_SETTINGS_KEYS.includes(key as KnownSettingsKey)) {
      fieldErrors[key] = 'Unknown setting key';
      continue;
    }

    // Type validation per key
    switch (key) {
      case 'locale':
      case 'timezone':
      case 'currency':
      case 'theme':
        if (typeof value !== 'string' || value.length === 0) {
          fieldErrors[key] = 'Must be a non-empty string';
        }
        break;
      case 'email_notifications':
      case 'push_notifications':
      case 'sms_notifications':
      case 'marketing_emails':
      case 'security_alerts':
      case 'two_factor_enabled':
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