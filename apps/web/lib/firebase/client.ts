'use client';

/**
 * Firebase client SDK wiring for phone login (S50 kit path).
 *
 * The browser SDK owns the SMS round-trip and OTP verification; the
 * resulting ID token is handed to the Laravel BFF exchange, which stays
 * authoritative for identity, eligibility, MFA, and the session. Only
 * the client entry points are used (`firebase/app` init +
 * `firebase/auth` phone) — the server-side admin SDK never ships to a
 * browser (tests/architecture/communication-boundaries).
 *
 * The SDK loads lazily (dynamic import), so module graphs that merely
 * import this file — component tests, unrelated routes — never
 * execute firebase/* at load time.
 *
 * Config comes from NEXT_PUBLIC_FIREBASE_* (see .env.example); the
 * whole path stays dormant until NEXT_PUBLIC_FIREBASE_LOGIN=true.
 */

import type { FirebaseApp } from 'firebase/app';
import type { Auth, RecaptchaVerifier } from 'firebase/auth';

const firebaseConfig = {
  apiKey: process.env.NEXT_PUBLIC_FIREBASE_API_KEY,
  authDomain: process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
  projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID,
  appId: process.env.NEXT_PUBLIC_FIREBASE_APP_ID,
};

/** Cutover switch: the legacy OTP path stays the default until enabled. */
export function firebaseLoginEnabled(): boolean {
  return process.env.NEXT_PUBLIC_FIREBASE_LOGIN === 'true';
}

/** Honest failure instead of a silent fallback when the flag is on. */
export function firebaseConfigProblem(): string | null {
  if (!firebaseConfig.apiKey || !firebaseConfig.projectId || !firebaseConfig.appId) {
    return 'Phone sign-in is not configured. Please try again later.';
  }
  return null;
}

let app: FirebaseApp | null = null;

export async function firebaseAuth(): Promise<Auth> {
  const { getAuth } = await import('firebase/auth');
  if (!app) {
    const { getApp, getApps, initializeApp } = await import('firebase/app');
    app = getApps().length > 0 ? getApp() : initializeApp(firebaseConfig);
  }
  return getAuth(app);
}

let verifier: RecaptchaVerifier | null = null;

/** Invisible reCAPTCHA widget — created once, reused across resends. */
export async function firebaseVerifier(): Promise<RecaptchaVerifier> {
  if (!verifier) {
    const { RecaptchaVerifier: RecaptchaVerifierClass } = await import('firebase/auth');
    // Optional explicit site key; otherwise the project's console
    // default applies.
    const siteKey = process.env.NEXT_PUBLIC_FIREBASE_RECAPTCHA_KEY;
    verifier = new RecaptchaVerifierClass(await firebaseAuth(), 'firebase-recaptcha-container', {
      size: 'invisible',
      ...(siteKey ? { siteKey } : {}),
    });
  }
  return verifier;
}

export function clearFirebaseVerifier(): void {
  try {
    verifier?.clear();
  } catch {
    // Widget already detached (navigation) — nothing left to clear.
  }
  verifier = null;
}
