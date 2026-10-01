import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { customerSettingsSections } from '@/application/settings/settings';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      // Backend exposes key-values; the sections shape stays a BFF
      // presentation concern until the F-catalog enrichment slice.
      const { data } = await laravelGet<{ settings: Record<string, unknown> }>(actor.token, '/customers/settings');
      return Response.json(ok({ sections: customerSettingsSections, values: data.settings ?? {} }, requestId), {
        headers: { 'Cache-Control': 'private, max-age=60' },
      });
    } catch (e) {
      return laravelErrorResponse(e, 'Settings are unavailable.', requestId);
    }
  }
  const ctx = anonymousContext('customer.settings', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ sections: customerSettingsSections }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=60' },
  });
}
