import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import {
  authRateLimiter,
  ipKey,
  idempotencyStore,
  requestFingerprint,
  THROTTLE,
} from '@/application/auth-throttle';
import { signupRequestSchema, bodyWithinCap } from '@/application/auth-schemas';
import { isFederated } from '@/server/federation';

interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;
}

/**
 * POST /api/auth/signup — signup intent + OTP challenge.
 *
 * Contract: auth/p0-contract.md §1 (single active challenge),
 * §4 (Idempotency-Key, fingerprint conflict → 409), §8.
 *
 * P0: E.164 normalization, IP throttle, idempotent double-submit
 * (gate: "Double-submit signup → single effect").
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  if (isFederated()) {
    // ADR-0020: the issuer is sole — the legacy ceremony does not exist
    // in federated deployments (verified proof auto-provisions there).
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

  const parsed = signupRequestSchema.safeParse(raw);
  if (!parsed.success) {
    return Response.json(
      err('VALIDATION_ERROR', parsed.error.issues[0]?.message ?? 'Invalid request.', requestId),
      { status: 422 },
    );
  }
  const { phone, name } = parsed.data;

  const ipDecision = await authRateLimiter.consume(
    ipKey('signup', req.headers),
    THROTTLE.signupIp.limit,
    THROTTLE.signupIp.windowSeconds,
  );
  if (!ipDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many attempts. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(ipDecision.retryAfter) },
    });
  }

  const idemKeyHeader = req.headers.get('idempotency-key');
  let idemKey: string | null = null;
  if (idemKeyHeader) {
    const fingerprint = await requestFingerprint(raw);
    const scope = `signup:${phone}`;
    const claim = await idempotencyStore.claim(`${scope}:${idemKeyHeader}`, fingerprint);
    if (claim === 'conflict') {
      // Contract §4: never silently reuse a response for a different body.
      return Response.json(err('CONFLICT', 'Idempotency-Key reused with a different request.', requestId), {
        status: 409,
      });
    }
    if (claim === 'in-progress') {
      return Response.json(err('CONFLICT', 'A signup is already in progress.', requestId), {
        status: 409,
        headers: { 'Retry-After': '2' },
      });
    }
    if (claim === 'completed') {
      const prior = idempotencyStore.getResult(`${scope}:${idemKeyHeader}`);
      return Response.json(ok(prior, requestId), { status: 202 });
    }
    idemKey = `${scope}:${idemKeyHeader}`;
  }

  try {
    const { data: challenge } = await laravelPost<LaravelChallenge>(
      null,
      '/customers/auth/signup',
      { tenant_id: laravelTenant(), phone, name },
      {},
      { timeout: 'fast', requestId },
    );

    const payload = {
      challengeId: challenge.challenge_id,
      expiresIn: challenge.expires_in,
      retryAfter: ipDecision.retryAfter,
    };
    if (idemKey) await idempotencyStore.complete(idemKey, payload);

    return Response.json(ok(payload, requestId), { status: 202 });
  } catch (e) {
    return laravelErrorResponse(e, 'Signup is unavailable.', requestId);
  }
}
