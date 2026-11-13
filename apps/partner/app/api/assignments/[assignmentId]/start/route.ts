import { newRequestId, ok, err } from '@/application/api-envelope';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';
import { laravelEnabled, laravelPost, PartnerApiError } from '@/adapters/backend/laravel-client';

const actors = partnerActorResolver();

export async function POST(req: Request, { params }: { params: Promise<{ assignmentId: string }> }) {
  const requestId = newRequestId();
  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { assignmentId } = await params;
  const idempotencyKey = req.headers.get('Idempotency-Key')?.trim() || `ik_${requestId}`;

  let body: { note?: string; checklist?: Record<string, unknown> } = {};
  try {
    body = await req.json();
  } catch {
    // Optional body
  }

  if (laravelEnabled()) {
    try {
      const token = bearerOrSessionCookie(req.headers);
      const { data } = await laravelPost(
        token,
        `/pandit/assignments/${encodeURIComponent(assignmentId)}:start`,
        {
          note: body.note ?? 'started',
          ...(body.checklist ? { checklist: body.checklist } : {}),
        },
        {
          not_found: 'ASSIGNMENT_NOT_FOUND',
          PANDIT_ASSIGNMENT_NOT_FOUND: 'ASSIGNMENT_NOT_FOUND',
          PANDIT_ASSIGNMENT_WRONG_STATE: 'ASSIGNMENT_CONFLICT',
          PANDIT_BOOKING_WRONG_STATE: 'ASSIGNMENT_CONFLICT',
          state_forbidden: 'ASSIGNMENT_CONFLICT',
        },
        { 'Idempotency-Key': idempotencyKey }
      );
      return Response.json(ok({ assignmentId, status: 'IN_PROGRESS', data }, requestId));
    } catch (e) {
      if (e instanceof PartnerApiError) {
        return Response.json(err(e.code, e.message, requestId), { status: e.status });
      }
      return Response.json(err('INTERNAL_ERROR', 'Could not start assignment.', requestId), { status: 500 });
    }
  }

  return Response.json(ok({ assignmentId, status: 'IN_PROGRESS' }, requestId));
}
