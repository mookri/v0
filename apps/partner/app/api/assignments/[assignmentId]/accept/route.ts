import { newRequestId, ok, err } from '@/application/api-envelope';
import { resolvePartnerActor } from '@/application/actor';
import { acceptOffer } from '@/application/assignments';

export async function POST(req: Request, { params }: { params: { assignmentId: string } }) {
  const requestId = newRequestId();
  const actor = resolvePartnerActor(req.headers);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const idempotencyKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;
  // TODO(phase-24): load the offer from fulfillment via repository, persist on accept.
  const result = acceptOffer(null, actor.partnerId, new Date().toISOString(), idempotencyKey);
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
  void params;
  return Response.json(ok({ assignmentId: params.assignmentId, status: result.offer.status }, requestId));
}
