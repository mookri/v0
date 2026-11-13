import { newRequestId, err, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { toBookingDetails, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function POST(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;
  const actor = laravelEnabled() ? wiredActor(req, requestId) : null;
  if (actor instanceof Response) return actor;

  let body: unknown;
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  if (!body || typeof body !== 'object' || Array.isArray(body)
    || !('newStartTime' in body) || typeof body.newStartTime !== 'string'
    || !('version' in body) || typeof body.version !== 'number'
    || !Number.isSafeInteger(body.version) || body.version < 1) {
    return Response.json(err('VALIDATION_ERROR', 'A timestamp and the observed booking version are required.', requestId), { status: 422 });
  }
  const start = new Date(body.newStartTime);
  if (Number.isNaN(start.getTime())) {
    return Response.json(err('VALIDATION_ERROR', 'newStartTime must be an ISO timestamp.', requestId), { status: 422 });
  }
  if (!actor) {
    return Response.json(err('BOOKING_NOT_FOUND', 'Booking could not be found.', requestId), { status: 404 });
  }

  const codeMap = {
    not_found: 'BOOKING_NOT_FOUND',
    state_forbidden: 'BOOKING_RESCHEDULE_UNAVAILABLE',
    invalid_state_transition: 'BOOKING_RESCHEDULE_UNAVAILABLE',
    version_conflict: 'STALE_RESOURCE',
    precondition_required: 'STALE_RESOURCE',
    precondition_failed: 'STALE_RESOURCE',
    conflict: 'BOOKING_SLOT_CONFLICT',
  } as const;

  const idempotencyKey = req.headers.get('Idempotency-Key')?.trim() ?? '';
  if (!idempotencyKey) {
    return Response.json(
      err('VALIDATION_ERROR', 'Idempotency-Key header is required.', requestId),
      { status: 400 }
    );
  }
  const extraHeaders: Record<string, string> = {
    'Idempotency-Key': idempotencyKey,
    'If-Match': `W/"v${body.version}"`,
  };

  try {
    const { data: moved } = await laravelPost<LaravelReservation>(
      actor.token,
      `/booking/reservations/${encodeURIComponent(bookingId)}/reschedule`,
      { scheduled_at: start.toISOString() },
      codeMap,
      extraHeaders,
      'v2',
    );
    return Response.json(ok(toBookingDetails(moved), requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Reschedule is unavailable.', requestId);
  }
}
