'use client';

/**
 * Firebase phone-login flow (S50 kit): one single-use intent, one SMS
 * round-trip, one exchange. Mirrors the challenge-store pattern in
 * lib/auth/auth-client.ts — short-lived routing state lives in memory
 * only; a page refresh restarts the flow.
 *
 * Sequence (the backend stays authoritative throughout):
 *   1. POST /api/auth/firebase/intent {phone} → intentId (600s TTL)
 *   2. signInWithPhoneNumber(auth, phone, verifier) → SMS → confirm(otp)
 *   3. user.getIdToken() → POST /api/auth/firebase/exchange
 *      {intentId, idToken, totpCode?} → HttpOnly session cookie
 *
 * firebase/* values load lazily so this module's load graph stays SDK
 * free (component tests import it transitively).
 */

import type { ConfirmationResult, User } from 'firebase/auth';
import { authPost, AuthRequestError } from '@/lib/auth/auth-client';
import {
  clearFirebaseVerifier,
  firebaseAuth,
  firebaseConfigProblem,
  firebaseVerifier,
} from '@/lib/firebase/client';
import { normalizePhoneE164 } from '@/lib/firebase/phone';

// Pure helpers re-exported for screens (kept SDK-free in phone.ts so
// unit tests never load firebase/auth).
export { friendlyFirebaseError } from '@/lib/firebase/phone';

interface FirebaseFlow {
  intentId: string;
  phone: string;
  confirmation: ConfirmationResult;
  /** Set once the SMS code is confirmed — the TOTP retry reuses it. */
  user: User | null;
}

let flow: FirebaseFlow | null = null;

export function firebaseFlowActive(): boolean {
  return flow !== null;
}

export function clearFirebaseFlow(): void {
  flow = null;
  clearFirebaseVerifier();
}

/** Step 1 — record the intent, then dispatch the SMS (phone screen). */
export async function beginFirebaseLogin(rawPhone: string): Promise<void> {
  const problem = firebaseConfigProblem();
  if (problem) throw new AuthRequestError('UNKNOWN', problem, 0);

  const phone = normalizePhoneE164(rawPhone);

  // Intent first: if the backend rejects (throttle/validation) no SMS
  // is spent — the send fires below, at signInWithPhoneNumber.
  const res = await authPost<{ data?: { intentId?: string } }>(
    '/api/auth/firebase/intent',
    { phone },
  );
  const intentId = res.data.data?.intentId;
  if (!intentId) throw new AuthRequestError('UNKNOWN', 'Failed to start verification.', 0);

  const { signInWithPhoneNumber } = await import('firebase/auth');
  const confirmation = await signInWithPhoneNumber(
    await firebaseAuth(),
    phone,
    await firebaseVerifier(),
  );
  flow = { intentId, phone, confirmation, user: null };
}

/**
 * Resend — a fresh intent supersedes the old one (the superseded row
 * expires unused; the TTL is measured from creation).
 */
export async function resendFirebaseLogin(rawPhone: string): Promise<void> {
  await beginFirebaseLogin(rawPhone);
}

/**
 * Step 2 — confirm the SMS code, then exchange the ID token. After the
 * first successful confirm() the stored user is reused, so the MFA
 * retry only re-runs the exchange.
 */
export async function completeFirebaseLogin(otp: string, totpCode?: string): Promise<void> {
  const current = flow;
  if (!current) {
    throw new AuthRequestError('UNAUTHENTICATED', 'Session expired. Please request a new code.', 401);
  }

  if (!current.user) {
    current.user = (await current.confirmation.confirm(otp)).user;
  }

  const idToken = await current.user.getIdToken();
  await authPost('/api/auth/firebase/exchange', {
    intentId: current.intentId,
    idToken,
    ...(totpCode ? { totpCode } : {}),
  });

  clearFirebaseFlow(); // success — the HttpOnly cookie carries the session
}
