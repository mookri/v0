'use client';

/**
 * Shared auth client — `auth/p0-contract.md` (transport side).
 *
 * One place for: E.164 normalization, timeouts, guarded JSON parsing,
 * and the canonical `proof` field. Screens must not hand-roll fetch.
 */

/** Network budget for auth calls. Backend budget is separate (fast/normal). */
const AUTH_TIMEOUT_MS = 10_000;

export type AuthErrorCode =
  | 'VALIDATION_ERROR'
  | 'RATE_LIMITED'
  | 'CONFLICT'
  | 'UNAUTHENTICATED'
  | 'NETWORK'
  | 'UNKNOWN';

export class AuthRequestError extends Error {
  constructor(
    readonly code: AuthErrorCode,
    message: string,
    readonly status: number,
    readonly retryAfter?: number,
  ) {
    super(message);
    this.name = 'AuthRequestError';
  }
}

export interface AuthResult<T> {
  status: number;
  data: T;
}

/**
 * Normalize to E.164 (contract §1: challenge is identity-bound to a
 * *normalized* identity — "9876543210" and "+919876543210" must not
 * become two different identities).
 */
export function toE164(dialCode: string, nationalNumber: string): string {
  const digits = nationalNumber.replace(/\D/g, '');
  const dial = dialCode.replace(/\D/g, ''); // e.g. "+91" -> "91"
  if (digits.startsWith(dial) && digits.length > dial.length) {
    return `+${digits}`;
  }
  return `+${dial}${digits}`;
}

/**
 * POST with timeout + guarded parsing.
 *
 * Never lets a 429/502 HTML page throw a raw SyntaxError at a screen;
 * every failure becomes an AuthRequestError with a displayable message.
 */
export async function authPost<T>(
  path: string,
  body: unknown,
  options: { idempotencyKey?: string } = {},
): Promise<AuthResult<T>> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), AUTH_TIMEOUT_MS);

  try {
    const headers: Record<string, string> = { 'Content-Type': 'application/json' };
    if (options.idempotencyKey) headers['Idempotency-Key'] = options.idempotencyKey;

    const res = await fetch(path, {
      method: 'POST',
      headers,
      body: JSON.stringify(body),
      signal: controller.signal,
      credentials: 'same-origin',
    });

    const payload = await safeJson(res);

    if (!res.ok) {
      const envelope = payload as { error?: { code?: string; message?: string } } | null;
      const code = normalizeErrorCode(envelope?.error?.code, res.status);
      const retryAfterHeader = res.headers.get('retry-after');
      throw new AuthRequestError(
        code,
        envelope?.error?.message ?? defaultMessageFor(res.status),
        res.status,
        retryAfterHeader ? Number(retryAfterHeader) : undefined,
      );
    }

    return { status: res.status, data: payload as T };
  } catch (e) {
    if (e instanceof AuthRequestError) throw e;
    if (e instanceof DOMException && e.name === 'AbortError') {
      throw new AuthRequestError('NETWORK', 'Request timed out. Please try again.', 0);
    }
    throw new AuthRequestError('NETWORK', 'Network error. Please check your connection.', 0);
  } finally {
    clearTimeout(timer);
  }
}

async function safeJson(res: Response): Promise<unknown> {
  try {
    return await res.json();
  } catch {
    return null; // Non-JSON body (proxy error page, empty 204, …)
  }
}

function normalizeErrorCode(backendCode: string | undefined, status: number): AuthErrorCode {
  if (backendCode === 'RATE_LIMITED') return 'RATE_LIMITED';
  if (backendCode === 'VALIDATION_ERROR') return 'VALIDATION_ERROR';
  if (backendCode === 'CONFLICT') return 'CONFLICT';
  if (backendCode === 'UNAUTHENTICATED') return 'UNAUTHENTICATED';
  if (status === 429) return 'RATE_LIMITED';
  if (status === 422) return 'VALIDATION_ERROR';
  if (status === 409) return 'CONFLICT';
  if (status === 401) return 'UNAUTHENTICATED';
  return 'UNKNOWN';
}

function defaultMessageFor(status: number): string {
  if (status === 429) return 'Too many attempts. Please wait and try again.';
  if (status === 401) return 'Invalid credentials.';
  if (status === 400) return 'Invalid request.';
  if (status >= 500) return 'Service unavailable. Please try again.';
  return 'Something went wrong.';
}

// ─── Challenge store ─────────────────────────────────────────────────────────
// Contract §1: the challengeId is short-lived routing state, not a
// credential — but it is still kept in memory rather than
// sessionStorage (XSS-readable, lost on refresh in a tab). A refresh
// deliberately restarts the flow; that matches the backend's "one
// active challenge" rule.

let activeChallenge: { id: string; purpose: 'login' | 'signup'; expiresAt: number } | null = null;

export function setChallenge(id: string, purpose: 'login' | 'signup', expiresIn: number): void {
  activeChallenge = { id, purpose, expiresAt: Date.now() + expiresIn * 1000 };
}

export function getChallenge(): { id: string; purpose: 'login' | 'signup' } | null {
  if (!activeChallenge) return null;
  if (Date.now() >= activeChallenge.expiresAt) {
    activeChallenge = null;
    return null;
  }
  return { id: activeChallenge.id, purpose: activeChallenge.purpose };
}

export function clearChallenge(): void {
  activeChallenge = null;
}

/** Contract §2: `proof` is canonical; `code` is a migration alias. */
export function redeemBody(challengeId: string, proof: string): { challengeId: string; proof: string } {
  return { challengeId, proof };
}

/** Deterministic-ish key for idempotent OTP/signup posts (contract §4). */
export function newIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }
  return `idm_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 10)}`;
}
