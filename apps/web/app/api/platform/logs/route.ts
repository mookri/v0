import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/logs — the audit-log console read (W2).
 *
 * Proxies Laravel GET /api/v1/platform/audit/logs, which reads the
 * hash-chained audit rows for the configured console tenant
 * (`platform:audit:view`). The previous target (`/platform/logs`) never
 * existed, so this panel could only ever fall back to an empty page.
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
      '/platform/audit/logs',
      { tenant_id: tenantId, limit },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch audit logs', requestId);
  }
}
