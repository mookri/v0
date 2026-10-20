import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/users — the users console's read (W1a).
 *
 * Proxies Laravel GET /api/v1/platform/users, which lists the **memberships of
 * one tenant** (`UserAdminController::index`) rather than a global user table.
 * Two conditions must both hold: the `tenant_id` query the controller validates,
 * and the `X-Tenant-Id` header `CheckCapability` needs (`platform:user:view`).
 * Sending `tenant_id: 'all'` satisfied neither — an "all tenants" view is not
 * representable on this API (plan D11/G11), so the route now names the
 * configured console tenant.
 *
 * 503 `DEPENDENCY_UNAVAILABLE` when the tenant variable is missing: a
 * misconfigured backend must not masquerade as an empty page.
 *
 * The `page`/`limit` params are carried for envelope compatibility with the
 * existing dashboard readers; this upstream is not paginated, so `total`
 * reflects what came back.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();
  const { searchParams } = new URL(request.url);
  const page = searchParams.get('page') ?? '1';
  const limit = searchParams.get('limit') ?? '50';

  if (!laravelEnabled()) {
    return Response.json(ok({ data: [], meta: { page: 1, limit: 50, total: 0, fallback: true } }, requestId), {
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
      '/platform/users',
      { tenant_id: tenantId, page, limit },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(
      ok(
        {
          data,
          meta: {
            ...meta,
            page: Number(page),
            limit: Number(limit),
            total: Array.isArray(data) ? data.length : 0,
          },
        },
        requestId,
      ),
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch platform users', requestId);
  }
}
