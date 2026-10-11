import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/content — platform admin content queue.
 * Requires admin session with capability to view content.
 * Proxies to Laravel GET /api/v1/platform/content.
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
    return Response.json(err('UNAUTHENTICATED', 'Admin session required', requestId), {
      status: 401,
    });
  }

  try {
    const { data, meta } = await laravelGet(
      token,
      '/platform/content',
      { tenant_id: '' },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch content queue', requestId);
  }
}