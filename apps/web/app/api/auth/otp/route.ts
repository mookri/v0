import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import {
  authRateLimiter,
  identityKey,
  ipKey,
  idempotencyStore,
  requestFingerprint,
  THROTTLE,
} from '@/application/auth-throttle';
import { otpRequestSchema, bodyWithinCap } from '@/application/auth-schemas';
import { isFederated } from '@/server/federation';

interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;
}

/**
 * POST /api/auth/otp — request an OTP challenge.
 *
 * Contract: auth/p0-contract.md §1 (one active challenge per
 * identity+purpose — backend invalidates the prior one), §4
 * (idempotency), §8 (status contract).
 *
 * P0 BFF duties: schema, throttle, idempotency. The backend remains
 * authoritative for challenge state (§0).
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

  const parsed = otpRequestSchema.safeParse(raw);
  if (!parsed.success) {
    return Response.json(err('VALIDATION_ERROR', parsed.error.issues[0]?.message ?? 'Invalid request.', requestId), { status: 422 });
  }
  const { identity, purpose } = parsed.data;

  // Throttle: IP first (cheap, no identity work), then per-identity.
  // These are abuse limits only — Laravel owns the real attempt lock (I8).
  const ipDecision = await authRateLimiter.consume(ipKey('otp', req.headers), THROTTLE.otpIp.limit, THROTTLE.otpIp.windowSeconds);
  if (!ipDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many requests. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(ipDecision.retryAfter) },
    });
  }

  const idDecision = await authRateLimiter.consume(identityKey('otp', identity), THROTTLE.otpIdentity.limit, THROTTLE.otpIdentity.windowSeconds);
  if (!idDecision.allowed) {
    return Response.json(err('RATE_LIMITED', 'Too many codes requested. Please try again later.', requestId), {
      status: 429,
      headers: { 'Retry-After': String(idDecision.retryAfter) },
    });
  }

  // Idempotency (§4): double-tap must not double-send SMS.
  const idemKeyHeader = req.headers.get('idempotency-key');
  let idemKey: string | null = null;
  if (idemKeyHeader) {
    const fingerprint = await requestFingerprint(raw);
    const scope = `otp:${identity}:${purpose}`;
    const claim = await idempotencyStore.claim(`${scope}:${idemKeyHeader}`, fingerprint);
    if (claim === 'conflict') {
      return Response.json(err('CONFLICT', 'Idempotency-Key reused with a different request.', requestId), { status: 409 });
    }
    if (claim === 'in-progress') {
      return Response.json(err('RATE_LIMITED', 'A code is already being sent.', requestId), {
        status: 429,
        headers: { 'Retry-After': '2' },
      });
    }
    if (claim === 'completed') {
      // Same fingerprint already succeeded — replay the original
      // response (the caller still needs the challengeId to proceed).
      const prior = idempotencyStore.getResult(`${scope}:${idemKeyHeader}`);
      return Response.json(ok(prior, requestId), { status: 202 });
    }
    idemKey = `${scope}:${idemKeyHeader}`;
  }

  try {
    const { data: challenge } = await laravelPost<LaravelChallenge>(
      null,
      '/customers/auth/otp',
      { tenant_id: laravelTenant(), purpose, identity },
      {},
      { timeout: 'fast', requestId },
    );

    const payload = {
      challengeId: challenge.challenge_id,
      expiresIn: challenge.expires_in,
      retryAfter: idDecision.retryAfter,
    };
    if (idemKey) await idempotencyStore.complete(idemKey, payload);

    return Response.json(ok(payload, requestId), { status: 202 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to send code.', requestId);
  }
}
