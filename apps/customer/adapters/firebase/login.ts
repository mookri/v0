'use client';

/**
 * Firebase phone-login flow (S50 kit) — customer app edition: one
 * single-use intent, one SMS round-trip, one exchange. This app has no
 * auth-client wrapper, so transport is the same bare fetch its legacy
 * login steps use.
 *
 * Sequence (the backend stays authoritative throughout):
 *   1. POST /api/auth/firebase/intent {phone} → intentId (600s TTL)
 *   2. signInWithPhoneNumber(auth, phone, verifier) → SMS → confirm(otp)
 *   3. user.getIdToken() → POST /api/auth/firebase/exchange
 *      {intentId, idToken, totpCode?} → HttpOnly session cookie
 *
 * firebase/* values load lazily so this module's load graph stays SDK
 * free.
 */

import type { ConfirmationResult, User } from 'firebase/auth';
import {
  clearFirebaseVerifier,
  firebaseAuth,
  firebaseConfigProblem,
  firebaseVerifier,
} from '@/adapters/firebase/client';
import { normalizePhoneE164 } from '@/adapters/firebase/phone';

// Pure helpers re-exported for the login screen (kept SDK-free in
// phone.ts so unit tests never load firebase/auth).
export { friendlyFirebaseError } from '@/adapters/firebase/phone';

interface FirebaseFlow {
  intentId: string;
  phone: string;
  confirmation: ConfirmationResult;
  /** Set once the SMS code is confirmed — the TOTP retry reuses it. */
  user: User | null;
}

let flow: FirebaseFlow | null = null;

export class FirebaseLoginError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly code?: string,
  ) {
    super(message);
    this.name = 'FirebaseLoginError';
  }
}

async function postJson<T>(path: string, body: unknown): Promise<T> {
  const res = await fetch(path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
    credentials: 'same-origin',
  });
  const payload = (await res.json().catch(() => null)) as
    | { data?: T; error?: { code?: string; message?: string } }
    | null;
  if (!res.ok) {
    throw new FirebaseLoginError(
      payload?.error?.message ?? 'Request failed.',
      res.status,
      payload?.error?.code,
    );
  }
  return (payload?.data ?? null) as T;
}

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
  if (problem) throw new FirebaseLoginError(problem, 0);

  const phone = normalizePhoneE164(rawPhone);

  // Intent first: if the backend rejects (throttle/validation) no SMS
  // is spent — the send fires below, at signInWithPhoneNumber.
  const { intentId } = await postJson<{ intentId?: string }>('/api/auth/firebase/intent', { phone });
  if (!intentId) throw new FirebaseLoginError('Failed to start verification.', 0);

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
    throw new FirebaseLoginError('Session expired. Please request a new code.', 401);
  }

  if (!current.user) {
    current.user = (await current.confirmation.confirm(otp)).user;
  }

  const idToken = await current.user.getIdToken();
  await postJson<unknown>('/api/auth/firebase/exchange', {
    intentId: current.intentId,
    idToken,
    ...(totpCode ? { totpCode } : {}),
  });

  clearFirebaseFlow(); // success — the HttpOnly cookie carries the session
}
