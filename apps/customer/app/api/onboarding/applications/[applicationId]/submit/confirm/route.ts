import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface SubmitConfirmResponse {
  data: { id: string; object: string; status: string };
  meta: { request_id?: string };
}

export async function POST(req: Request, { params }: { params: Promise<{ applicationId: string }> }) {
  const requestId = newRequestId();
  const { applicationId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.onboarding.submit.confirm', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('VALIDATION_ERROR', 'Challenge ID and code are required.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const body = await req.json();
    const { challenge_id, code } = body as { challenge_id?: string; code?: string };

    if (!challenge_id || !code) {
      return Response.json(err('VALIDATION_ERROR', 'Challenge ID and code are required.', requestId), { status: 422 });
    }

    const { data } = await laravelPost<SubmitConfirmResponse>(
      actor.token,
      `/onboarding/applications/${encodeURIComponent(applicationId)}/submit/confirm`,
      { challenge_id, code },
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        state_forbidden: 'CONFLICT',
        validation_failed: 'VALIDATION_ERROR',
        unauthenticated: 'UNAUTHENTICATED',
      },
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to confirm submission.', requestId);
  }
}