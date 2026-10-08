import { newRequestId, ok } from '@/application/api-envelope';
import { laravelGet, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { mfaGetContext } from '@/server/mfa-guard';

/**
 * GET /api/mfa/status — read MFA enrollment state.
 *
 * Contract §5: **AAL1** (cookie session). Read-only; no body, no
 * spread. Cookie-first actor per §7 (legacy header only behind
 * LEGACY_HEADER_AUTH=1).
 */
export async function GET(req: Request) {
  const requestId = newRequestId();
  const ctx = await mfaGetContext(req, requestId);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelGet(
      ctx.actor.token,
      '/identity/mfa',
      { tenant_id: laravelTenant() ?? '' },
      {},
      { timeout: 'fast', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'MFA status unavailable.', requestId);
  }
}
