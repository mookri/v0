import { newRequestId, err, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { toBookingDetails, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function POST(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;

  if (!laravelEnabled()) {
    return Response.json(
      err('VALIDATION_ERROR', 'Confirm unavailable in mock mode.', requestId),
      { status: 422 }
    );
  }

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
    const { data: reservation, etag } = await laravelPost<LaravelReservation>(
      actor.token,
      `/booking/reservations/${encodeURIComponent(bookingId)}/confirm`,
      {},
      {
        not_found: 'BOOKING_NOT_FOUND',
        invalid_state_transition: 'CONFLICT',
        precondition_required: 'STALE_RESOURCE',
        precondition_failed: 'STALE_RESOURCE',
      },
      extraHeaders,
      'v2',
    );

    const headers: Record<string, string> = {};
    if (etag) headers['ETag'] = etag;

    return Response.json(ok(toBookingDetails(reservation), requestId), { headers });
  } catch (e) {
    return laravelErrorResponse(e, 'Confirmation could not be completed.', requestId);
  }
}
