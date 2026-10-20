import { partnerActorResolver, headersToRecord, bearerOrSessionCookie } from '@/application/actor';
import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';

// Temple BFF helpers: actor resolution (signed session), backend bearer
// extraction, and the shared dashboard-section handler — used by the
// /api/temples/* route files (mock default, laravel behind the flag).

const templeActors = partnerActorResolver();

/** Resolved partner actor for the request, or null when unauthenticated. */
export async function templeActor(req: Request) {
  return templeActors.resolve(headersToRecord(req.headers));
}

/** Raw bearer token forwarded to the Laravel backend, or null. */
export function templeToken(req: Request): string | null {
  return bearerOrSessionCookie(req.headers);
}

interface DashboardRouteContext {
  params: Promise<{ templeId: string }>;
}

// Shared shell for /api/temples/:templeId/dashboard/* routes: each file
// wires one section to its backend path and shapes the payload the page
// expects (kernel `data` unwrapped, then re-wrapped by the section).
export async function getDashboardSection(
  req: Request,
  ctx: DashboardRouteContext,
  options: {
    section: string;
    mockPayload: unknown;
    message: string;
    query?: Record<string, string>;
    transform?: (data: unknown, meta: Record<string, unknown>) => unknown;
  },
): Promise<Response> {
  const requestId = newRequestId();
  const { templeId } = await ctx.params;

  if (!laravelEnabled()) {
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok(options.mockPayload, requestId));
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data, meta } = await laravelGet<unknown>(
      templeToken(req),
      `/temple/partner/temples/${encodeURIComponent(templeId)}/dashboard/${options.section}`,
      options.query ?? {},
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    const payload = options.transform ? options.transform(data, meta as Record<string, unknown>) : data;
    return Response.json(ok(payload, requestId));
  } catch (e) {
    return laravelErrorResponse(e, options.message, requestId);
  }
}
