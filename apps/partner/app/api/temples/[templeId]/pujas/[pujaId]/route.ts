import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { templeActor, templeToken } from '@/server/temples';

interface TemplePujaAssignment {
  id: string;
  temple_id: string;
  puja_id: string;
  price_override_minor: number | null;
  capacity: number | null;
  schedule_json: string | null;
  status: string;
  created_at: string;
}

export async function GET(
  req: Request,
  { params }: { params: Promise<{ templeId: string; pujaId: string }> },
) {
  const requestId = newRequestId();
  const { templeId, pujaId } = await params;

  if (!laravelEnabled()) {
    // Dev/mock fallback: no fixture data, so the assignment reads as missing
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(err('NOT_FOUND', 'Puja assignment not found.', requestId), { status: 404 });
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelGet<TemplePujaAssignment>(
      templeToken(req),
      `/temple/partner/temples/${encodeURIComponent(templeId)}/pujas/${encodeURIComponent(pujaId)}`,
      {},
      {
        not_found: 'NOT_FOUND',
        TEMPLE_PUJA_NOT_FOUND: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { headers: { 'Cache-Control': 'private, max-age=60' } });
  } catch (e) {
    return laravelErrorResponse(e, 'Puja assignment is unavailable.', requestId);
  }
}
