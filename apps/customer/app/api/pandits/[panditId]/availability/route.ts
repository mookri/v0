import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface PanditAvailabilitySlot {
  id: string;
  day_of_week: number;
  start_time: string;
  end_time: string;
  slot_duration_minutes: number;
  is_recurring: boolean;
  specific_date: string | null;
}

// Kernel envelope unwrapped here: the BFF payload is the bare slots
// array (parity-customers.md row: pandit availability → AvailabilitySlot[]).
export async function GET(req: Request, { params }: { params: Promise<{ panditId: string }> }) {
  const requestId = newRequestId();
  const { panditId } = await params;

  if (!laravelEnabled()) {
    return Response.json(err('NOT_FOUND', 'Pandit not found.', requestId), { status: 404 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) {
      query[k] = v;
    }

    const { data } = await laravelGet<PanditAvailabilitySlot[]>(
      actor.token,
      `/pandit/profiles/${encodeURIComponent(panditId)}/availability`,
      query,
      {
        not_found: 'NOT_FOUND',
        PANDIT_NOT_FOUND: 'NOT_FOUND',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(
      ok(data, requestId),
      { headers: { 'Cache-Control': 'private, max-age=60' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Pandit availability is unavailable.', requestId);
  }
}