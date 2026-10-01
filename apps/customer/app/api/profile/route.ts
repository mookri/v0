import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      const { data: profile } = await laravelGet<unknown>(actor.token, '/customers/profile');
      return Response.json(ok({ profile }, requestId), {
        headers: { 'Cache-Control': 'private, max-age=30' },
      });
    } catch (e) {
      return laravelErrorResponse(e, 'Profile is unavailable.', requestId);
    }
  }
  const ctx = anonymousContext('customer.profile', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ profile: null }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=30' },
  });
}
