import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPut, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';

const actors = partnerActorResolver();

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    const actor = await actors.resolve(headersToRecord(req.headers));
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(err('NOT_FOUND', 'Pandit profile not found.', requestId), { status: 404 });
  }

  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  const token = bearerOrSessionCookie(req.headers);
  if (!token) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelGet<Record<string, unknown>>(
      token,
      '/pandit/profile',
      {},
      {
        not_found: 'NOT_FOUND',
        PANDIT_NOT_FOUND: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { headers: { 'Cache-Control': 'private, max-age=60' } });
  } catch (e) {
    return laravelErrorResponse(e, 'Pandit profile is unavailable.', requestId);
  }
}

export async function PATCH(req: Request) {
  const requestId = newRequestId();

  const body = (await req.json().catch(() => ({}))) as Record<string, unknown>;

  if (!laravelEnabled()) {
    const actor = await actors.resolve(headersToRecord(req.headers));
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok({ id: 'mock', ...body }, requestId));
  }

  const actor = await actors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  const token = bearerOrSessionCookie(req.headers);
  if (!token) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelPut<Record<string, unknown>>(
      token,
      '/pandit/profile',
      body,
      {
        not_found: 'NOT_FOUND',
        PANDIT_NOT_FOUND: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to update pandit profile.', requestId);
  }
}

