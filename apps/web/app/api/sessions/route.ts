import { newRequestId, ok } from '@/application/api-envelope';
import { laravelGet, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { getRequestContext } from '@/server/route-guard';

/**
 * GET /api/sessions — list active sessions.
 *
 * Contract §7: cookie-only actor (raw `Authorization` reads and the
 * LEGACY_HEADER_AUTH path both removed — header tokens are not
 * credentials). §8: X-Request-Id propagated to Laravel for log
 * correlation.
 */
export async function GET(req: Request) {
  const requestId = newRequestId();
  const ctx = await getRequestContext(req, requestId);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelGet(
      ctx.actor.token,
      '/identity/sessions',
      { tenant_id: laravelTenant() },
      {},
      { timeout: 'fast', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Sessions unavailable.', requestId);
  }
}
