import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { mfaPostContext } from '@/server/mfa-guard';
import { mfaEnrollSchema } from '@/application/auth-schemas';

/**
 * POST /api/mfa/enroll — start TOTP enrollment.
 *
 * Contract §5: **AAL2** required — a fresh step-up must already exist;
 * Laravel checks its freshness (BFF cannot judge assurance, I18).
 *
 * Explicit payload (`type` only); no `{...body}` spread (§5).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await mfaPostContext(req, requestId, mfaEnrollSchema);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/identity/mfa/enroll',
      { tenant_id: laravelTenant(), type: ctx.data.type },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'MFA enroll unavailable.', requestId);
  }
}
