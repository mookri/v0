import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * POST /api/admin/managers/{id}/request-info — W3 capability surfacing.
 *
 * Proxies Laravel POST /api/v1/onboarding/review/{id}:request-info
 * (`capability:onboarding:review:view`, no step-up). The BFF forwards the
 * caller's session; the backend's `WebMutations::negotiate` answers JSON
 * for an API-shaped request (no Referer) and a redirect + flash for a
 * browser form post. This route is the API shape.
 */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    return Response.json(ok({ data: { id, decision: 'request_info', status: 'under_review' }, meta: { fallback: true } }, requestId), {
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
      `/onboarding/review/${encodeURIComponent(id)}:request-info`,
      {},
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to request more information', requestId);
  }
}
