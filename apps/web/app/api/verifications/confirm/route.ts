import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { postContext } from '@/server/route-guard';
import { verificationConfirmSchema } from '@/application/state-schemas';
import { THROTTLE } from '@/application/auth-throttle';

/**
 * POST /api/verifications/confirm — confirm a verification code.
 *
 * - schema-validated `id` is interpolated into the backend path
 *   (charset excludes `/` and `:` — no path smuggling)
 * - §3: per-IP attempt throttle mirrors the backend's wrong-code lock
 *   (backend counter stays authoritative, I8/I9)
 * - forwarded keys are exactly `{code}`
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await postContext(req, requestId, {
    schema: verificationConfirmSchema,
    throttle: { purpose: 'verification-confirm', limit: THROTTLE.mfaIp.limit, windowSeconds: THROTTLE.mfaIp.windowSeconds },
  });
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      `/customers/verifications/${ctx.data.id}:confirm`,
      { token: ctx.data.token },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Verification confirmation failed.', requestId);
  }
}
