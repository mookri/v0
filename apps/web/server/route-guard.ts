import type { z } from 'zod';
import { err } from '@/application/api-envelope';
import { laravelEnabled } from '@/adapters/backend/laravel-client';
import { authRateLimiter, ipKey } from '@/application/auth-throttle';
import { bodyWithinCap } from '@/application/auth-schemas';
import { wiredActor, type WiredActor } from '@/server/wired-actor';

/**
 * Generic route guard for stateful session/state APIs — P1 sibling of
 * `server/mfa-guard.ts`, following `auth/p0-contract.md` §7/§8.
 *
 * Order: backend enabled → 1 MB cap → JSON parse (400 on malformed,
 * per §8) → zod command schema (422) → optional per-IP throttle
 * (429 + Retry-After) → cookie-first actor (401 per the §7 matrix).
 *
 * Like mfa-guard, this only enforces the AAL1 floor (cookie session).
 * Security state stays backend-authoritative (I18). The `api/consistency-
 * contracts` branch is where the two guards get unified behind one
 * implementation if duplication is judged worth removing.
 */

export type RequestContext =
  | { ok: true; actor: WiredActor }
  | { ok: false; response: Response };

export function rejectRequest(
  requestId: string,
  code: Parameters<typeof err>[0],
  message: string,
  status: number,
  headers?: Record<string, string>,
): Response {
  return Response.json(err(code, message, requestId), { status, headers });
}

function reject(
  requestId: string,
  code: Parameters<typeof err>[0],
  message: string,
  status: number,
  headers?: Record<string, string>,
): { ok: false; response: Response } {
  return { ok: false, response: rejectRequest(requestId, code, message, status, headers) };
}

/** GET paths: enabled check + cookie-first actor. */
export async function getRequestContext(req: Request, requestId: string): Promise<RequestContext> {
  if (!laravelEnabled()) {
    return reject(requestId, 'UNAUTHENTICATED', 'Sign in to continue.', 401);
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return { ok: false, response: actor };
  return { ok: true, actor };
}

export interface PostContextOptions<S extends z.ZodTypeAny> {
  /** Command schema — the only keys that may ever be forwarded. */
  schema: S;
  /**
   * Accept an absent body as `{}` (bodyless commands like
   * "revoke other sessions"). Malformed JSON still → 400.
   */
  allowEmptyBody?: boolean;
  /** Optional per-IP abuse throttle (429 + Retry-After). */
  throttle?: { purpose: string; limit: number; windowSeconds: number };
}

export async function postContext<S extends z.ZodTypeAny>(
  req: Request,
  requestId: string,
  options: PostContextOptions<S>,
): Promise<{ ok: true; actor: WiredActor; data: z.infer<S> } | { ok: false; response: Response }> {
  if (!laravelEnabled()) {
    return reject(requestId, 'UNAUTHENTICATED', 'Sign in to continue.', 401);
  }

  if (!bodyWithinCap(req.headers.get('content-length'))) {
    return reject(requestId, 'VALIDATION_ERROR', 'Request body too large.', 413);
  }

  const text = await req.text();
  let raw: unknown;
  if (!text.trim() && options.allowEmptyBody) {
    raw = {};
  } else {
    try {
      raw = JSON.parse(text);
    } catch {
      return reject(requestId, 'VALIDATION_ERROR', 'Invalid request body.', 400);
    }
  }

  const parsed = options.schema.safeParse(raw);
  if (!parsed.success) {
    return reject(
      requestId,
      'VALIDATION_ERROR',
      parsed.error.issues[0]?.message ?? 'Invalid request.',
      422,
    );
  }

  if (options.throttle) {
    const decision = await authRateLimiter.consume(
      ipKey(options.throttle.purpose, req.headers),
      options.throttle.limit,
      options.throttle.windowSeconds,
    );
    if (!decision.allowed) {
      return reject(
        requestId,
        'RATE_LIMITED',
        'Too many attempts. Please wait and try again.',
        429,
        { 'Retry-After': String(decision.retryAfter) },
      );
    }
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return { ok: false, response: actor };

  return { ok: true, actor, data: parsed.data as z.infer<S> };
}
