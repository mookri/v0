import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Referrals (wired only): code, count, and masked referred list straight
// from the backend. Without the flag the surface is unauthenticated-only.
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const { data: summary } = await laravelGet<unknown>(actor.token, '/customers/referrals');
    return Response.json(ok(summary as Record<string, unknown>, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Referrals are unavailable.', requestId);
  }
}
