import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { authRateLimiter, ipKey, THROTTLE } from '@/application/auth-throttle';
import { loginRequestSchema, bodyWithinCap, receivedForm } from '@/application/auth-schemas';
import { setSessionCookie } from '@/server/session-cookie';

interface LaravelSession {
  token: string;
  expires_at: string;
  tenant_id: string;
  actor: { user_id: string; session_id: string };
}

/**
 * POST /api/auth/login — redeem a challenge for a session.
 *
 * Contract: auth/p0-contract.md §2 (verify-and-consume is atomic in
 * Laravel; BFF never judges the proof), §7 (session cookie), §8.
 *
 * Migration (§2): canonical field is `proof`; `code` is a temporary
 * alias accepted for ONE release. Which form arrived is logged with
 * the requestId so the removal milestone can be verified.
 *
 * P0 change: the old `identityId + proof` fan-out (OTP request →
 * redeem, 2 sequential backend calls) is REMOVED. Callers must request
 * a challenge via /auth/otp first.
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

  const parsed = loginRequestSchema.safeParse(raw);
  if (!parsed.success) {
    return Response.json(
      err('VALIDATION_ERROR', parsed.error.issues[0]?.message ?? 'Invalid request.', requestId),
      { status: 422 },
    );
  }
  const { challengeId, proof, code, totpCode } = parsed.data;

  // Migration telemetry (contract §2): record the vocabulary in use.
  const form = receivedForm({ proof, code });
  if (form === 'code') {
    console.warn(`[auth] deprecated 'code' field received on /auth/login (requestId=${requestId}) — migrate to 'proof'`);
  }

  // BFF-side abuse throttle. The attempt/lock counter itself is
  // backend-authoritative (I8, I9) — this only bounds request volume.
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
    // Single redeem call — verify-and-consume happens atomically
    // inside Laravel (contract §2). No fan-out.
    const redeem: Record<string, string> = {
      tenant_id: laravelTenant(),
      challenge_id: challengeId,
      code: proof ?? code ?? '',
    };
    if (totpCode !== undefined) redeem['totp_code'] = totpCode;

    const { data: session } = await laravelPost<LaravelSession>(
      null,
      '/customers/auth/login',
      redeem,
      {},
      { timeout: 'normal', requestId },
    );

    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(
      ok(
        { sessionId: session.actor.session_id, expiresAt: session.expires_at, receivedForm: form },
        requestId,
      ),
      { headers: { 'Set-Cookie': setSessionCookie(session.token, maxAge) } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}
