import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { templeActor, templeToken } from '@/server/temples';

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
  updated_at: string;
}

export async function GET(req: Request, { params }: { params: Promise<{ templeId: string }> }) {
  const requestId = newRequestId();
  const { templeId } = await params;

  if (!laravelEnabled()) {
    // Dev/mock fallback: no fixture data, so detail reads as missing
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(err('NOT_FOUND', 'Temple not found.', requestId), { status: 404 });
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelGet<TempleProfile>(
      templeToken(req),
      `/temple/profiles/${encodeURIComponent(templeId)}`,
      {},
      {
        not_found: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { headers: { 'Cache-Control': 'private, max-age=60' } });
  } catch (e) {
    return laravelErrorResponse(e, 'Temple details are unavailable.', requestId);
  }
}
