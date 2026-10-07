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

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.pandits', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(
      ok({ items: [] as PanditProfile[], pagination: { hasMore: false, nextCursor: null } }, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) {
      query[k] = v;
    }

    const page = await laravelGet<PanditProfile[]>(
      actor.token,
      '/pandit/profiles',
      query,
      {
        not_found: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(
      ok(
        {
          items: page.data ?? [],
          pagination: {
            hasMore: page.meta?.page?.has_more ?? false,
            nextCursor: page.meta?.page?.next_cursor ?? null,
          },
        },
        requestId,
      ),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Pandit directory is unavailable.', requestId);
  }
}
