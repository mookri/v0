import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingApps, getEvidence, loadOwnApplication } from '@/server/onboarding';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps } from '@navdhya/partner/src/onboarding/steps';

export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;
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
