import { newRequestId, ok } from '@/application/api-envelope';
import { laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { getRequestContext, postContext } from '@/server/route-guard';
import { savedAddSchema } from '@/application/state-schemas';

interface SavedBackendItem {
  id?: string;
  ref_id?: string;
  refId?: string;
  ref_type?: string;
  refType?: string;
}

/**
 * GET /api/saved — list saved items (AAL1 cookie actor, §7).
 */
export async function GET(req: Request) {
  const requestId = newRequestId();
  const ctx = await getRequestContext(req, requestId);
  if (!ctx.ok) return ctx.response;

  try {
    const { data: backendData } = await laravelGet<{ items?: SavedBackendItem[] } | SavedBackendItem[]>(
      ctx.actor.token,
      '/customers/saved',
      {},
      {},
      { timeout: 'fast', requestId },
    );

    // Handle both array and object-with-items responses
    const rawItems = Array.isArray(backendData) ? backendData : (backendData?.items ?? []);

    // Enrich saved items for BFF consumption (interim title=ref mapping)
    const enrichedItems = rawItems.map((item) => {
      const refId = item.ref_id ?? item.refId ?? '';
      const refType = item.ref_type ?? item.refType ?? 'puja';
      return {
        id: item.id,
        type: refType,
        title: refId,
        href: `/${refType}s/${refId}`,
      };
    });

    return Response.json(ok({ items: enrichedItems }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Saved items unavailable.', requestId);
  }
}

/**
 * POST /api/saved — add a saved item.
 *
 * Contract §8: 1 MB cap, malformed JSON → 400, schema failure → 422.
 * Forwarded keys are exactly `{ref_type, ref_id}` — never a body
 * spread. `title` is not part of the contract: the backend store has
 * no title column, so forwarding one would promise a write that can
 * never round-trip.
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await postContext(req, requestId, { schema: savedAddSchema });
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/customers/saved',
      { ref_type: ctx.data.ref_type, ref_id: ctx.data.ref_id },
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to add saved item.', requestId);
  }
}
