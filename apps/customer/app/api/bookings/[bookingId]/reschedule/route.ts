import { newRequestId, err, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { toBookingDetails, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function POST(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;
  if (laravelEnabled()) {
    return laravelReschedule(req, bookingId, requestId);
  }
  let body: { newStartTime?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  const start = body.newStartTime ? new Date(body.newStartTime) : null;
  if (!start || Number.isNaN(start.getTime())) {
    return Response.json(err('VALIDATION_ERROR', 'newStartTime must be an ISO timestamp.', requestId), { status: 422 });
  }
  // Mock mode has no reservation store, so the move cannot exist. Keep the
  // request validation (malformed bodies stay 422) and return the honest
  // not-found instead of the old always-401 gate.
  return Response.json(err('BOOKING_NOT_FOUND', 'Booking could not be found.', requestId), { status: 404 });
}

// Wired reschedule: the BFF reads the live version first (optimistic
// lock), then posts the move. Version races surface as STALE_RESOURCE
// so the client refetches instead of overwriting.
async function laravelReschedule(req: Request, bookingId: string, requestId: string) {
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  let body: { newStartTime?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  const start = body.newStartTime ? new Date(body.newStartTime) : null;
  if (!start || Number.isNaN(start.getTime())) {
    return Response.json(err('VALIDATION_ERROR', 'newStartTime must be an ISO timestamp.', requestId), { status: 422 });
  }
  const codeMap = {
    not_found: 'BOOKING_NOT_FOUND',
    state_forbidden: 'BOOKING_RESCHEDULE_UNAVAILABLE',
    version_conflict: 'STALE_RESOURCE',
    conflict: 'BOOKING_SLOT_CONFLICT',
  } as const;
  try {
    const { data: current } = await laravelGet<LaravelReservation>(
      actor.token,
      `/booking/reservations/${encodeURIComponent(bookingId)}`,
      {},
      codeMap,
    );
    const { data: moved } = await laravelPost<LaravelReservation>(
      actor.token,
      `/booking/reservations/${encodeURIComponent(bookingId)}:reschedule`,
      { new_start_time: start.toISOString(), version: current.version },
      codeMap,
    );
    return Response.json(ok(toBookingDetails(moved), requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Reschedule is unavailable.', requestId);
  }
}
