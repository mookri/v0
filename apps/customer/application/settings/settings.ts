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
