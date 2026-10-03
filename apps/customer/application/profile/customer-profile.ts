// Profile view model matching the BFF /api/profile response
export interface CustomerProfileViewModel {
  id: string;
  name: string;
  email: string;
  phone?: string;
  city?: string;
  country?: string;
  timezone?: string;
  languages?: string[];
  mfaEnabled?: boolean;
  enrolledAt?: string;
}

export function toCustomerProfile(data: Record<string, unknown>): CustomerProfileViewModel {
  return {
    id: String(data.id ?? ''),
    name: String(data.name ?? ''),
    email: String(data.email ?? ''),
    phone: data.phone ? String(data.phone) : undefined,
    city: data.city ? String(data.city) : undefined,
    country: data.country ? String(data.country) : undefined,
    timezone: data.timezone ? String(data.timezone) : undefined,
    languages: Array.isArray(data.languages) ? data.languages.map(String) : undefined,
    mfaEnabled: data.mfaEnabled ? Boolean(data.mfaEnabled) : undefined,
    enrolledAt: data.mfaEnrolledAt ? String(data.mfaEnrolledAt) : undefined,
  };
}

export interface ProfileUpdateInput {
  name?: string;
  email?: string;
  phone?: string;
  city?: string;
  country?: string;
  timezone?: string;
  languages?: string[];
}

export type ProfileUpdateResult =
  | { ok: true; input: ProfileUpdateInput }
  | { ok: false; reason: 'VALIDATION_ERROR'; fieldErrors: Record<string, string> };

export function validateProfileUpdate(input: ProfileUpdateInput): ProfileUpdateResult {
  const fieldErrors: Record<string, string> = {};
  if (input.email !== undefined && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(input.email)) {
    fieldErrors.email = 'Please enter a valid email address';
  }
  if (input.name !== undefined && input.name.trim().length < 2) {
    fieldErrors.name = 'Please enter your name';
  }
  if (input.phone !== undefined && input.phone.replace(/\D/g, '').length < 10) {
    fieldErrors.phone = 'Please enter a valid phone number';
  }
  if (Object.keys(fieldErrors).length > 0) return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  return { ok: true, input };
}

// Deactivate/Reactivate types
export interface DeactivateInput {
  challengeId: string;
  code: string;
}

export interface ReactivateInput {
  challengeId: string;
  code: string;
}

export type DeactivateResult =
  | { ok: true }
  | { ok: false; reason: 'VALIDATION_ERROR' | 'UNAUTHENTICATED' | 'MFA_REQUIRED'; message: string };

export type ReactivateResult =
  | { ok: true }
  | { ok: false; reason: 'VALIDATION_ERROR' | 'UNAUTHENTICATED'; message: string };