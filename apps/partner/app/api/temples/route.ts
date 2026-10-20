import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { templeActor, templeToken } from '@/server/temples';

interface TempleProfile {
  id: string;
  slug: string;
  name: string;
  trust_name: string;
  type: string;
  city: string;
  state: string;
  lat: number | null;
  lng: number | null;
  images: string[];
  amenities: string[];
  description: string | null;
  rating_avg: number;
  review_count: number;
  verification_status: string;
  status: string;
}

interface TempleListPayload {
  data: TempleProfile[];
  meta: { page?: { limit?: number; has_more?: boolean; next_cursor?: string | null }; request_id?: string };
}

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    // Dev/mock fallback: authenticated, empty directory
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(
      ok(
        {
          data: [] as TempleProfile[],
          meta: { page: { limit: 20, has_more: false, next_cursor: null } },
        },
        requestId,
      ),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) query[k] = v;

    const response = await laravelGet<TempleProfile[]>(templeToken(req), '/temple/profiles', query, {
      not_found: 'NOT_FOUND',
      validation_failed: 'VALIDATION_ERROR',
    });

    return Response.json(
      ok({ data: response.data, meta: response.meta } satisfies TempleListPayload, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Temple directory is unavailable.', requestId);
  }
}
