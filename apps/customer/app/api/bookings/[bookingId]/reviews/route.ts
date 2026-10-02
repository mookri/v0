import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function POST(req: Request, { params }: { params: Promise<{ bookingId: string }> }) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  let body: Record<string, unknown> = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  try {
    const { data: review } = await laravelPost<unknown>(
      actor.token,
      `/booking/reservations/${encodeURIComponent((await params).bookingId)}/reviews`,
      body,
      { state_forbidden: 'REVIEW_NOT_ALLOWED', conflict: 'REVIEW_EXISTS', not_found: 'BOOKING_NOT_FOUND' },
    );
    return Response.json(ok(review as Record<string, unknown>, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Review could not be saved.', requestId);
  }
}
