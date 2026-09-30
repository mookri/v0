import { newRequestId, err } from '../../../../../application/api-envelope';
import { anonymousContext } from '../../../../../application/request-context';
import { rescheduleCustomerBooking } from '../../../../../application/bookings/reschedule-customer-booking';

export async function POST(req: Request, { params }: { params: { bookingId: string } }) {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.booking.detail', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  let body: { newStartTime?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }
  const start = body.newStartTime ? new Date(body.newStartTime) : null;
  if (!start || Number.isNaN(start.getTime())) {
    return Response.json(err('VALIDATION_ERROR', 'newStartTime must be an ISO timestamp.', requestId), { status: 400 });
  }
  const idempotencyKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;
  const result = rescheduleCustomerBooking(null, ctx.actor.userId, start, true, idempotencyKey);
  if (result.reason === 'NOT_FOUND') {
    return Response.json(err('BOOKING_NOT_FOUND', 'Booking could not be found.', requestId), { status: 404 });
  }
  return Response.json(err('BOOKING_RESCHEDULE_UNAVAILABLE', 'Reschedule is not available for this booking.', requestId), {
    status: 409,
  });
}
