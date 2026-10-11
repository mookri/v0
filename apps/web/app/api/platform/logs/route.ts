import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/logs — super admin audit logs.
 * Requires super admin session with platform-wide access.
 * Proxies to Laravel GET /api/v1/platform/logs.
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

  try {
    const { data, meta } = await laravelGet(
      token,
      '/platform/logs',
      { tenant_id: 'all', page, limit },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta: { ...meta, page: Number(page), limit: Number(limit) } }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch audit logs', requestId);
  }
}