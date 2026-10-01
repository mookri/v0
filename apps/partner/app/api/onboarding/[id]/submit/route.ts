import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps, onboardingOtp, getEvidence } from '@/server/onboarding';
import { headersToRecord } from '@/application/actor';
import { beginSubmit } from '@navdhya/partner/src/application/onboarding-submit';

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
  try {
    const { challengeId } = await beginSubmit({ otp: onboardingOtp() }, app, getEvidence(id), actor.userId, new Date().toISOString());
    return Response.json(ok({ applicationId: id, challengeId }, requestId));
  } catch (e) {
    const code = e instanceof Error ? e.message : '';
    if (code.startsWith('SUBMIT_INCOMPLETE')) {
      return Response.json(err('ONBOARDING_INCOMPLETE', 'Steps are still missing.', requestId), { status: 422 });
    }
    if (code.startsWith('SUBMIT_INVALID_STATE')) {
      return Response.json(err('ONBOARDING_INVALID_TRANSITION', 'Cannot submit in this state.', requestId), { status: 409 });
    }
    if (code === 'OTP_RATE_LIMITED') {
      return Response.json(err('RATE_LIMITED', 'Too many attempts.', requestId), { status: 429 });
    }
    throw e;
  }
}
