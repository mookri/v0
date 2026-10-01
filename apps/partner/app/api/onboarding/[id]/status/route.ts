import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps, onboardingDocs, getEvidence } from '@/server/onboarding';
import { headersToRecord } from '@/application/actor';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { missingSteps } from '@navdhya/partner/src/onboarding/steps';

export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { id } = await params;
  const app = await onboardingApps.findById(id);
  if (!app) return Response.json(err('ONBOARDING_NOT_FOUND', 'Application not found.', requestId), { status: 404 });
  if (app.identityId !== actor.userId) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
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
