import { newRequestId, ok } from '@/application/api-envelope';
import { laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { mfaPostContext } from '@/server/mfa-guard';
import { mfaRecoveryCodesSchema } from '@/application/auth-schemas';

/**
 * POST /api/mfa/recovery-codes — regenerate recovery codes.
 *
 * Contract §5: **AAL2** — live TOTP required (Laravel-authoritative);
 * regenerating recovery codes is a credential-adjacent mutation and a
 * normal session cookie must not suffice.
 *
 * Explicit payload (`code` only); no `{...body}` spread (§5).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await mfaPostContext(req, requestId, mfaRecoveryCodesSchema);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/identity/mfa/recovery-codes',
      { tenant_id: laravelTenant(), code: ctx.data.code },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'MFA recovery codes unavailable.', requestId);
  }
}
