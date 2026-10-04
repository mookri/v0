import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, headersToRecord } from '@/application/actor';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';

interface SubmitBeginResponse {
  data: { id: string; object: string; challenge_id: string };
  meta: { request_id?: string };
}

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    // Dev/mock fallback
    const { onboardingOtp, getEvidence, loadOwnApplication } = await import('@/server/onboarding');
    const { beginSubmit } = await import('@navdhya/partner/src/application/onboarding-submit');

    const loaded = await loadOwnApplication(req, requestId, id);
    if ('response' in loaded) return loaded.response;
    const app = loaded.application;
    try {
      const { challengeId } = await beginSubmit({ otp: onboardingOtp() }, app, getEvidence(id), app.identityId, new Date().toISOString());
      return Response.json(ok({ applicationId: id, challengeId }, requestId));
    } catch (e) {
      const code = e instanceof Error ? e.message : '';
      if (code.startsWith('SUBMIT_INCOMPLETE')) {
        return Response.json(err('ONBOARDING_INCOMPLETE', 'Steps are still missing.', requestId), { status: 422 });
      }
      if (code.startsWith('SUBMIT_INVALID_STATE')) {
        return Response.json(err('ONBOARDING_INVALID_TRANSITION', 'Cannot submit in this state.', requestId), { status: 409 });
      }
      if (code === 'OTP_RATE_LIMITED') {
        return Response.json(err('RATE_LIMITED', 'Too many attempts.', requestId), { status: 429 });
      }
      throw e;
    }
  }

  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? (await import('@/application/actor')).readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelPost<SubmitBeginResponse>(
      token,
      `/onboarding/applications/${encodeURIComponent(id)}/submit`,
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