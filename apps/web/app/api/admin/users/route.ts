import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/users — platform admin user listing (W1b).
 *
 * Proxies Laravel GET /api/v1/platform/users. The backend takes `tenant_id`
 * in the query (UserAdminController::index validates it), so `tenant_id: ''`
 * (what this route used to send) failed validation on every call. The
 * tenant now comes from the configured console tenant, and a missing one is
 * reported as 503 instead of a silent 422.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();
  const { searchParams } = new URL(request.url);
  const page = searchParams.get('page') ?? '1';
  const limit = searchParams.get('limit') ?? '20';

  if (!laravelEnabled()) {
    return Response.json(ok({ data: [], meta: { page: 1, limit: 20, total: 0, fallback: true } }, requestId), {
      status: 200,
    });
  }

  const { token } = extractSessionToken(request);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Admin session required', requestId), {
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
      '/platform/users',
      { tenant_id: tenantId, page, limit },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta: { ...meta, page: Number(page), limit: Number(limit) } }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch users', requestId);
  }
}
