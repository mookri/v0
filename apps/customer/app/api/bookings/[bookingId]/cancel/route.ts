import { newRequestId, err, ok } from '../../../../../application/api-envelope';
import { anonymousContext } from '../../../../../application/request-context';
import { cancelCustomerBooking } from '../../../../../application/bookings/cancel-customer-booking';
import { scopedIdempotencyKey } from '../../../../../application/idempotency';

export async function POST(req: Request, { params }: { params: { bookingId: string } }) {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.booking.detail', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const rawKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;
  const idempotencyKey = scopedIdempotencyKey({
    actorId: ctx.actor.userId,
    operation: 'cancel-booking',
    resourceId: params.bookingId,
    key: rawKey,
  });
  const result = cancelCustomerBooking(null, ctx.actor.userId, idempotencyKey);
  if (result.reason === 'NOT_FOUND') {
    return Response.json(err('BOOKING_NOT_FOUND', 'Booking could not be found.', requestId), { status: 404 });
  }
  if (result.reason === 'FORBIDDEN') {
    return Response.json(err('FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  return Response.json(
    err('BOOKING_CANNOT_CANCEL', 'Booking cannot be cancelled in its current state.', requestId),
    { status: 409 },
  );
}
