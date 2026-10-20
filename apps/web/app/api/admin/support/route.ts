import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/support — platform admin support queue (W1b slice 2).
 *
 * Re-pointed from `/platform/support` (which never existed) to the module
 * endpoint that does: `GET /api/v1/customers/support/staff/tickets`
 * (`capability:customers:support:view`). Tenant-scoped through
 * ResolveTenantContext (header).
 */
export async function GET(request: Request) {
  const requestId = newRequestId();
  const { searchParams } = new URL(request.url);
  const status = searchParams.get('status') ?? '';

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

  const tenantId = laravelTenant();
  if (!tenantId) {
    return Response.json(
      err('DEPENDENCY_UNAVAILABLE', 'No console tenant configured: set CUSTOMER_TENANT_ID.', requestId),
      { status: 503 },
    );
  }

  const query: Record<string, string> = {};
  if (status) query.status = status;

  try {
    const { data, meta } = await laravelGet(
      token,
      '/customers/support/staff/tickets',
      query,
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch support tickets', requestId);
  }
}
