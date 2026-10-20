import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired booking history (CUSTOMER_BACKEND=laravel): GET /api/bookings/[bookingId]/history
export async function GET(
  req: Request,
  { params }: { params: Promise<{ bookingId: string }> }
) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const { bookingId } = await params;
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<unknown>(actor.token, `/booking/reservations/${bookingId}/history`);
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Booking history unavailable.', requestId);
  }
}