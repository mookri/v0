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

// Closed key set - backend validates, BFF mirrors for UX.
// Must match CustomerSetting::KEYS in platform-navdhya exactly.
export const KNOWN_SETTINGS_KEYS = [
  'locale',
  'notifications_email',
  'notifications_sms',
  'marketing_opt_in',
  'timezone',
  'quiet_hours',
] as const;

export type KnownSettingsKey = (typeof KNOWN_SETTINGS_KEYS)[number];

export type CustomerSettings = {
  locale: string;
  notifications_email: boolean;
  notifications_sms: boolean;
  marketing_opt_in: boolean;
  timezone: string;
  quiet_hours: { start?: string; end?: string } | null;
};

// Typed defaults mirror CustomerSetting::DEFAULTS in platform-navdhya.
// They are the render baseline for partial/empty settings maps: booleans
// must stay booleans (a blank string would render a text input and then
// fail PATCH validation), and quiet_hours must stay null or an object.
export const DEFAULT_SETTINGS: CustomerSettings = {
  locale: 'en',
  notifications_email: true,
  notifications_sms: true,
  marketing_opt_in: false,
  timezone: 'Asia/Kolkata',
  quiet_hours: null,
};

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

    // Type validation per key
    switch (key) {
      case 'locale':
      case 'timezone':
        if (typeof value !== 'string' || value.length === 0) {
          fieldErrors[key] = 'Must be a non-empty string';
        }
        break;
      case 'notifications_email':
      case 'notifications_sms':
      case 'marketing_opt_in':
        if (typeof value !== 'boolean') {
          fieldErrors[key] = 'Must be a boolean';
        }
        break;
      case 'quiet_hours':
        if (value !== null && value !== undefined) {
          if (typeof value !== 'object' || value === null || Array.isArray(value)) {
            fieldErrors[key] = 'Must be an object or null';
          } else {
            const qh = value as Record<string, unknown>;
            if ('start' in qh && typeof qh.start !== 'string') fieldErrors[key] = 'start must be HH:MM';
            if ('end' in qh && typeof qh.end !== 'string') fieldErrors[key] = 'end must be HH:MM';
            if ('start' in qh && !/^([01]\d|2[0-3]):([0-5]\d)$/.test(qh.start as string)) fieldErrors[key] = 'start must be HH:MM';
            if ('end' in qh && !/^([01]\d|2[0-3]):([0-5]\d)$/.test(qh.end as string)) fieldErrors[key] = 'end must be HH:MM';
          }
        }
        break;
    }
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  }

  return { ok: true, values: input };
}