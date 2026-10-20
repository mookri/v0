import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/vendors — platform admin vendor directory (W1b).
 *
 * Proxies Laravel GET /api/v1/vendor/admin/vendors (cursor-paginated,
 * `capability:vendor:profile:view`). Tenant-scoped through ResolveTenantContext
 * (header), so the configured console tenant is carried on `X-Tenant-Id`.
 * Filters (`status`, `verification_status`, `search`, `limit`) pass through.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();
  const { searchParams } = new URL(request.url);
  const limit = searchParams.get('limit') ?? '50';
  const status = searchParams.get('status') ?? '';
  const verificationStatus = searchParams.get('verification_status') ?? '';
  const search = searchParams.get('search') ?? '';

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
  if (verificationStatus) query.verification_status = verificationStatus;
  if (search) query.search = search;

  try {
    const { data, meta } = await laravelGet(
      token,
      '/vendor/admin/vendors',
      query,
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch vendors', requestId);
  }
}
