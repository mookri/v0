import { newRequestId, ok, err } from '@/application/api-envelope';
import { login } from '@navdhya/identity/src/application/token-session';
import { authIdentities, authSessions, authVerifier, authTokenSecret, seedDevIdentity } from '@/server/auth';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { buildSessionCookie } from '@/server/backend-session';
import type { CustomerSession, OtpChallenge } from '@navdhya/otp/schemas';

interface LoginBody {
  challengeId?: string;
  challenge_id?: string;
  token?: string;
  totpCode?: string;
  totp_code?: string;
  // Credential mock login fallback
  identityId?: string;
  proof?: string;
}

export async function POST(req: Request) {
  const requestId = newRequestId();
  seedDevIdentity();

  let body: LoginBody = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  const challengeId = body.challengeId ?? body.challenge_id;
  const token = body.token;
  const totpCode = body.totpCode ?? body.totp_code;

  // Single-proxy OTP login (token and/or totpCode)
  if (challengeId || token) {
    if (!laravelEnabled()) {
      return Response.json(
        err('DEPENDENCY_UNAVAILABLE', 'OTP authentication is unavailable in mock mode.', requestId),
        { status: 503 },
      );
    }

    if (!challengeId) {
      return Response.json(err('VALIDATION_ERROR', 'challengeId is required.', requestId), { status: 422 });
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
          headers: { 'Set-Cookie': buildSessionCookie(session.token, maxAge) },
        },
      );
    } catch (e) {
      return laravelErrorResponse(e, 'Invalid credentials.', requestId);
    }
  }

  // Legacy credential mock login path (when identityId + proof provided)
  if (!body.identityId || typeof body.proof !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'challengeId or identityId and proof are required.', requestId), { status: 422 });
  }

  if (laravelEnabled()) {
    return Response.json(err('VALIDATION_ERROR', 'Use OTP login with challengeId and token.', requestId), { status: 422 });
  }

  const nowIso = new Date().toISOString();
  try {
    const { session, token: sessionToken } = await login(
      { identities: authIdentities, sessions: authSessions, verifier: authVerifier, tokenSecret: authTokenSecret() },
      {
        identityId: body.identityId,
        proof: body.proof,
        audience: 'customer',
        roles: ['customer'],
        sessionId: `sess_${requestId}`,
        tokenTtlSeconds: 3600,
        sessionTtlSeconds: 86400,
        nowIso,
      },
    );
    return Response.json(ok({ token: sessionToken, sessionId: session.id, expiresAt: session.expiresAt }, requestId));
  } catch (e) {
    const code = e instanceof Error ? e.message : '';
    if (code === 'IDENTITY_INACTIVE' || code === 'CREDENTIAL_INVALID') {
      return Response.json(err('UNAUTHENTICATED', 'Invalid credentials.', requestId), { status: 401 });
    }
    throw e;
  }
}
