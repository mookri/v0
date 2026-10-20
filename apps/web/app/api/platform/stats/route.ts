import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/stats — single aggregate for the SuperAdmin overview (W2).
 *
 * Replaces the five dangling `/platform/stats/{users,revenue,system,cities,security}`
 * calls the overview used to make (and swallow). One round trip, one tenant,
 * real counts from `platform/ops/stats`. The overview renders whatever the
 * backend can honestly report and leaves the rest as "—" instead of faking it.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(
      ok(
        {
          data: { users: { total: 0, active: 0 }, roles: { total: 0 }, audit: { total: 0, denied_today: 0 }, tenant: null },
          meta: { fallback: true },
        },
        requestId,
      ),
      { status: 200 },
    );
  }

  const { token } = extractSessionToken(request);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Super admin session required', requestId), {
      status: 401,
    });
  }

  const tenantId = laravelTenant();
  if (!tenantId) {
    return Response.json(
      err('DEPENDENCY_UNAVAILABLE', 'No console tenant configured: set CUSTOMER_TENANT_ID.', requestId),
      { status: 503 },
    );
  }

  try {
    const { data, meta } = await laravelGet(
      token,
      '/platform/stats',
      { tenant_id: tenantId },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch platform stats', requestId);
  }
}
