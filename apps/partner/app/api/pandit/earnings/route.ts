import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';

const actors = partnerActorResolver();

export async function GET(req: Request) {
  const requestId = newRequestId();

  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), {
      status: 401,
      headers: { 'Cache-Control': 'private, no-store', Vary: 'Cookie, Authorization' },
    });
  }

  if (!laravelEnabled()) {
    return Response.json(
      err(
        'DEPENDENCY_UNAVAILABLE',
        'Partner earnings ledger backend is unavailable (NAVDHYA_API_BASE not configured).',
        requestId,
      ),
      {
        status: 503,
        headers: { 'Cache-Control': 'private, no-store', Vary: 'Cookie, Authorization' },
      },
    );
  }

  const token = bearerOrSessionCookie(req.headers);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), {
      status: 401,
      headers: { 'Cache-Control': 'private, no-store', Vary: 'Cookie, Authorization' },
    });
  }

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

    return Response.json(ok(data, requestId), {
      headers: { 'Cache-Control': 'private, no-store', Vary: 'Cookie, Authorization' },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Pandit earnings ledger is currently unavailable.', requestId);
  }
}
