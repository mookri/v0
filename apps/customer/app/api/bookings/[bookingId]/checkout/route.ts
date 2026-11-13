import { newRequestId, err, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { toBookingDetails, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function POST(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;

  if (!laravelEnabled()) {
    return Response.json(
      err('VALIDATION_ERROR', 'Checkout unavailable in mock mode.', requestId),
      { status: 422 }
    );
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  const idempotencyKey = req.headers.get('Idempotency-Key')?.trim() ?? '';
  if (!idempotencyKey) {
    return Response.json(
      err('VALIDATION_ERROR', 'Idempotency-Key header is required.', requestId),
      { status: 400 }
    );
  }
  const extraHeaders: Record<string, string> = {
    'Idempotency-Key': idempotencyKey,
  };

  try {
    const { data: reservation, etag } = await laravelPost<LaravelReservation>(
      actor.token,
      `/booking/reservations/${encodeURIComponent(bookingId)}/checkout`,
      {},
      {
        not_found: 'BOOKING_NOT_FOUND',
        invalid_state_transition: 'CONFLICT',
        resource_unavailable: 'BOOKING_SLOT_CONFLICT',
        quote_stale: 'CONFLICT',
      },
      extraHeaders,
      'v2',
    );

    const headers: Record<string, string> = {};
    if (etag) headers['ETag'] = etag;

    return Response.json(ok(toBookingDetails(reservation), requestId), { headers });
  } catch (e) {
    return laravelErrorResponse(e, 'Checkout could not be completed.', requestId);
  }
}
