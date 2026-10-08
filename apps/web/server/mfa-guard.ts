import type { z } from 'zod';
import { THROTTLE } from '@/application/auth-throttle';
import { postContext, getRequestContext } from '@/server/route-guard';
import type { WiredActor } from '@/server/wired-actor';

/**
 * MFA-specific wrapper over the shared route guard —
 * `auth/p0-contract.md` §5/§7/§8.
 *
 * The pipeline (enabled → 1 MB cap → 400 on malformed JSON → 422 on
 * schema → 429 + Retry-After on the 5/15min attempt throttle →
 * cookie-first actor) now lives once in `server/route-guard.ts`; this
 * wrapper only pins the MFA throttle policy and keeps the AAL notes:
 *
 * Assurance (§5): every MFA operation requires at least AAL1 — that
 * floor is the cookie-actor check inside the shared guard. AAL2 proofs
 * (live TOTP / fresh step-up) travel in the request body and are
 * validated by Laravel; the BFF deliberately does not judge them (I18).
 */

export type MfaContext<T> =
  | { ok: true; actor: WiredActor; data: T }
  | { ok: false; response: Response };

export async function mfaPostContext<S extends z.ZodTypeAny>(
  req: Request,
  requestId: string,
  schema: S,
): Promise<MfaContext<z.infer<S>>> {
  return postContext(req, requestId, {
    schema,
    throttle: {
      purpose: 'mfa',
      limit: THROTTLE.mfaIp.limit,
      windowSeconds: THROTTLE.mfaIp.windowSeconds,
    },
  });
}

/** GET variant (status read): no body, AAL1 floor via cookie actor. */
export async function mfaGetContext(
  req: Request,
  requestId: string,
): Promise<MfaContext<null>> {
  const ctx = await getRequestContext(req, requestId);
  if (!ctx.ok) return ctx;
  return { ok: true, actor: ctx.actor, data: null };
}
