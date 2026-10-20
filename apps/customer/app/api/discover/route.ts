import { newRequestId, ok } from '@/application/api-envelope';
import { getDiscoverFeed } from '@/application/discover/discover-feed';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      // Kernel envelope wraps the feed object; the BFF unwraps it so the
      // client shape stays `{recommended, popular, recent}` (parity mirror).
      const { data: feed } = await laravelGet<unknown>(actor.token, '/customers/discover');
      return Response.json(ok(feed as Record<string, unknown>, requestId), {
        headers: { 'Cache-Control': 'private, max-age=60' },
      });
    } catch (e) {
      return laravelErrorResponse(e, 'Discover feed is unavailable.', requestId);
    }
  }
  const data = getDiscoverFeed({});
  return Response.json(ok(data, requestId), {
    headers: { 'Cache-Control': 'private, max-age=60' },
  });
}
