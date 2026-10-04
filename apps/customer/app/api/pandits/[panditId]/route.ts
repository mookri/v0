import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface PanditProfile {
  id: string;
  name: string;
  bio?: string;
  specializations: string[];
  market: string;
  languages: string[];
  experience_years: number;
  rating_avg: number;
  review_count: number;
  verification_status: string;
  status: string;
  created_at: string;
}

export async function GET(req: Request, { params }: { params: Promise<{ panditId: string }> }) {
  const requestId = newRequestId();
  const { panditId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.pandit.detail', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('NOT_FOUND', 'Pandit not found.', requestId), { status: 404 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<PanditProfile>(
      actor.token,
      `/pandit/profiles/${encodeURIComponent(panditId)}`,
      {},
      {
        not_found: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(
      ok(data, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Pandit details are unavailable.', requestId);
  }
}