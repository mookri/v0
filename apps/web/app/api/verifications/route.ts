import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired verifications list (CUSTOMER_BACKEND=laravel): GET /api/verifications
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<unknown>(actor.token, '/customers/verifications');
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Verifications unavailable.', requestId);
  }
}

// Wired verification create (CUSTOMER_BACKEND=laravel): POST /api/verifications
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { type?: 'phone' | 'email'; contact?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  if (!body.type || !body.contact) {
    return Response.json(err('VALIDATION_ERROR', 'type and contact are required.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPost<unknown>(
      actor.token,
      '/customers/verifications',
      { type: body.type, contact: body.contact }
    );
    return Response.json(ok(data, requestId), { status: 202 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to start verification.', requestId);
  }
}