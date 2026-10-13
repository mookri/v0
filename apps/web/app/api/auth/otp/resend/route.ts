import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { authRateLimiter, ipKey, THROTTLE } from '@/application/auth-throttle';
import { bodyWithinCap } from '@/application/auth-schemas';
import type { OtpResend } from '@navdhya/otp/schemas';


/**
 * POST /api/auth/otp/resend — server-gated resend.
 *
 * Pure proxy to Laravel POST /customers/auth/otp/resend (no-store).
 * Laravel validates the compounding schedule (60s → 180s → 540s, max 3)
 * and returns 202 {object: 'otp_resend', channel, channels, resends_left, next_allowed_at}.
 * The browser only calls widget.retryOtp(channel) on 202.
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

  const body = raw as { challengeId?: string; challenge_id?: string } | null;
  const challengeId = body?.challengeId ?? body?.challenge_id;

  if (!challengeId || typeof challengeId !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challengeId is required.', requestId), { status: 422 });
  }

  // IP throttle for abuse bounding
  const ipDecision = await authRateLimiter.consume(
    ipKey('otp-resend', req.headers),
    THROTTLE.otpIp.limit,
    THROTTLE.otpIp.windowSeconds,
  );
  if (!ipDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many resend requests. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(ipDecision.retryAfter) },
    });
  }

  try {
    const { data: resendResult } = await laravelPost<OtpResend>(
      null,
      '/customers/auth/otp/resend',
      {
        tenant_id: laravelTenant(),
        challenge_id: challengeId,
      },
      {},
      { timeout: 'fast', requestId },
    );

    return Response.json(ok(resendResult, requestId), {
      status: 202,
      headers: {
        'Cache-Control': 'no-store, max-age=0',
      },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to resend code.', requestId);
  }
}
