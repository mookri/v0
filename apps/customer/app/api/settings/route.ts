import { newRequestId, ok, err } from '@/application/api-envelope';
import { customerSettingsSections, DEFAULT_SETTINGS, validateSettingsUpdate } from '@/application/settings/settings';
import { laravelEnabled, laravelGet, laravelPatch, laravelErrorResponse } from '@/adapters/backend/laravel-client';
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
  return Response.json(ok({ sections: customerSettingsSections, values: DEFAULT_SETTINGS }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=60' },
  });
}

export async function PATCH(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: Record<string, unknown> = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  // Validate against known keys (closed key set)
  const validation = validateSettingsUpdate(body);
  if (!validation.ok) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid settings.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPatch<{ settings: Record<string, unknown> }>(
      actor.token,
      '/customers/settings',
      validation.values
    );
    return Response.json(ok({ sections: customerSettingsSections, values: data.settings ?? {} }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Settings update failed.', requestId);
  }
}