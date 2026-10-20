import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPut, laravelDelete, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { templeActor, templeToken } from '@/server/temples';

type RouteContext = { params: Promise<{ templeId: string; managerId: string }> };

export async function PUT(req: Request, ctx: RouteContext) {
  const requestId = newRequestId();
  const { templeId, managerId } = await ctx.params;

  const body = (await req.json().catch(() => ({}))) as { role?: string };

  if (!laravelEnabled()) {
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok({ id: managerId, temple_id: templeId, role: body.role ?? 'viewer' }, requestId));
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    const { data } = await laravelPut<unknown>(
      templeToken(req),
      `/temple/partner/temples/${encodeURIComponent(templeId)}/managers/${encodeURIComponent(managerId)}`,
      { role: body.role },
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to update manager role.', requestId);
  }
}

export async function DELETE(req: Request, ctx: RouteContext) {
  const requestId = newRequestId();
  const { templeId, managerId } = await ctx.params;

  if (!laravelEnabled()) {
    const actor = await templeActor(req);
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    return Response.json(ok({ deleted: true }, requestId));
  }

  const actor = await templeActor(req);
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });

  try {
    await laravelDelete<unknown>(
      templeToken(req),
      `/temple/partner/temples/${encodeURIComponent(templeId)}/managers/${encodeURIComponent(managerId)}`,
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        validation_failed: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok({ deleted: true }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to remove manager.', requestId);
  }
}
