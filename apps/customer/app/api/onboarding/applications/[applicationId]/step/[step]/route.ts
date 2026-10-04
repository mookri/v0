import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface StepResponse {
  data: { id: string; object: string; step: string; saved: boolean };
  meta: { request_id?: string };
}

export async function PUT(req: Request, { params }: { params: Promise<{ applicationId: string; step: string }> }) {
  const requestId = newRequestId();
  const { applicationId, step } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.onboarding.step', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('VALIDATION_ERROR', 'Invalid step data.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const body = await req.json();
    const { value } = body as { value?: unknown };

    if (value === undefined) {
      return Response.json(err('VALIDATION_ERROR', 'Step value is required.', requestId), { status: 422 });
    }

    // Use GET with custom method via laravelPost equivalent - we need a PUT helper
    const { laravelPut } = await import('@/adapters/backend/laravel-client');
    const { data } = await laravelPut<StepResponse>(
      actor.token,
      `/onboarding/applications/${encodeURIComponent(applicationId)}/step/${encodeURIComponent(step)}`,
      { value },
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        state_forbidden: 'CONFLICT',
        validation_failed: 'VALIDATION_ERROR',
        payload_too_large: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to save step.', requestId);
  }
}