import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { buildSessionCookie } from '@/server/backend-session';
import type { CustomerSession } from '@navdhya/otp/schemas';

interface VerifyBody {
  challengeId?: string;
  challenge_id?: string;
  token?: string;
}

export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(
      err('DEPENDENCY_UNAVAILABLE', 'Verification service unavailable in mock mode.', requestId),
      { status: 503 },
    );
  }

  let body: VerifyBody = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  const challengeId = body.challengeId ?? body.challenge_id;
  const token = body.token;

  if (!challengeId || typeof challengeId !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challengeId is required.', requestId), { status: 422 });
  }

  if (!token || typeof token !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'token is required.', requestId), { status: 422 });
  }

  try {
    const { data: session } = await laravelPost<CustomerSession>(
      null,
      '/customers/auth/verify',
      {
        tenant_id: laravelTenant(),
        challenge_id: challengeId,
        token,
      },
      {},
      { timeout: 'normal', requestId },
    );

    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(
      ok(
        {
          ...session,
          sessionId: session.actor.session_id,
          expiresAt: session.expires_at,
        },
        requestId,
      ),
      {
        status: 201,
        headers: { 'Set-Cookie': buildSessionCookie(session.token, maxAge) },
      },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}
