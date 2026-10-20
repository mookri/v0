import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelPatch, laravelDelete, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

async function mutate(req: Request, requestId: string, method: 'PATCH' | 'DELETE' | 'DEFAULT', id: string) {
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  const path = method === 'DEFAULT'
    ? `/customers/addresses/${encodeURIComponent(id)}:default`
    : `/customers/addresses/${encodeURIComponent(id)}`;
  try {
    if (method === 'DELETE') {
      const { data } = await laravelDelete<unknown>(actor.token, path);
      return Response.json(ok(data as Record<string, unknown>, requestId));
    }
    let body: Record<string, unknown> = {};
    if (method === 'PATCH') {
      try {
        body = (await req.json()) as typeof body;
      } catch {
        return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
      }
    }
    const { data } = method === 'PATCH'
      ? await laravelPatch<unknown>(actor.token, path, body)
      : await laravelPost<unknown>(actor.token, path, body);
    return Response.json(ok(data as Record<string, unknown>, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Address is unavailable.', requestId);
  }
}

export async function PATCH(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  return mutate(req, requestId, 'PATCH', (await params).id);
}

export async function DELETE(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  return mutate(req, requestId, 'DELETE', (await params).id);
}
