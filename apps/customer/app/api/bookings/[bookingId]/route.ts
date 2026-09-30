import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { authorizeBooking } from '@/application/bookings/get-customer-booking-details';
import { toCustomerBookingDetails } from '@/application/bookings/get-customer-booking-details';

export async function GET(_req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;
  const ctx = anonymousContext('customer.booking.detail', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  if (!bookingId || bookingId.length > 200) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid booking id.', requestId), { status: 400 });
  }
  const access = authorizeBooking(null, ctx.actor.userId);
  if (!access.ok) return Response.json(err('BOOKING_NOT_FOUND', 'Booking could not be found.', requestId), { status: 404 });
  return Response.json(ok(toCustomerBookingDetails({ booking: access.booking, puja: { id: 'p', name: 'Puja' } }), requestId));
}
