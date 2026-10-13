import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { authRateLimiter, ipKey, THROTTLE } from '@/application/auth-throttle';
import { bodyWithinCap } from '@/application/auth-schemas';
import { setSessionCookie } from '@/server/session-cookie';
import type { CustomerSession } from '@navdhya/otp/schemas';

interface RawVerifyBody {
  challengeId?: string;
  challenge_id?: string;
  token?: string;
}

/**
 * POST /api/auth/verify — redeem the signup challenge with the MSG91 widget token, issue a session.
 *
 * Atomically verifies the token against MSG91 in Laravel, creates customer profile/membership,
 * and mints the HMAC customer session.
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  if (!bodyWithinCap(req.headers.get('content-length'))) {
    return Response.json(err('VALIDATION_ERROR', 'Request body too large.', requestId), { status: 413 });
  }

  let raw: unknown;
  try {
    raw = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }

  const body = raw as RawVerifyBody | null;
  const challengeId = body?.challengeId ?? body?.challenge_id;
  const token = body?.token;

  if (!challengeId || typeof challengeId !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challengeId is required.', requestId), { status: 422 });
  }

  if (!token || typeof token !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'token is required.', requestId), { status: 422 });
  }

  const ipDecision = await authRateLimiter.consume(
    ipKey('verify', req.headers),
    THROTTLE.loginIp.limit,
    THROTTLE.loginIp.windowSeconds,
  );
  if (!ipDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many attempts. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(ipDecision.retryAfter) },
    });
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
        headers: { 'Set-Cookie': setSessionCookie(session.token, maxAge) },
      },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}
