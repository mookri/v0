import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { mfaPostContext } from '@/server/mfa-guard';
import { mfaConfirmSchema } from '@/application/auth-schemas';

/**
 * POST /api/mfa/confirm — confirm enrollment with the enroll-secret proof.
 *
 * Contract §5: **AAL1 + valid enroll-secret proof** (the `code` here is
 * the proof against the pending enrollment, validated by Laravel).
 *
 * Explicit payload (`code` only); no `{...body}` spread (§5).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await mfaPostContext(req, requestId, mfaConfirmSchema);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/identity/mfa/confirm',
      { tenant_id: laravelTenant(), code: ctx.data.code },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'MFA confirm unavailable.', requestId);
  }
}
