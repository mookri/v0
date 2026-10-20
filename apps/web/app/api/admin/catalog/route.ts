import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/catalog — platform admin catalog item listing (W3).
 *
 * Proxies Laravel GET /api/v1/catalog/items (`scope:catalog:read` for the
 * list; `capability:catalog:item:manage` gates every mutation). Tenant-scoped
 * through ResolveTenantContext (header).
 */
export async function GET(request: Request) {
  const requestId = newRequestId();
  const { searchParams } = new URL(request.url);
  const limit = searchParams.get('limit') ?? '50';
  const status = searchParams.get('status') ?? '';

  if (!laravelEnabled()) {
    return Response.json(ok({ data: [], meta: { fallback: true, limit: Number(limit) } }, requestId), {
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

  const query: Record<string, string> = { limit };
  if (status) query.status = status;

  try {
    const { data, meta } = await laravelGet(
      token,
      '/catalog/items',
      query,
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch catalog items', requestId);
  }
}
