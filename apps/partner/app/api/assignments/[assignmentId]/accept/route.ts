import { newRequestId, ok, err } from '@/application/api-envelope';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';
import { acceptOffer } from '@/application/assignments';
import { laravelEnabled, laravelPost, PartnerApiError } from '@/adapters/backend/laravel-client';
import { MemoryAssignmentGateway } from '@navdhya/fulfillment/src/adapters/memory-assignment';

const actors = partnerActorResolver();
// Single-instance staging source (same limitation as the events memory
// bus); a shared fulfillment store replaces this behind the port.
const assignments = new MemoryAssignmentGateway();

export function __assignmentGatewayForTests() {
  return assignments;
}

export async function POST(req: Request, { params }: { params: Promise<{ assignmentId: string }> }) {
  const requestId = newRequestId();
  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const { assignmentId } = await params;
  const idempotencyKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;

  if (laravelEnabled()) {
    try {
      const token = bearerOrSessionCookie(req.headers);
      const { data } = await laravelPost(
        token,
        `/pandit/assignments/${encodeURIComponent(assignmentId)}:confirm`,
        {},
        { not_found: 'ASSIGNMENT_NOT_FOUND', state_forbidden: 'ASSIGNMENT_CONFLICT' },
        { 'Idempotency-Key': idempotencyKey }
      );
      return Response.json(ok({ assignmentId, status: 'ACCEPTED', data }, requestId));
    } catch (e) {
      if (e instanceof PartnerApiError) {
        return Response.json(err(e.code, e.message, requestId), { status: e.status });
      }
      return Response.json(err('INTERNAL_ERROR', 'Could not accept assignment.', requestId), { status: 500 });
    }
  }

  const offer = await assignments.get(assignmentId);
  const result = acceptOffer(offer, actor.userId, new Date().toISOString(), idempotencyKey);
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
  const accepted = await assignments.accept(assignmentId);
  if (!accepted) {
    return Response.json(err('ASSIGNMENT_CONFLICT', 'Offer was taken or expired.', requestId), { status: 409 });
  }
  return Response.json(ok({ assignmentId, status: 'ACCEPTED' }, requestId));
}
