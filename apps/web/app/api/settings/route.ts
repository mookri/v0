import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { customerSettingsSections, validateSettingsUpdate } from '@/application/settings/settings';
import { laravelEnabled, laravelGet, laravelPatch, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { postContext } from '@/server/route-guard';
import { settingsUpdateSchema } from '@/application/state-schemas';

/**
 * GET /api/settings — read settings (AAL1 cookie actor, §7).
 *
 * Dev fallback (backend not wired) keeps serving the section catalog
 * via `anonymousContext` — no security decision is made there (I18).
 */
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      // Backend exposes key-values; the sections shape stays a BFF
      // presentation concern until the F-catalog enrichment slice.
      const { data } = await laravelGet<{ settings: Record<string, unknown> }>(
        actor.token,
        '/customers/settings',
        {},
        {},
        { timeout: 'fast', requestId },
      );
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

/**
 * PATCH /api/settings — update settings.
 *
 * Two-layer validation: this schema guarantees a plain object (§8
 * statuses: malformed → 400, wrong shape → 422), then the BFF-owned
 * closed key set (`validateSettingsUpdate`) mirrors the backend's
 * `CustomerSetting::KEYS` — unknown keys are rejected with a 422 here,
 * never forwarded to Laravel. Forwarded values are FLAT: the backend
 * diffs top-level request keys against its key set (a `settings`
 * wrapper reads as the unknown key "settings").
 */
export async function PATCH(req: Request) {
  const requestId = newRequestId();
  const ctx = await postContext(req, requestId, { schema: settingsUpdateSchema });
  if (!ctx.ok) return ctx.response;

  const validation = validateSettingsUpdate(ctx.data);
  if (!validation.ok) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid settings.', requestId), { status: 422 });
  }

  try {
    const { data } = await laravelPatch<{ settings: Record<string, unknown> }>(
      ctx.actor.token,
      '/customers/settings',
      validation.values,
      {},
      { timeout: 'normal', requestId },
    );
    return Response.json(ok({ sections: customerSettingsSections, values: data.settings ?? {} }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Settings update failed.', requestId);
  }
}
