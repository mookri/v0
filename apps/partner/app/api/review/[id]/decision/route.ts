import { newRequestId, ok, err } from '@/application/api-envelope';
import { resolveReviewerActor } from '@/server/review';
import { withCookieFallback, readSessionCookie } from '@/application/actor';
import { isReviewer } from '@/server/review';
import { assertSameTenant } from '@navdhya/identity/src/ports/actor-resolver';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { reviewApplication } from '@navdhya/partner/application/review';
import { activateOnApproval } from '@navdhya/partner/application/activation';
import type { PartnerApiErrorCode } from '@/application/api-envelope';

type ReviewDecision = 'APPROVED' | 'REJECTED' | 'REQUEST_INFO';

interface ReviewDecisionResponse {
  data: {
    id: string;
    object: string;
    status: string;
    review_id: string;
  };
  meta: { request_id?: string };
}

type CodeMap = Record<string, PartnerApiErrorCode>;

const DECISION_TO_BACKEND: Record<ReviewDecision, { path: string; codeMap: CodeMap }> = {
  APPROVED: {
    path: '/onboarding/review/{id}:approve',
    codeMap: {
      not_found: 'NOT_FOUND',
      permission_missing: 'FORBIDDEN',
      state_forbidden: 'CONFLICT',
      validation_failed: 'VALIDATION_ERROR',
      step_up_required: 'FORBIDDEN',
    },
  },
  REJECTED: {
    path: '/onboarding/review/{id}:reject',
    codeMap: {
      not_found: 'NOT_FOUND',
      permission_missing: 'FORBIDDEN',
      state_forbidden: 'CONFLICT',
      validation_failed: 'VALIDATION_ERROR',
      step_up_required: 'FORBIDDEN',
    },
  },
  REQUEST_INFO: {
    path: '/onboarding/review/{id}:request-info',
    codeMap: {
      not_found: 'NOT_FOUND',
      permission_missing: 'FORBIDDEN',
      state_forbidden: 'CONFLICT',
      validation_failed: 'VALIDATION_ERROR',
      step_up_required: 'FORBIDDEN',
    },
  },
};

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    // Dev/mock fallback
    const { onboardingApps } = await import('@/server/onboarding');
    const { reviewAudit, reviewPartners, isReviewer: checkReviewer, resolveReviewerActor: resolveActor } = await import('@/server/review');
    const { withCookieFallback: withFallback } = await import('@/application/actor');
    const { assertSameTenant: assertTenant } = await import('@navdhya/identity/src/ports/actor-resolver');
    const { reviewApplication: revApp } = await import('@navdhya/partner/application/review');
    type DecisionType = ReviewDecision;
    const { activateOnApproval: activate } = await import('@navdhya/partner/application/activation');

    const actor = await resolveActor(withFallback(req));
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    if (!checkReviewer(actor.roles)) {
      return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
    }
    const app = await (await import('@/server/onboarding')).onboardingApps.findById(id);
    if (!app) return Response.json(err('ONBOARDING_NOT_FOUND', 'Application not found.', requestId), { status: 404 });
    try {
      assertTenant(actor, { market: app.market });
    } catch {
      return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
    }
    let body: { decision?: DecisionType; reasonCode?: string; note?: string; secondReviewer?: string } = {};
    try {
      body = (await req.json()) as typeof body;
    } catch {
      return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
    }
    if (body.decision !== 'APPROVED' && body.decision !== 'REJECTED' && body.decision !== 'REQUEST_INFO') {
      return Response.json(err('VALIDATION_ERROR', 'Invalid decision.', requestId), { status: 400 });
    }
    if (body.decision === 'REJECTED' && (!body.secondReviewer || body.secondReviewer === actor.userId)) {
      return Response.json(err('VALIDATION_ERROR', 'Rejections need a second reviewer.', requestId), { status: 400 });
    }
    const nowIso = new Date().toISOString();
    try {
      const reviewable = app.status === 'SUBMITTED' ? { ...app, status: 'UNDER_REVIEW' as const } : app;
      if (reviewable !== app) await (await import('@/server/onboarding')).onboardingApps.update(reviewable);
      const { application, record } = await revApp(reviewable, {
        decision: body.decision!,
        reviewerId: actor.userId,
        reasonCode: body.reasonCode as 'DOCUMENT_INCOMPLETE' | 'SKILL_REQUIREMENT_NOT_MET' | 'SERVICE_AREA_UNSUPPORTED' | 'VERIFICATION_FAILED' | 'PROFILE_INCOMPLETE' | 'OTHER' | undefined,
        note: body.secondReviewer ? `[countersigned:${body.secondReviewer}] ${body.note ?? ''}`.trim() : body.note,
        nowIso,
      });
      await (await import('@/server/onboarding')).onboardingApps.update(application);
      await (await import('@/server/review')).reviewAudit.append(record);
      let activated: { partnerId: string; eligible: boolean } | null = null;
      if (application.status === 'APPROVED') {
        const result = await activate(
          { partners: (await import('@/server/review')).reviewPartners },
          {
            identityId: application.identityId,
            type: application.type,
            partnerId: `p_${application.identityId}`,
            nowIso,
          },
        );
        activated = { partnerId: result.partner.id, eligible: result.eligible };
      }
      return Response.json(ok({ applicationId: id, status: application.status, reviewId: record.id, activated }, requestId));
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

  const actor = await resolveReviewerActor(withCookieFallback(req));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  if (!isReviewer(actor.roles)) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }

  let body: { decision?: ReviewDecision; reasonCode?: string; note?: string; secondReviewer?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }
  if (!body.decision || !['APPROVED', 'REJECTED', 'REQUEST_INFO'].includes(body.decision)) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid decision.', requestId), { status: 400 });
  }
  if (body.decision === 'REJECTED' && (!body.secondReviewer || body.secondReviewer === actor.userId)) {
    return Response.json(err('VALIDATION_ERROR', 'Rejections need a second reviewer.', requestId), { status: 400 });
  }

  try {
    const { path, codeMap } = DECISION_TO_BACKEND[body.decision];
    const backendPath = path.replace('{id}', encodeURIComponent(id));

    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelPost<{
      data: { id: string; object: string; status: string; review_id: string };
      meta: { request_id?: string };
    }>(
      token,
      backendPath,
      body.decision === 'REJECTED'
        ? { reason_code: body.reasonCode, second_reviewer: body.secondReviewer, note: body.note }
        : { reason_code: body.reasonCode, note: body.note },
      codeMap,
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to record review decision.', requestId);
  }
}