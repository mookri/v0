import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { readCustomerToken, resolveCustomerActor, customerTokenSecret } from '@/server/backend-session';
import { toBookingDetails, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function GET(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;
  if (laravelEnabled()) {
    return laravelBooking(req, bookingId, requestId);
  }
  if (!bookingId || bookingId.length > 200) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid booking id.', requestId), { status: 422 });
  }
  // Mock mode has no reservation store (GET /api/bookings is empty), so a
  // detail lookup is truthfully not-found. Never invent a booking here.
  return Response.json(err('BOOKING_NOT_FOUND', 'Booking could not be found.', requestId), { status: 404 });
}

async function laravelBooking(req: Request, bookingId: string, requestId: string) {
  const cookie = req.headers.get('cookie');
  const actor = resolveCustomerActor(cookie, customerTokenSecret());
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  if (!bookingId || bookingId.length > 200) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid booking id.', requestId), { status: 422 });
  }
  try {
    const { data: reservation } = await laravelGet<LaravelReservation>(
      readCustomerToken(cookie),
      `/booking/reservations/${encodeURIComponent(bookingId)}`,
      {},
      { not_found: 'BOOKING_NOT_FOUND' },
    );
    return Response.json(ok(toBookingDetails(reservation), requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Booking is unavailable.', requestId);
  }
}
