import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps } from '@/server/onboarding';
import { reviewAudit, isReviewer } from '@/server/review';
import { headersToRecord } from '@/application/actor';
import { assertSameTenant } from '@navdhya/identity/src/ports/actor-resolver';
import { reviewApplication, ReviewDecision } from '@navdhya/partner/src/application/review';

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  if (!isReviewer(actor.roles)) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  const { id } = await params;
  const app = await onboardingApps.findById(id);
  if (!app) return Response.json(err('ONBOARDING_NOT_FOUND', 'Application not found.', requestId), { status: 404 });
  try {
    assertSameTenant(actor, { market: app.market });
  } catch {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  let body: { decision?: ReviewDecision; reasonCode?: 'OTHER'; note?: string; secondReviewer?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }
  if (body.decision !== 'APPROVED' && body.decision !== 'REJECTED' && body.decision !== 'REQUEST_INFO') {
    return Response.json(err('VALIDATION_ERROR', 'Invalid decision.', requestId), { status: 400 });
  }
  // Four-eyes on negative decisions: a different reviewer must countersign.
  if (body.decision === 'REJECTED' && (!body.secondReviewer || body.secondReviewer === actor.userId)) {
    return Response.json(err('VALIDATION_ERROR', 'Rejections need a second reviewer.', requestId), { status: 400 });
  }
  const nowIso = new Date().toISOString();
  try {
    const { application, record } = reviewApplication(app, {
      decision: body.decision,
      reviewerId: actor.userId,
      reasonCode: body.reasonCode,
      note: body.secondReviewer ? `[countersigned:${body.secondReviewer}] ${body.note ?? ''}`.trim() : body.note,
      nowIso,
    });
    await onboardingApps.update(application);
    await reviewAudit.append(record);
    return Response.json(ok({ applicationId: id, status: application.status, reviewId: record.id }, requestId));
  } catch (e) {
    const code = e instanceof Error ? e.message : '';
    if (code.startsWith('REVIEW_INVALID_STATE')) {
      return Response.json(err('ONBOARDING_INVALID_TRANSITION', 'Cannot review in this state.', requestId), { status: 409 });
    }
    if (code === 'REVIEW_REASON_REQUIRED') {
      return Response.json(err('VALIDATION_ERROR', 'Rejections need a reason.', requestId), { status: 400 });
    }
    throw e;
  }
}
