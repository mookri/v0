import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface TrackInfo {
  id: string;
  name: string;
  version: string;
  sla_hours: number;
  steps: Array<{ id: string; required: boolean }>;
}

interface TracksResponse {
  data: Record<string, TrackInfo>;
  meta: { request_id?: string };
}

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.onboarding.tracks', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(ok({ data: {} as Record<string, TrackInfo>, meta: { request_id: requestId } }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<TracksResponse>(
      actor.token,
      '/onboarding/tracks',
      {},
      {
        not_found: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { headers: { 'Cache-Control': 'private, max-age=300' } });
  } catch (e) {
    return laravelErrorResponse(e, 'Onboarding tracks are unavailable.', requestId);
  }
}