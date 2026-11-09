import { newRequestId, err, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function POST(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  const { bookingId } = await params;

  if (!laravelEnabled()) {
    return Response.json(
      err('VALIDATION_ERROR', 'Quote calculation unavailable in mock mode.', requestId),
      { status: 422 }
    );
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  let body: Record<string, unknown> = {};
  try {
    body = await req.json();
  } catch {
    // Optional body
  }

  try {
    const { data: quote, etag } = await laravelPost<Record<string, unknown>>(
      actor.token,
      `/booking/reservations/${encodeURIComponent(bookingId)}/quote`,
      body,
      { not_found: 'BOOKING_NOT_FOUND', invalid_state: 'CONFLICT' },
      {},
      'v2',
    );

    const headers: Record<string, string> = {};
    if (etag) headers['ETag'] = etag;

    return Response.json(ok(quote, requestId), { headers });
  } catch (e) {
    return laravelErrorResponse(e, 'Commercial quote could not be calculated.', requestId);
  }
}
