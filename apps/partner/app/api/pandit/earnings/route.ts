import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';

const actors = partnerActorResolver();

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    const actor = await actors.resolve(headersToRecord(req.headers));
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok({ data: { total_minor: 0, currency: 'INR', breakdown: [] }, meta: { fallback: true } }, requestId));
  }

  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  const token = bearerOrSessionCookie(req.headers);
  if (!token) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelGet<unknown>(
      token,
      '/pandit/earnings',
      {},
      {
        not_found: 'NOT_FOUND',
        PANDIT_NOT_FOUND: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { headers: { 'Cache-Control': 'private, max-age=60' } });
  } catch (e) {
    return laravelErrorResponse(e, 'Pandit earnings are unavailable.', requestId);
  }
}
