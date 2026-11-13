import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * POST /api/admin/managers/{id}/reject — W3 capability surfacing.
 *
 * Proxies Laravel POST /api/v1/onboarding/review/{id}:reject
 * (`capability:onboarding:review:reject` + `stepup:2`). Body carries
 * `reason_code`, `note`, `second_reviewer`. The backend returns 403
 * `step_up_required` when no fresh MFA proof exists.
 */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  const body = (await request.json().catch(() => ({}))) as {
    reason_code?: string;
    note?: string | null;
    second_reviewer?: string;
  };

  const reasonCode = typeof body.reason_code === 'string' ? body.reason_code.trim() : '';
  const secondReviewer = typeof body.second_reviewer === 'string' ? body.second_reviewer.trim() : '';

  if (!reasonCode || !secondReviewer) {
    return Response.json(
      err('VALIDATION_ERROR', 'reason_code and second_reviewer countersignature are required to reject an application.', requestId),
      { status: 422 },
    );
  }

  if (!laravelEnabled()) {
    return Response.json(
      ok(
        {
          data: {
            id,
            decision: 'rejected',
            status: 'rejected',
            reason_code: reasonCode,
            second_reviewer: secondReviewer,
          },
          meta: { fallback: true },
        },
        requestId,
      ),
      { status: 200 },
    );
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
      `/onboarding/review/${encodeURIComponent(id)}:reject`,
      {
        reason_code: reasonCode,
        note: body.note ?? null,
        second_reviewer: secondReviewer,
      },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to reject application', requestId);
  }
}
