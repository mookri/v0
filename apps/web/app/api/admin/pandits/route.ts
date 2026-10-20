import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/pandits — platform admin pandit directory (W1b).
 *
 * Proxies Laravel GET /api/v1/pandit/admin/profiles (cursor-paginated,
 * `capability:pandit:profile:view` inside the route). The module route is
 * tenant-scoped through ResolveTenantContext (header-only), so the BFF carries
 * the configured tenant on `X-Tenant-Id` and answers 503 when it is missing
 * rather than letting the backend reply `tenant_header_missing`.
 *
 * Filters (`status`, `verification_status`, `search`, `limit`) are passed
 * through verbatim; the backend validates each one.
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
      '/pandit/admin/profiles',
      query,
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch pandit profiles', requestId);
  }
}
