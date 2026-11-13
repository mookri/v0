import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';

const actors = partnerActorResolver();

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  const idempotencyKey = req.headers.get('Idempotency-Key')?.trim() || `ik_${requestId}`;

  let body: { checklist?: Record<string, unknown> } = {};
  try {
    body = await req.json();
  } catch {
    // Optional body
  }

  if (!laravelEnabled()) {
    const actor = await actors.resolve(headersToRecord(req.headers));
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok({ id, status: 'IN_PROGRESS' }, requestId));
  }

  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  const token = bearerOrSessionCookie(req.headers);
  if (!token) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelPost<unknown>(
      token,
      `/pandit/assignments/${encodeURIComponent(id)}:start`,
      body.checklist ? { checklist: body.checklist } : {},
      {
        not_found: 'NOT_FOUND',
        PANDIT_ASSIGNMENT_NOT_FOUND: 'NOT_FOUND',
        PANDIT_BOOKING_WRONG_STATE: 'CONFLICT',
        PANDIT_ASSIGNMENT_WRONG_STATE: 'CONFLICT',
        validation_failed: 'VALIDATION_ERROR',
      },
      { 'Idempotency-Key': idempotencyKey },
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to start assignment.', requestId);
  }
}
