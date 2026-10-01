import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps, getEvidence, saveEvidence } from '@/server/onboarding';
import { headersToRecord } from '@/application/actor';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';

export async function PUT(req: Request, { params }: { params: Promise<{ id: string; step: string }> }) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { id, step } = await params;
  const app = await onboardingApps.findById(id);
  if (!app) return Response.json(err('ONBOARDING_NOT_FOUND', 'Application not found.', requestId), { status: 404 });
  if (app.identityId !== actor.userId) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
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
  const idempotencyKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;
  void idempotencyKey;
  saveEvidence(id, { ...getEvidence(id), [step]: body.value });
  return Response.json(ok({ applicationId: id, step, saved: true }, requestId));
}
