import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps, onboardingOtp, getEvidence } from '@/server/onboarding';
import { headersToRecord } from '@/application/actor';
import { confirmSubmit } from '@navdhya/partner/src/application/onboarding-submit';

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { id } = await params;
  const app = await onboardingApps.findById(id);
  if (!app) return Response.json(err('ONBOARDING_NOT_FOUND', 'Application not found.', requestId), { status: 404 });
  if (app.identityId !== actor.userId) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
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
