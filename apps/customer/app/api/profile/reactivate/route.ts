import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';

// Wired profile reactivate (CUSTOMER_BACKEND=laravel): POST /api/profile:reactivate
// Public endpoint - suspended members cannot pass tenant gate, so no auth required
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
    return Response.json(err('VALIDATION_ERROR', 'challengeId and code are required for reactivation.', requestId), { status: 422 });
  }

  try {
    const { data } = await laravelPost<unknown>(
      null, // No auth - public endpoint
      '/customers/profile:reactivate',
      { challenge_id: body.challengeId, code: body.code }
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Profile reactivation failed.', requestId);
  }
}