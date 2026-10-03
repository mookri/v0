import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired verification confirm (CUSTOMER_BACKEND=laravel): POST /api/verifications/confirm
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { id?: string; code?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  if (!body.id || !body.code) {
    return Response.json(err('VALIDATION_ERROR', 'id and code are required.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPost<unknown>(
      actor.token,
      `/customers/verifications/${body.id}:confirm`,
      { code: body.code }
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Verification confirmation failed.', requestId);
  }
}