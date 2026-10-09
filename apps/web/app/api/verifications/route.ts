import { newRequestId, ok } from '@/application/api-envelope';
import { laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { getRequestContext, postContext, rejectRequest } from '@/server/route-guard';
import { verificationCreateSchema } from '@/application/state-schemas';
import {
  authRateLimiter,
  identityKey,
  ipKey,
  idempotencyStore,
  requestFingerprint,
  THROTTLE,
} from '@/application/auth-throttle';

/**
 * GET /api/verifications — list verifications (AAL1 cookie actor, §7).
 */
export async function GET(req: Request) {
  const requestId = newRequestId();
  const ctx = await getRequestContext(req, requestId);
  if (!ctx.ok) return ctx.response;

  try {
    const { data } = await laravelGet(
      ctx.actor.token,
      '/customers/verifications',
      {},
      {},
      { timeout: 'fast', requestId },
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Verifications unavailable.', requestId);
  }
}

/**
 * POST /api/verifications — start a phone/email verification (sends a
 * code), mirroring `/auth/otp` protections:
 *
 * - §1/I3: phone contacts normalized to E.164 by the schema, so the
 *   backend binds to one identity form
 * - §3: per-identity + per-IP throttle → 429 + Retry-After (this sends
 *   SMS/email — same abuse class as OTP issue)
 * - §4: Idempotency-Key with response replay; same key + different
 *   body → 409 (double-tap must not double-send)
 * - §8: cap, 400/422 statuses, X-Request-Id
 */
export async function POST(req: Request) {
  const requestId = newRequestId();
  const ctx = await postContext(req, requestId, {
    schema: verificationCreateSchema,
    throttle: { purpose: 'verification', limit: THROTTLE.otpIp.limit, windowSeconds: THROTTLE.otpIp.windowSeconds },
  });
  if (!ctx.ok) return ctx.response;
  const { channel, value } = ctx.data;

  const idDecision = await authRateLimiter.consume(
    identityKey('verification', value),
    THROTTLE.otpIdentity.limit,
    THROTTLE.otpIdentity.windowSeconds,
  );
  if (!idDecision.allowed) {
    return rejectRequest(requestId, 'RATE_LIMITED', 'Too many codes requested. Please try again later.', 429, {
      'Retry-After': String(idDecision.retryAfter),
    });
  }

  const idemKeyHeader = req.headers.get('idempotency-key');
  let idemKey: string | null = null;
  if (idemKeyHeader) {
    const fingerprint = await requestFingerprint({ channel, value });
    const scope = `verification:${channel}:${value}`;
    const claim = await idempotencyStore.claim(`${scope}:${idemKeyHeader}`, fingerprint);
    if (claim === 'conflict') {
      return rejectRequest(requestId, 'CONFLICT', 'Idempotency-Key reused with a different request.', 409);
    }
    if (claim === 'in-progress') {
      return rejectRequest(requestId, 'RATE_LIMITED', 'A code is already being sent.', 429, {
        'Retry-After': '2',
      });
    }
    if (claim === 'completed') {
      const prior = idempotencyStore.getResult(`${scope}:${idemKeyHeader}`);
      return Response.json(ok(prior, requestId), { status: 202 });
    }
    idemKey = `${scope}:${idemKeyHeader}`;
  }

  try {
    const { data } = await laravelPost(
      ctx.actor.token,
      '/customers/verifications',
      { channel, value },
      {},
      { timeout: 'fast', requestId },
    );
    if (idemKey) await idempotencyStore.complete(idemKey, data);
    return Response.json(ok(data, requestId), { status: 202 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to start verification.', requestId);
  }
}
