export type ProfileStatus = 'MINIMAL' | 'PARTIAL' | 'COMPLETE';
export type FieldSource = 'CUSTOMER' | 'SYSTEM' | 'IMPORT' | 'ADMIN';

export interface ProfileField<T> {
  value?: T;
  source: FieldSource;
  updatedAt: string;
}

export interface CustomerProfile {
  customerId: string;
  displayName?: ProfileField<string>;
  preferredLanguage?: ProfileField<string>;
  timezone?: ProfileField<string>;
  version: number;
  updatedAt: string;
}

export function validateDisplayName(name: string): boolean {
  const t = name.trim();
  return t.length >= 2 && t.length <= 80;
}

export function profileStatus(p: CustomerProfile, required: (keyof CustomerProfile)[]): ProfileStatus {
  const present = required.filter((k) => {
    const f = p[k] as ProfileField<unknown> | undefined;
    return f !== undefined && (f as ProfileField<unknown>).value !== undefined;
  }).length;
  if (present === 0) return 'MINIMAL';
  if (present < required.length) return 'PARTIAL';
  return 'COMPLETE';
}
