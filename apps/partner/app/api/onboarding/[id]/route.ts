import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, headersToRecord } from '@/application/actor';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import type { ApplicationTrack } from '@navdhya/partner/src/domain/partner-application';

export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    // Dev/mock fallback
    const { loadOwnApplication } = await import('@/server/onboarding');
    const { getTrackDefinition } = await import('@navdhya/partner/src/onboarding/tracks');
    const { missingSteps } = await import('@navdhya/partner/src/onboarding/steps');
    const { getEvidence } = await import('@/server/onboarding');

    const loaded = await loadOwnApplication(req, requestId, id);
    if ('response' in loaded) return loaded.response;
    const app = loaded.application;
    const def = getTrackDefinition(app.track, app.trackVersion);
    const evidence = getEvidence(id);
    const needs = missingSteps(def, evidence);
    return Response.json(
      ok(
        {
          application: { id: app.id, track: app.track, trackVersion: app.trackVersion, status: app.status },
          steps: def.steps,
          needs,
          evidence,
        },
        requestId,
      ),
    );
  }

  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? (await import('@/application/actor')).readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelGet<Record<string, unknown>>(
      token,
      `/onboarding/applications/${encodeURIComponent(id)}`,
      {},
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
      },
    );

    // Transform backend response to match partner app's expected shape
    const app = data.data as Record<string, unknown>;
    const def = await (async () => {
      const { getTrackDefinition } = await import('@navdhya/partner/src/onboarding/tracks');
      return getTrackDefinition(app.track as ApplicationTrack, app.track_version as string);
    })();
    const { missingSteps } = await import('@navdhya/partner/src/onboarding/steps');
    const needs = missingSteps(def, (app.evidence ?? {}) as Record<string, unknown>);

    return Response.json(
      ok(
        {
          application: { id: app.id, track: app.track, trackVersion: app.track_version, status: app.status },
          steps: def.steps,
          needs,
          evidence: app.evidence ?? {},
        },
        requestId,
      ),
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Application details are unavailable.', requestId);
  }
}