import { newRequestId, ok, err } from '@/application/api-envelope';
import { partnerActorResolver, headersToRecord } from '@/application/actor';
import { acceptOffer } from '@/application/assignments';

const actors = partnerActorResolver();

export async function POST(req: Request, { params }: { params: Promise<{ assignmentId: string }> }) {
  const requestId = newRequestId();
  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const idempotencyKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;
  // TODO(fulfillment-repo): load the offer from fulfillment via repository, persist on accept.
  const result = acceptOffer(null, actor.userId, new Date().toISOString(), idempotencyKey);
  if (!result.ok && result.reason === 'NOT_FOUND') {
    return Response.json(err('ASSIGNMENT_NOT_FOUND', 'Assignment not found.', requestId), { status: 404 });
  }
  if (!result.ok && result.reason === 'NOT_OWNED') {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  if (!result.ok && result.reason === 'EXPIRED') {
    return Response.json(err('ASSIGNMENT_EXPIRED', 'Offer is no longer valid.', requestId), { status: 409 });
  }
  if (!result.ok) {
    return Response.json(err('ASSIGNMENT_CONFLICT', 'Offer cannot be accepted.', requestId), { status: 409 });
  }
  const { assignmentId } = await params;
  return Response.json(ok({ assignmentId, status: result.offer.status }, requestId));
}
