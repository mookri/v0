import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * POST /api/admin/managers/{id}/approve — W3 capability surfacing.
 *
 * Proxies Laravel POST /api/v1/onboarding/review/{id}:approve
 * (`capability:onboarding:review:approve` + `stepup:2`). The backend returns
 * 403 `step_up_required` when no fresh MFA proof exists; the console's
 * StepUpModal handles that challenge and retries.
 */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    return Response.json(ok({ data: { id, decision: 'approved', status: 'approved' }, meta: { fallback: true } }, requestId), {
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
      `/onboarding/review/${encodeURIComponent(id)}:approve`,
      {},
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to approve application', requestId);
  }
}
