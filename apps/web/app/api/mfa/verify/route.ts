import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { mfaPostContext } from '@/server/mfa-guard';
import { mfaVerifySchema } from '@/application/auth-schemas';

/**
 * POST /api/mfa/verify — step-up primitive.
 *
 * Contract §5: **AAL1** required (cookie session); a successful verify
 * *produces* the step-up. §6: MFA completion is a session-rotation
 * trigger — rotation itself is authoritative in Laravel; the BFF
 * forwards the backend's response untouched.
 *
 * Payload to Laravel is explicit (`code` only): `{...body}` spreads are
 * forbidden — client-supplied keys must never reach the authority (§5).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await mfaPostContext(req, requestId, mfaVerifySchema);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/identity/mfa/verify',
      { tenant_id: laravelTenant(), code: ctx.data.code },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'MFA verify unavailable.', requestId);
  }
}
