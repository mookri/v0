import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, headersToRecord } from '@/application/actor';
import { laravelEnabled, laravelPut, laravelErrorResponse } from '@/adapters/backend/laravel-client';

interface StepResponse {
  data: { id: string; object: string; step: string; saved: boolean };
  meta: { request_id?: string };
}

export async function PUT(req: Request, { params }: { params: Promise<{ id: string; step: string }> }) {
  const requestId = newRequestId();
  const { id, step } = await params;

  if (!laravelEnabled()) {
    // Dev/mock fallback
    const { loadOwnApplication, getEvidence, saveEvidence } = await import('@/server/onboarding');
    const { getTrackDefinition } = await import('@navdhya/partner/src/onboarding/tracks');

    const loaded = await loadOwnApplication(req, requestId, id);
    if ('response' in loaded) return loaded.response;
    const app = loaded.application;
    if (app.status !== 'DRAFT' && app.status !== 'UNDER_REVIEW') {
      return Response.json(err('ONBOARDING_INVALID_TRANSITION', 'Application is not editable.', requestId), { status: 409 });
    }
    const def = getTrackDefinition(app.track, app.trackVersion);
    if (!def.steps.some((s) => s.id === step)) {
      return Response.json(err('VALIDATION_ERROR', 'Unknown step.', requestId), { status: 400 });
    }
    let body: { value?: unknown } = {};
    try {
      body = (await req.json()) as typeof body;
    } catch {
      return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
    }
    saveEvidence(id, { ...getEvidence(id), [step]: body.value });
    return Response.json(ok({ applicationId: id, step, saved: true }, requestId));
  }

  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  let body: { value?: unknown } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }
  if (body.value === undefined) {
    return Response.json(err('VALIDATION_ERROR', 'Step value is required.', requestId), { status: 400 });
  }

  try {
    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? (await import('@/application/actor')).readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelPut<StepResponse>(
      token,
      `/onboarding/applications/${encodeURIComponent(id)}/step/${encodeURIComponent(step)}`,
      { value: body.value },
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