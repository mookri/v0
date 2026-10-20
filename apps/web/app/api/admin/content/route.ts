import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/content — platform admin content queue (W2).
 *
 * Re-pointed from `/platform/content` (never existed) to
 * `GET /api/v1/platform/ops/content` (`platform:ops:view`). Content is a
 * central (global) table, so no tenant filter is sent — only `limit`.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();
  const { searchParams } = new URL(request.url);
  const limit = searchParams.get('limit') ?? '50';

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

  try {
    const { data, meta } = await laravelGet(
      token,
      '/platform/ops/content',
      { limit },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch content queue', requestId);
  }
}
