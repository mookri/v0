import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

interface LaravelSession {
  token: string;
  expires_at: string;
  tenant_id: string;
  actor: { user_id: string; session_id: string };
}

// Wired signup verify (CUSTOMER_BACKEND=laravel): redeems the signup
// challenge, sets the session cookie, and returns the session (no
// token to the client — same rule as login). Without the flag the
// surface is unauthenticated-only.
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { challengeId?: string; code?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  if (!body.challengeId || typeof body.code !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challengeId and code are required.', requestId), { status: 422 });
  }

  try {
    const tenant = laravelTenant();
    const { data: session } = await laravelPost<LaravelSession>(null, '/customers/auth/verify', {
      tenant_id: tenant,
      challenge_id: body.challengeId,
      code: body.code,
    });

    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(ok({ sessionId: session.actor.session_id, expiresAt: session.expires_at }, requestId), {
      status: 201,
      headers: { 'Set-Cookie': `navdhya-session=${session.token}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${maxAge}` },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}