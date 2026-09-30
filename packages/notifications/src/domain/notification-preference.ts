import { NotificationCategory } from './notification';

export interface NotificationPreference {
  userId: string;
  category: NotificationCategory;
  channels: { email?: boolean; sms?: boolean; whatsapp?: boolean; push?: boolean; inApp?: boolean };
  locale?: string;
  timezone?: string;
  quietHours?: { start: string; end: string };
  marketingConsent?: boolean;
}

// Precedence: LEGAL/SECURITY > BUSINESS-CRITICAL > USER PREFERENCE > availability.
// Mandatory security/transactional traffic ignores opt-outs.
export function isChannelAllowed(
  pref: NotificationPreference,
  category: NotificationCategory,
  channel: keyof NonNullable<NotificationPreference['channels']>,
): boolean {
  if (category === 'security') return true;
  if (category === 'transactional') return pref.channels[channel] !== false;
  if (category === 'marketing' && !pref.marketingConsent) return false;
  return pref.channels[channel] === true;
}
