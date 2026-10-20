import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelDelete, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { templeActor, templeToken } from '@/server/temples';

interface TempleManager {
  id: string;
  temple_id: string;
  user_id: string;
  role: string;
  invited_at: string;
  accepted_at: string | null;
}

type RouteContext = { params: Promise<{ templeId: string }> };

export async function GET(req: Request, ctx: RouteContext) {
  const requestId = newRequestId();
  const { templeId } = await ctx.params;

  if (!laravelEnabled()) {
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok({ data: [], meta: { fallback: true } }, requestId));
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelGet<TempleManager[]>(
      templeToken(req),
      `/temple/partner/temples/${encodeURIComponent(templeId)}/managers`,
      {},
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data ?? [], requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Temple managers are unavailable.', requestId);
  }
}

export async function POST(req: Request, ctx: RouteContext) {
  const requestId = newRequestId();
  const { templeId } = await ctx.params;

  const body = (await req.json().catch(() => ({}))) as { user_id?: string; role?: string };

  if (!laravelEnabled()) {
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok({ id: 'mock', temple_id: templeId, user_id: body.user_id ?? '', role: body.role ?? 'viewer', invited_at: new Date().toISOString(), accepted_at: null }, requestId), { status: 201 });
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelPost<TempleManager>(
      templeToken(req),
      `/temple/partner/temples/${encodeURIComponent(templeId)}/managers`,
      { user_id: body.user_id, role: body.role },
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to invite manager.', requestId);
  }
}

