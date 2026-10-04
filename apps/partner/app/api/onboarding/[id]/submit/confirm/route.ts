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
    // Dev/mock fallback
    const { onboardingOtp, onboardingApps, getEvidence, loadOwnApplication } = await import('@/server/onboarding');
    const { confirmSubmit } = await import('@navdhya/partner/src/application/onboarding-submit');

    const loaded = await loadOwnApplication(req, requestId, id);
    if ('response' in loaded) return loaded.response;
    const app = loaded.application;
    let body: { challengeId?: string; code?: string } = {};
    try {
      body = (await req.json()) as typeof body;
    } catch {
      return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
    }
    if (!body.challengeId || typeof body.code !== 'string') {
      return Response.json(err('VALIDATION_ERROR', 'challengeId and code are required.', requestId), { status: 400 });
    }
    try {
      const { application } = await confirmSubmit(
        { otp: onboardingOtp() },
        app,
        getEvidence(id),
        body.challengeId,
        body.code,
        new Date().toISOString(),
      );
      await onboardingApps.update(application);
      return Response.json(ok({ applicationId: id, status: application.status }, requestId));
    } catch (e) {
      const code = e instanceof Error ? e.message : '';
      if (code === 'OTP_INVALID') {
        return Response.json(err('UNAUTHENTICATED', 'Invalid or expired code.', requestId), { status: 401 });
      }
      if (code.startsWith('SUBMIT_INCOMPLETE')) {
        return Response.json(err('ONBOARDING_INCOMPLETE', 'Steps are still missing.', requestId), { status: 422 });
      }
      if (code.startsWith('SUBMIT_INVALID_STATE')) {
        return Response.json(err('ONBOARDING_INVALID_TRANSITION', 'Cannot submit in this state.', requestId), { status: 409 });
      }
      throw e;
    }
  }

  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  let body: { challengeId?: string; code?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }
  if (!body.challengeId || typeof body.code !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challengeId and code are required.', requestId), { status: 400 });
  }

  try {
    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? (await import('@/application/actor')).readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelPost<SubmitConfirmResponse>(
      token,
      `/onboarding/applications/${encodeURIComponent(id)}/submit/confirm`,
      { challenge_id: body.challengeId, code: body.code },
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