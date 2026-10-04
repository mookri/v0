import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, headersToRecord } from '@/application/actor';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import type { ApplicationTrack } from '@navdhya/partner/src/domain/partner-application';

export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    // Dev/mock fallback — keep existing in-memory behavior for local dev
    const { onboardingActors: actors, onboardingApps } = await import('@/server/onboarding');
    const { getTrackDefinition } = await import('@navdhya/partner/src/onboarding/tracks');
    const { DEFAULT_MARKET } = await import('@navdhya/identity/src/ports/actor-resolver');

    const actor = await actors.resolve(headersToRecord(req.headers));
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    let body: { track?: string } = {};
    try {
      body = (await req.json()) as typeof body;
    } catch {
      return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
    }
    const track = body.track as ApplicationTrack;
    try {
      const def = getTrackDefinition(track);
      const applicationId = `app_${requestId}`;
      await onboardingApps.save({
        id: applicationId,
        identityId: actor.userId,
        type: track === 'pandit' ? 'PANDIT' : 'VENDOR',
        track,
        trackVersion: def.version,
        market: actor.market ?? DEFAULT_MARKET,
        status: 'DRAFT',
      });
      return Response.json(ok({ applicationId, track, trackVersion: def.version }, requestId), { status: 201 });
    } catch (e) {
      if (e instanceof Error && e.message.startsWith('UNKNOWN_TRACK')) {
        return Response.json(err('VALIDATION_ERROR', 'Unknown track.', requestId), { status: 400 });
      }
      throw e;
    }
  }

  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  let body: { track?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }
  const track = body.track;
  if (!track) {
    return Response.json(err('VALIDATION_ERROR', 'Track is required.', requestId), { status: 400 });
  }

  try {
    // Extract token from session for backend call
    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? (await import('@/application/actor')).readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelPost(
      token,
      '/onboarding/applications',
      { track },
      {
        validation_failed: 'VALIDATION_ERROR',
        unknown_track: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to start onboarding application.', requestId);
  }
}