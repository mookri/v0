import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { laravelEnabled, laravelGet, laravelPatch, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      const { data: profile } = await laravelGet<unknown>(actor.token, '/customers/profile');
      return Response.json(ok({ profile }, requestId), {
        headers: { 'Cache-Control': 'private, max-age=30' },
      });
    } catch (e) {
      return laravelErrorResponse(e, 'Profile is unavailable.', requestId);
    }
  }
  const ctx = anonymousContext('customer.profile', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ profile: null }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=30' },
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

  // Validate allowed fields (backend has strict field allowlist)
  const allowedFields = ['name', 'email', 'phone', 'city', 'country', 'timezone', 'languages'];
  const filteredBody: Record<string, unknown> = {};
  for (const key of allowedFields) {
    if (body[key] !== undefined) {
      filteredBody[key] = body[key];
    }
  }

  if (Object.keys(filteredBody).length === 0) {
    return Response.json(err('VALIDATION_ERROR', 'No valid fields provided.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data: profile } = await laravelPatch<unknown>(actor.token, '/customers/profile', filteredBody);
    return Response.json(ok({ profile }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Profile update failed.', requestId);
  }
}