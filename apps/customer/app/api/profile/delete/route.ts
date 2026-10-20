import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired profile delete (CUSTOMER_BACKEND=laravel): POST /api/profile/delete
// Requires authenticated user + fresh OTP proof
// Soft deletes profile (sets deleted_at), suspends membership, records audit
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { challengeId?: string; code?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  if (!body.challengeId || !body.code) {
    return Response.json(err('VALIDATION_ERROR', 'challengeId and code are required for account deletion.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPost<unknown>(
      actor.token,
      '/customers/profile:delete',
      { challenge_id: body.challengeId, token: body.code }
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Profile deletion failed.', requestId);
  }
}