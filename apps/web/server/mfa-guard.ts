import type { z } from 'zod';
import { err } from '@/application/api-envelope';
import { laravelEnabled } from '@/adapters/backend/laravel-client';
import { authRateLimiter, ipKey, THROTTLE } from '@/application/auth-throttle';
import { bodyWithinCap } from '@/application/auth-schemas';
import { wiredActor, type WiredActor } from '@/server/wired-actor';

/**
 * Shared guard for `/api/mfa/*` — `auth/p0-contract.md` §5/§7/§8.
 *
 * Runs, in order:
 *   1. backend enabled            → 401 when not wired
 *   2. 1 MB body cap              → 413
 *   3. JSON parse                 → 400
 *   4. zod schema                 → 422 (explicit command, never a spread)
 *   5. per-IP attempt throttle    → 429 + Retry-After (mirror of the
 *                                   backend lock window; backend stays
 *                                   authoritative per I8/I9)
 *   6. cookie-first actor         → 401 per the §7 precedence matrix
 *                                   (no silent header fallback)
 *
 * Assurance notes (§5): every MFA operation requires at least AAL1 —
 * that floor is the `wiredActor` cookie check below. AAL2 proofs
 * (live TOTP / fresh step-up) travel in the request body and are
 * validated by Laravel; the BFF deliberately does not judge them (I18).
 */

export type MfaContext<T> =
  | { ok: true; actor: WiredActor; data: T }
  | { ok: false; response: Response };

function reject(requestId: string, code: Parameters<typeof err>[0], message: string, status: number, headers?: Record<string, string>): { ok: false; response: Response } {
  return { ok: false, response: Response.json(err(code, message, requestId), { status, headers }) };
}

export async function mfaPostContext<S extends z.ZodTypeAny>(
  req: Request,
  requestId: string,
  schema: S,
): Promise<MfaContext<z.infer<S>>> {
  if (!laravelEnabled()) {
    return reject(requestId, 'UNAUTHENTICATED', 'Sign in to continue.', 401);
  }

  if (!bodyWithinCap(req.headers.get('content-length'))) {
    return reject(requestId, 'VALIDATION_ERROR', 'Request body too large.', 413);
  }

  let raw: unknown;
  try {
    raw = await req.json();
  } catch {
    return reject(requestId, 'VALIDATION_ERROR', 'Invalid request body.', 400);
  }

  const parsed = schema.safeParse(raw);
  if (!parsed.success) {
    return reject(
      requestId,
      'VALIDATION_ERROR',
      parsed.error.issues[0]?.message ?? 'Invalid request.',
      422,
    );
  }

  const decision = await authRateLimiter.consume(
    ipKey('mfa', req.headers),
    THROTTLE.mfaIp.limit,
    THROTTLE.mfaIp.windowSeconds,
  );
  if (!decision.allowed) {
    return reject(requestId, 'RATE_LIMITED', 'Too many attempts. Please wait and try again.', 429, {
      'Retry-After': String(decision.retryAfter),
    });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) {
    return { ok: false, response: actor };
  }

  return { ok: true, actor, data: parsed.data as z.infer<S> };
}

/** GET variant (status read): no body, AAL1 floor via cookie actor. */
export async function mfaGetContext(
  req: Request,
  requestId: string,
): Promise<MfaContext<null>> {
  if (!laravelEnabled()) {
    return reject(requestId, 'UNAUTHENTICATED', 'Sign in to continue.', 401);
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) {
    return { ok: false, response: actor };
  }

  return { ok: true, actor, data: null };
}
