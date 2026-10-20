import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * POST /api/admin/catalog/{id}/publish — W3 capability surfacing.
 *
 * Proxies Laravel POST /api/v1/catalog/items/{id}:publish
 * (`capability:catalog:item:manage`).
 */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    return Response.json(ok({ data: { id, status: 'ACTIVE' }, meta: { fallback: true } }, requestId), {
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
    const { data, meta } = await laravelPost(
      token,
      `/catalog/items/${encodeURIComponent(id)}:publish`,
      {},
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to publish catalog item', requestId);
  }
}
