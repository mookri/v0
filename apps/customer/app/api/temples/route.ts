import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

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

interface TemplePage {
  limit?: number;
  has_more?: boolean;
  next_cursor?: string | null;
}

// Payload shape the mock branch, parity-customers.md and
// (protected)/temples/page.tsx all agree on: `{data, meta: {page}}`.
interface TempleListPayload {
  data: TempleProfile[];
  meta: { page?: TemplePage; request_id?: string };
}

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ data: [] as TempleProfile[], meta: { page: { limit: 20, has_more: false, next_cursor: null } } }, requestId), {
      headers: { 'Cache-Control': 'private, max-age=60' },
    });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) {
      query[k] = v;
    }

    const response = await laravelGet<TempleProfile[]>(
      actor.token,
      '/temple/profiles',
      query,
      {
        not_found: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(
      ok({ data: response.data, meta: response.meta } satisfies TempleListPayload, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Temple directory is unavailable.', requestId);
  }
}