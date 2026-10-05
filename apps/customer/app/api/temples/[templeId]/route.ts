import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface TempleProfile {
  id: string;
  slug: string;
  name: string;
  trust_name: string;
  type: string;
  address: string;
  city: string;
  state: string;
  lat: number | null;
  lng: number | null;
  images: string[];
  amenities: string[];
  description: string | null;
  verification_status: string;
  status: string;
  created_at: string;
}

export async function GET(req: Request, { params }: { params: Promise<{ templeId: string }> }) {
  const requestId = newRequestId();
  const { templeId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.temple.detail', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('NOT_FOUND', 'Temple not found.', requestId), { status: 404 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<TempleProfile>(
      actor.token,
      `/temple/profiles/${encodeURIComponent(templeId)}`,
      {},
      {
        not_found: 'NOT_FOUND',
        TEMPLE_NOT_FOUND: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(
      ok(data, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Temple details are unavailable.', requestId);
  }
}