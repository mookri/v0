import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { listSavedItems } from '@/application/saved/saved-items';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface LaravelSavedItem {
  id: string;
  ref_type: string;
  ref_id: string;
  saved_at: string | null;
}

interface LaravelSavedPage {
  data: LaravelSavedItem[];
  meta: { page?: { has_more?: boolean; next_cursor?: string | null } };
}

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      const page = await laravelGet<LaravelSavedItem[]>(actor.token, '/customers/saved', { limit: '25' });
      const items = (page.data ?? []).map((item) => ({
        // Interim mapping: titles enrich via catalog in a later slice;
        // the ref id is the stable key until then (parity mirror).
        id: item.id,
        type: item.ref_type,
        title: item.ref_id,
        href: `/pujas/${item.ref_id}`,
      }));
      return Response.json(
        ok(
          {
            items,
            pagination: {
              hasMore: page.meta?.page?.has_more ?? false,
              nextCursor: page.meta?.page?.next_cursor ?? null,
            },
          },
          requestId,
        ),
        { headers: { 'Cache-Control': 'private, max-age=30' } },
      );
    } catch (e) {
      return laravelErrorResponse(e, 'Saved items are unavailable.', requestId);
    }
  }
  const ctx = anonymousContext('customer.saved', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const data = listSavedItems([], () => null);
  return Response.json(ok({ items: data, pagination: { hasMore: false, nextCursor: null } }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=30' },
  });
}
