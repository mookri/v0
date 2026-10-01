import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingOtp, getEvidence, loadOwnApplication } from '@/server/onboarding';
import { beginSubmit } from '@navdhya/partner/src/application/onboarding-submit';

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;
  const loaded = await loadOwnApplication(req, requestId, id);
  if ('response' in loaded) return loaded.response;
  const app = loaded.application;
  try {
    const { challengeId } = await beginSubmit({ otp: onboardingOtp() }, app, getEvidence(id), app.identityId, new Date().toISOString());
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
