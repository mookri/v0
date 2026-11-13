import {
  normalizeCanonicalGotra,
  normalizeCanonicalNakshatra,
} from '@navdhya/validation';

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
  gotra?: string;
  nakshatra?: string;
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
    gotra: data.gotra ? String(data.gotra) : undefined,
    nakshatra: data.nakshatra ? String(data.nakshatra) : undefined,
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
  gotra?: string;
  nakshatra?: string;
}

export type ProfileUpdateResult =
  | { ok: true; input: ProfileUpdateInput }
  | { ok: false; reason: 'VALIDATION_ERROR'; fieldErrors: Record<string, string> };

export function validateProfileUpdate(input: ProfileUpdateInput): ProfileUpdateResult {
  const fieldErrors: Record<string, string> = {};
  const normalized: ProfileUpdateInput = { ...input };

  if (input.email !== undefined && input.email.trim() !== '' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(input.email)) {
    fieldErrors.email = 'Please enter a valid email address';
  }
  if (input.name !== undefined && input.name.trim().length < 2) {
    fieldErrors.name = 'Please enter your name';
  }
  if (input.phone !== undefined && input.phone.trim() !== '' && input.phone.replace(/\D/g, '').length < 10) {
    fieldErrors.phone = 'Please enter a valid phone number';
  }
  if (input.gotra !== undefined && input.gotra.trim() !== '') {
    const canonicalGotra = normalizeCanonicalGotra(input.gotra);
    if (!canonicalGotra) {
      fieldErrors.gotra = 'Please select a canonical Gotra from the list';
    } else {
      normalized.gotra = canonicalGotra;
    }
  }
  if (input.nakshatra !== undefined && input.nakshatra.trim() !== '') {
    const canonicalNakshatra = normalizeCanonicalNakshatra(input.nakshatra);
    if (!canonicalNakshatra) {
      fieldErrors.nakshatra = 'Please select a canonical Nakshatra from the list';
    } else {
      normalized.nakshatra = canonicalNakshatra;
    }
  }

  if (Object.keys(fieldErrors).length > 0) return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  return { ok: true, input: normalized };
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