import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface TemplePage {
  limit?: number;
  has_more?: boolean;
  next_cursor?: string | null;
}

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

// Same payload shape as the temple directory: `{data, meta: {page}}`
// (parity-customers.md temple pujas row).
interface TemplePujaListPayload {
  data: TemplePujaAssignment[];
  meta: { page?: TemplePage; request_id?: string };
}

export async function GET(req: Request, { params }: { params: Promise<{ templeId: string }> }) {
  const requestId = newRequestId();
  const { templeId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.temple.pujas', requestId);
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

    const response = await laravelGet<TemplePujaAssignment[]>(
      actor.token,
      `/temple/profiles/${encodeURIComponent(templeId)}/pujas`,
      query,
      {
        not_found: 'NOT_FOUND',
        TEMPLE_NOT_FOUND: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(
      ok({ data: response.data, meta: response.meta } satisfies TemplePujaListPayload, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Temple pujas are unavailable.', requestId);
  }
}
