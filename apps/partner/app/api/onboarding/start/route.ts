import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps } from '@/server/onboarding';
import { headersToRecord } from '@/application/actor';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { ApplicationTrack } from '@navdhya/partner/src/domain/partner-application';
import { DEFAULT_MARKET } from '@navdhya/identity/src/ports/actor-resolver';

export async function POST(req: Request) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
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
