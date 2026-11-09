import { newRequestId, err, ok } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { cancelCustomerBooking } from '@/application/bookings/cancel-customer-booking';
import { scopedIdempotencyKey } from '@/application/idempotency';
import { laravelEnabled, laravelPost, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { toBookingDetails, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function POST(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;

    let ifMatch = req.headers.get('If-Match');
    const idempotencyKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;
    const extraHeaders: Record<string, string> = {
      'Idempotency-Key': idempotencyKey,
    };

    if (!ifMatch) {
      try {
        const current = await laravelGet<{ version: number }>(
          actor.token,
          `/booking/reservations/${encodeURIComponent(bookingId)}`,
          {},
          {},
          {},
          'v2',
        );
        ifMatch = `W/"v${current.data.version}"`;
      } catch (e) {
        return laravelErrorResponse(e, 'Target booking could not be resolved.', requestId);
      }
    }
    extraHeaders['If-Match'] = ifMatch;

    try {
      const { data: reservation } = await laravelPost<LaravelReservation>(
        actor.token,
        `/booking/reservations/${encodeURIComponent(bookingId)}/cancel`,
        {},
        { not_found: 'BOOKING_NOT_FOUND', state_forbidden: 'BOOKING_CANNOT_CANCEL', invalid_state_transition: 'BOOKING_CANNOT_CANCEL' },
        extraHeaders,
        'v2',
      );
      return Response.json(ok(toBookingDetails(reservation), requestId));
    } catch (e) {
      return laravelErrorResponse(e, 'Booking cannot be cancelled.', requestId);
    }
  }
  const ctx = anonymousContext('customer.booking.detail', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const rawKey = req.headers.get('Idempotency-Key') ?? `ik_${requestId}`;
  const idempotencyKey = scopedIdempotencyKey({
    actorId: ctx.actor.userId,
    operation: 'cancel-booking',
    resourceId: bookingId,
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
