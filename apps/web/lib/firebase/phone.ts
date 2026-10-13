/**
 * Pure helpers for the Firebase phone-login flow (S50 kit) — kept
 * SDK-free so they stay unit-testable without importing firebase/auth.
 */

/**
 * E.164 for the Firebase send. The *same* string goes to the intent,
 * so the backend's phone-match (intent ↔ id_token claim) holds
 * exactly. Bare 10-digit input assumes the house default (+91).
 */
export function normalizePhoneE164(raw: string): string {
  const trimmed = raw.trim();
  const digits = trimmed.replace(/\D/g, '');
  if (trimmed.startsWith('+')) return `+${digits}`;
  if (digits.length === 10) return `+91${digits}`;
  return `+${digits}`;
}

/**
 * Map Firebase SDK codes to honest copy. The SDK tells the client
 * directly (its own abuse limits, expired codes); the backend exchange
 * itself stays no-oracle — every credential-shaped failure is one 401.
 */
export function friendlyFirebaseError(e: unknown): string | null {
  const code = (e as { code?: string } | null | undefined)?.code ?? '';
  if (code === 'auth/invalid-verification-code') return 'Invalid code. Please try again.';
  if (code === 'auth/code-expired') return 'This code has expired. Request a new one.';
  if (code === 'auth/too-many-requests' || code === 'auth/quota-exceeded') {
    return 'Too many attempts. Please try again later.';
  }
  if (code === 'auth/captcha-check-failed') return 'Verification failed. Please try again.';
  if (code === 'auth/invalid-phone-number') return 'Enter a valid mobile number';
  if (code.startsWith('auth/')) return 'Verification failed. Please try again.';
  return null;
}
