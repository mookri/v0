import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { postContext } from '@/server/route-guard';
import { revokeOthersSchema } from '@/application/state-schemas';
import { THROTTLE } from '@/application/auth-throttle';

/**
 * POST /api/sessions/revoke-others — end every other session.
 *
 * Contract §6: naturally idempotent (repeating it has the same effect),
 * so no Idempotency-Key is required. Bodyless command — accepted empty,
 * unknown keys are stripped by the schema and never forwarded (§5-style
 * explicitness). AAL1 (cookie session) per §7; backend enforces the
 * actual revocation (I18).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await postContext(req, requestId, {
    schema: revokeOthersSchema,
    allowEmptyBody: true,
    throttle: { purpose: 'revoke-others', limit: THROTTLE.loginIp.limit, windowSeconds: THROTTLE.loginIp.windowSeconds },
  });
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/identity/sessions:revoke-others',
      { tenant_id: laravelTenant() },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Revoke sessions unavailable.', requestId);
  }
}
