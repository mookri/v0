import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingOtp, onboardingApps, getEvidence, loadOwnApplication } from '@/server/onboarding';
import { confirmSubmit } from '@navdhya/partner/src/application/onboarding-submit';

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;
  const loaded = await loadOwnApplication(req, requestId, id);
  if ('response' in loaded) return loaded.response;
  const app = loaded.application;
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
