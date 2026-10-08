import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { authRateLimiter, ipKey, THROTTLE } from '@/application/auth-throttle';
import { verifyRequestSchema, bodyWithinCap } from '@/application/auth-schemas';
import { setSessionCookie } from '@/server/session-cookie';

interface LaravelSession {
  token: string;
  expires_at: string;
  tenant_id: string;
  actor: { user_id: string; session_id: string };
}

/**
 * POST /api/auth/verify — redeem the signup challenge, issue a session.
 *
 * Contract: auth/p0-contract.md §2 (atomic verify-and-consume in
 * Laravel), §6 (session issued only after authoritative verification,
 * I14), §7 (cookie policy), §8.
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

  const parsed = verifyRequestSchema.safeParse(raw);
  if (!parsed.success) {
    return Response.json(
      err('VALIDATION_ERROR', parsed.error.issues[0]?.message ?? 'Invalid request.', requestId),
      { status: 422 },
    );
  }
  const { challengeId, code } = parsed.data;

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
    const { data: session } = await laravelPost<LaravelSession>(
      null,
      '/customers/auth/verify',
      { tenant_id: laravelTenant(), challenge_id: challengeId, code },
      {},
      { timeout: 'normal', requestId },
    );

    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(
      ok({ sessionId: session.actor.session_id, expiresAt: session.expires_at }, requestId),
      { status: 201, headers: { 'Set-Cookie': setSessionCookie(session.token, maxAge) } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}
