import { newRequestId, ok, err } from '@/application/api-envelope';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';
import { laravelEnabled, laravelPost, PartnerApiError } from '@/adapters/backend/laravel-client';

const actors = partnerActorResolver();

export async function POST(
  req: Request,
  { params }: { params: Promise<{ templeId: string; approvalId: string }> }
) {
  const requestId = newRequestId();
  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { templeId, approvalId } = await params;

  let body: { decision: 'APPROVED' | 'DECLINED'; reason?: string };
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid JSON body.', requestId), { status: 422 });
  }

  if (!body.decision || !['APPROVED', 'DECLINED'].includes(body.decision)) {
    return Response.json(
      err('VALIDATION_ERROR', 'decision must be APPROVED or DECLINED.', requestId),
      { status: 422 }
    );
  }

  if (laravelEnabled()) {
    try {
      const token = bearerOrSessionCookie(req.headers);
      const response = await laravelPost<Record<string, unknown>>(
        token,
        `/temple/partner/temples/${encodeURIComponent(templeId)}/council/approvals/${encodeURIComponent(approvalId)}/decide`,
        body,
        {
          not_found: 'NOT_FOUND',
          state_forbidden: 'CONFLICT',
          validation_failed: 'VALIDATION_ERROR',
        }
      );
      return Response.json(ok(response.data, requestId));
    } catch (e) {
      if (e instanceof PartnerApiError) {
        return Response.json(err(e.code, e.message, requestId), { status: e.status });
      }
      return Response.json(err('INTERNAL_ERROR', 'Could not record council decision.', requestId), { status: 500 });
    }
  }

  return Response.json(
    ok(
      {
        approvalId,
        templeId,
        decision: body.decision,
        reason: body.reason ?? null,
        status: body.decision,
      },
      requestId
    )
  );
}
