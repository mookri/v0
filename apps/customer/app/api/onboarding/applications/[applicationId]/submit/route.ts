import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface SubmitBeginResponse {
  data: { id: string; object: string; challenge_id: string };
  meta: { request_id?: string };
}

export async function POST(req: Request, { params }: { params: Promise<{ applicationId: string }> }) {
  const requestId = newRequestId();
  const { applicationId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.onboarding.submit.begin', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('CONFLICT', 'Cannot submit in this state.', requestId), { status: 409 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPost<SubmitBeginResponse>(
      actor.token,
      `/onboarding/applications/${encodeURIComponent(applicationId)}/submit`,
      {},
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        state_forbidden: 'CONFLICT',
        validation_failed: 'VALIDATION_ERROR',
        rate_limited: 'RATE_LIMITED',
      },
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to initiate submission.', requestId);
  }
}