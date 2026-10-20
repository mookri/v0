export interface CustomerVerification {
  id: string;
  type: 'phone' | 'email';
  contact: string;
  status: 'pending' | 'verified' | 'expired';
  createdAt: string;
  confirmedAt?: string;
}

export interface StartVerificationInput {
  type: 'phone' | 'email';
  contact: string;
}

export type StartVerificationResult =
  | { ok: true; challengeId: string; expiresIn: number }
  | { ok: false; reason: 'VALIDATION_ERROR' | 'CONFLICT'; fieldErrors?: Record<string, string>; message?: string };

export function validateStartVerification(input: StartVerificationInput): StartVerificationResult {
  const fieldErrors: Record<string, string> = {};

  if (!input.type || !['phone', 'email'].includes(input.type)) {
    fieldErrors.type = 'Type must be phone or email';
  }

  if (!input.contact || input.contact.trim().length === 0) {
    fieldErrors.contact = 'Contact is required';
  } else if (input.type === 'phone' && input.contact.replace(/\D/g, '').length < 10) {
    fieldErrors.contact = 'Enter a valid phone number';
  } else if (input.type === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(input.contact)) {
    fieldErrors.contact = 'Enter a valid email address';
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  }

  return { ok: true, challengeId: '', expiresIn: 0 };
}

export interface ConfirmVerificationInput {
  id: string;
  code: string;
}

export type ConfirmVerificationResult =
  | { ok: true }
  | { ok: false; reason: 'VALIDATION_ERROR' | 'UNAUTHENTICATED' | 'EXPIRED'; fieldErrors?: Record<string, string>; message?: string };

export function validateConfirmVerification(input: ConfirmVerificationInput): ConfirmVerificationResult {
  const fieldErrors: Record<string, string> = {};

  if (!input.id || input.id.trim().length === 0) {
    fieldErrors.id = 'Verification ID is required';
  }

  if (!input.code || input.code.length !== 6) {
    fieldErrors.code = 'Enter the complete 6-digit code';
  }

  if (Object.keys(fieldErrors).length > 0) {
    return { ok: false, reason: 'VALIDATION_ERROR', fieldErrors };
  }

  return { ok: true };
}