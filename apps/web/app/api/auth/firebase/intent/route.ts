import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { authRateLimiter, identityKey, ipKey, THROTTLE } from '@/application/auth-throttle';
import { firebaseIntentSchema, bodyWithinCap } from '@/application/auth-schemas';

interface LaravelPhoneIntent {
  intent_id: string;
  expires_in: number;
}

/**
 * POST /api/auth/firebase/intent — record the single-use phone-login
 * intent (S50 kit; backend route customers/auth/firebase/intent).
 *
 * P0 BFF duties: schema + throttle. No idempotency here: this endpoint
 * only records the intent row — the SMS fires client-side at
 * signInWithPhoneNumber, so a double-tap cannot double-send; the
 * backend's own throttle bounds the rows.
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  if (!bodyWithinCap(req.headers.get('content-length'))) {
    return Response.json(err('VALIDATION_ERROR', 'Request too large.', requestId), { status: 413 });
  }

  let body: { phone?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  const parsed = firebaseIntentSchema.safeParse(body);
  if (!parsed.success) {
    return Response.json(err('VALIDATION_ERROR', 'Enter a valid mobile number.', requestId), { status: 422 });
  }
  const { phone } = parsed.data;

  // Abuse envelope — same budgets as the OTP send; the backend
  // throttle (throttle:10,1) stays the real lock.
  const ipDecision = await authRateLimiter.consume(
    ipKey('firebase-intent', req.headers),
    THROTTLE.otpIp.limit,
    THROTTLE.otpIp.windowSeconds,
  );
  if (!ipDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many requests. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(ipDecision.retryAfter) },
    });
  }

  const idDecision = await authRateLimiter.consume(
    identityKey('firebase-intent', phone),
    THROTTLE.otpIdentity.limit,
    THROTTLE.otpIdentity.windowSeconds,
  );
  if (!idDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many requests. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(idDecision.retryAfter) },
    });
  }

  try {
    const { data: intent } = await laravelPost<LaravelPhoneIntent>(
      null,
      '/customers/auth/firebase/intent',
      { tenant_id: laravelTenant(), phone },
      {},
      { timeout: 'fast', requestId },
    );

    return Response.json(
      ok({ intentId: intent.intent_id, expiresIn: intent.expires_in }, requestId),
      { status: 202 },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to start verification.', requestId);
  }
}
