import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface TempleAvailabilitySlot {
  assignment_id: string;
  puja_id: string;
  day_of_week: number;
  start: string;
  end: string;
  capacity: number | null;
  price_override_minor: number | null;
}

// Kernel envelope unwrapped here: the BFF payload is the bare slots
// array — same shape as pandit availability (parity-customers.md
// temple availability row).
export async function GET(req: Request, { params }: { params: Promise<{ templeId: string }> }) {
  const requestId = newRequestId();
  const { templeId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.temple.availability', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    // Mock world has no temples — same 404 as the temple detail route.
    return Response.json(err('NOT_FOUND', 'Temple not found.', requestId), { status: 404 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) {
      query[k] = v;
    }

    const { data } = await laravelGet<TempleAvailabilitySlot[]>(
      actor.token,
      `/temple/profiles/${encodeURIComponent(templeId)}/availability`,
      query,
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
    return laravelErrorResponse(e, 'Temple availability is unavailable.', requestId);
  }
}
