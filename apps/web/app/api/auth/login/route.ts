import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { authRateLimiter, ipKey, THROTTLE } from '@/application/auth-throttle';
import { bodyWithinCap } from '@/application/auth-schemas';
import { setSessionCookie } from '@/server/session-cookie';
import { isFederated } from '@/server/federation';
import type { CustomerSession, OtpChallenge } from '@navdhya/otp/schemas';

interface RawLoginBody {
  challengeId?: string;
  challenge_id?: string;
  token?: string;
  totpCode?: string;
  totp_code?: string;
}

/**
 * POST /api/auth/login — two-phase login redeem.
 *
 * Phase 1: {challengeId, token} verifies the MSG91 widget's JWT access token.
 * Non-MFA accounts mint and return 200 + customer_session + Set-Cookie immediately.
 * MFA-enrolled accounts hold the row and return 202 + mfa_required.
 *
 * Phase 2: {challengeId, totpCode} redeems the held row with the TOTP factor,
 * minting and returning 200 + customer_session + Set-Cookie.
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (isFederated()) {
    // ADR-0020: the issuer is sole — the legacy ceremony does not exist
    // in federated deployments.
    return Response.json(err('NOT_FOUND', 'This deployment authenticates at the auth subdomain.', requestId), { status: 404 });
  }

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

  const body = raw as RawLoginBody | null;
  const challengeId = body?.challengeId ?? body?.challenge_id;
  const token = body?.token;
  const totpCode = body?.totpCode ?? body?.totp_code;

  if (!challengeId || typeof challengeId !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challengeId is required.', requestId), { status: 422 });
  }

  if (!token && !totpCode) {
    return Response.json(err('VALIDATION_ERROR', 'Either token or totpCode is required.', requestId), { status: 422 });
  }

  // BFF-side abuse throttle. Attempt/lock counter is backend-authoritative.
  const ipDecision = await authRateLimiter.consume(
    ipKey('login', req.headers),
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
    const redeemPayload: Record<string, string> = {
      tenant_id: laravelTenant(),
      challenge_id: challengeId,
    };
    if (token) redeemPayload.token = token;
    if (totpCode) redeemPayload.totp_code = totpCode;

    const { data } = await laravelPost<CustomerSession | OtpChallenge>(
      null,
      '/customers/auth/login',
      redeemPayload,
      {},
      { timeout: 'normal', requestId },
    );

    // Two-phase MFA hold: 202 mfa_required
    if ((data as OtpChallenge)?.status === 'mfa_required' || (data as OtpChallenge)?.object === 'otp_challenge') {
      return Response.json(ok(data, requestId), { status: 202 });
    }

    // Success: CustomerSession
    const session = data as CustomerSession;
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
        status: 200,
        headers: { 'Set-Cookie': setSessionCookie(session.token, maxAge) },
      },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}
