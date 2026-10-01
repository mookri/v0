import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingDocs, getEvidence, loadOwnApplication } from '@/server/onboarding';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps } from '@navdhya/partner/src/onboarding/steps';

export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;
  const loaded = await loadOwnApplication(req, requestId, id);
  if ('response' in loaded) return loaded.response;
  const app = loaded.application;
  const def = getTrackDefinition(app.track, app.trackVersion);
  const documents = await onboardingDocs.findByApplication(id);
  return Response.json(
    ok(
      {
        id: app.id,
        status: app.status,
        rejectionCode: app.rejectionCode ?? null,
        needs: missingSteps(def, getEvidence(id)),
        documents: documents.map((d) => ({ id: d.id, kind: d.kind, status: d.status, reasonCode: d.reasonCode ?? null })),
      },
      requestId,
    ),
  );
}
