import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { mfaPostContext } from '@/server/mfa-guard';
import { mfaRevokeSchema } from '@/application/auth-schemas';

/**
 * POST /api/mfa/revoke — disable MFA.
 *
 * Contract §5: **AAL2** — a *live* TOTP must accompany the request;
 * Laravel rejects stale/replayed codes atomically (gate: "MFA replay
 * reject"). A plain authenticated cookie is never sufficient (§5).
 *
 * Explicit payload (`code` only); no `{...body}` spread (§5).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await mfaPostContext(req, requestId, mfaRevokeSchema);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/identity/mfa/revoke',
      { tenant_id: laravelTenant(), code: ctx.data.code },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'MFA revoke unavailable.', requestId);
  }
}
