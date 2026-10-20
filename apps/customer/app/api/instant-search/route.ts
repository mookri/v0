import { newRequestId, ok } from '@/application/api-envelope';
import type { InstantSearchResult } from '@/application/instant-search';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Instant-book search (B2): date + slot (+ optional puja) → assignable
// pandits and open temple slots. The backend is the sole authority for
// assignability (availability containment + conflict gate); this route
// only forwards the window and degrades to an empty result set when the
// backend is unwired — never an invented pandit or slot. Result shape is
// the shared application type so page, route and tests agree.

function emptyResult(): InstantSearchResult {
  return {
    object: 'instant_search',
    date: '',
    start: '',
    end: '',
    puja_id: null,
    pandits: [],
    temple_slots: [],
  };
}

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok(emptyResult(), requestId), {
      headers: { 'Cache-Control': 'private, max-age=0' },
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

    const result = await laravelGet<InstantSearchResult>(
      actor.token,
      '/booking/instant-search',
      query,
      { validation_failed: 'VALIDATION_ERROR' },
    );

    return Response.json(ok(result.data ?? emptyResult(), requestId), {
      headers: { 'Cache-Control': 'private, max-age=0' },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Instant search is unavailable.', requestId);
  }
}
