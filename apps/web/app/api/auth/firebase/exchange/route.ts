import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { CustomerApiError } from '@/adapters/backend/customer-api-client';
import { authRateLimiter, ipKey, THROTTLE } from '@/application/auth-throttle';
import { firebaseExchangeSchema, bodyWithinCap } from '@/application/auth-schemas';
import { setSessionCookie } from '@/server/session-cookie';

interface LaravelSession {
  token: string;
  expires_at: string;
  tenant_id: string;
  actor: { user_id: string; session_id: string };
}

/**
 * POST /api/auth/firebase/exchange — verify the Firebase ID token and
 * mint the session (S50 kit; backend customers/auth/firebase/exchange).
 *
 * P0 BFF duties: schema + throttle + cookie. The backend verifies the
 * token, binds phone ↔ intent, enforces eligibility and MFA, and stays * authoritative; every credential-shaped failure arrives here as the
 * one collapsed 401 (no oracle on account/MFA state).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  if (!bodyWithinCap(req.headers.get('content-length'))) {
    return Response.json(err('VALIDATION_ERROR', 'Request too large.', requestId), { status: 413 });
  }

  let body: { intentId?: string; idToken?: string; totpCode?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  const parsed = firebaseExchangeSchema.safeParse(body);
  if (!parsed.success) {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  const { intentId, idToken, totpCode } = parsed.data;

  // Abuse envelope mirrors login (backend owns the real lock).
  const ipDecision = await authRateLimiter.consume(
    ipKey('firebase-exchange', req.headers),
    THROTTLE.loginIp.limit,
    THROTTLE.loginIp.windowSeconds,
  );
  if (!ipDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many requests. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(ipDecision.retryAfter) },
    });
  }

  try {
    const payload: Record<string, string> = {
      tenant_id: laravelTenant(),
      intent_id: intentId,
      id_token: idToken,
    };
    if (totpCode !== undefined) payload.totp_code = totpCode;

    const { data: session } = await laravelPost<LaravelSession>(
      null,
      '/customers/auth/firebase/exchange',
      payload,
      {},
      { timeout: 'normal', requestId },
    );

    // Contract §7: raw backend bearer in an HttpOnly cookie; the body
    // carries no token.
    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(
      ok({ sessionId: session.actor.session_id, expiresAt: session.expires_at }, requestId),
      { headers: { 'Set-Cookie': setSessionCookie(session.token, maxAge) } },
    );
  } catch (e) {
    // The typed 503 (Firebase admin creds absent/outage) must not read
    // as a credentials problem — the status already says "later".
    const message =
      e instanceof CustomerApiError && e.code === 'DEPENDENCY_UNAVAILABLE'
        ? 'Sign-in is temporarily unavailable. Please try again shortly.'
        : 'Invalid credentials.';
    return laravelErrorResponse(e, message, requestId);
  }
}
