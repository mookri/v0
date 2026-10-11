import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/api — super admin API management.
 * Requires super admin session with platform-wide access.
 * Proxies to Laravel GET /api/v1/platform/api.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ data: {}, meta: { fallback: true } }, requestId), {
      status: 200,
    });
  }

  const { token } = extractSessionToken(request);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Super admin session required', requestId), {
      status: 401,
    });
  }

  try {
    const { data, meta } = await laravelGet(
      token,
      '/platform/api',
      { tenant_id: 'all' },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch API management', requestId);
  }
}