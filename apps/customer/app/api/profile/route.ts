import { newRequestId, ok, err } from '@/application/api-envelope';
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

  // CustomerProfileController::update accepts only these keys and 422s
  // unknown ones. Email/phone changes go through OTP verification, not
  // this endpoint.
  const allowedFields = ['name', 'display_name', 'locale'];
  const filteredBody: Record<string, unknown> = {};
  for (const key of allowedFields) {
    if (body[key] !== undefined) {
      filteredBody[key] = body[key];
    }
  }

  if (Object.keys(filteredBody).length === 0) {
    return Response.json(
      err('VALIDATION_ERROR', 'No updatable profile fields provided (name, display_name, locale).', requestId),
      { status: 422 },
    );
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