import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/roles — the roles console's read (W1a).
 *
 * Proxies Laravel GET /api/v1/platform/roles, which is tenant-scoped: the
 * controller demands `tenant_id` in the query (`RoleController::scopeToTenant`
 * answers 404 `not_found` for an unknown/inactive tenant), while the shared
 * client already places the same tenant on `X-Tenant-Id` for the header-only
 * middleware pair. Both must name a real tenant — `tenant_id: 'all'` (what
 * this route used to send) never resolves to a row, so the panel could only
 * ever fail.
 *
 * `CUSTOMER_BACKEND=laravel` without `CUSTOMER_TENANT_ID` is a
 * misconfiguration, not a rate limit or an auth failure, so it answers 503
 * `DEPENDENCY_UNAVAILABLE` with a message naming the variable instead of
 * pretending the call succeeded.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ data: [], meta: { fallback: true } }, requestId), {
      status: 200,
    });
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
      '/platform/roles',
      { tenant_id: tenantId },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch platform roles', requestId);
  }
}
