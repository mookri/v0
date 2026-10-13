import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, headersToRecord } from '@/application/actor';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';

interface SubmitConfirmResponse {
  data: { id: string; object: string; status: string };
  meta: { request_id?: string };
}

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    return Response.json(
      err('DEPENDENCY_UNAVAILABLE', 'Submission OTP unavailable in mock mode.', requestId),
      { status: 503 },
    );
  }

  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  let body: { challengeId?: string; challenge_id?: string; token?: string; code?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }

  const challengeId = body.challengeId ?? body.challenge_id;
  const token = body.token ?? body.code;

  if (!challengeId || typeof token !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challenge_id and token are required.', requestId), { status: 400 });
  }

  try {
    const bearerToken = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? (await import('@/application/actor')).readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelPost<SubmitConfirmResponse>(
      bearerToken,
      `/onboarding/applications/${encodeURIComponent(id)}/submit/confirm`,
      { challenge_id: challengeId, token },
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