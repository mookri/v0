import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

interface LaravelChallenge {
  challenge_id: string;
  expires_in: number;
}

interface LaravelSession {
  token: string;
  expires_at: string;
  tenant_id: string;
  actor: { user_id: string; session_id: string };
}

// Wired login (CUSTOMER_BACKEND=laravel):
// - If challengeId provided: verifies OTP (+ optional TOTP) and creates session
// - Else if identityId + proof: fans out to backend OTP + login (backwards compat)
// Returns { sessionId, expiresAt }, sets HttpOnly session cookie.
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { identityId?: string; challengeId?: string; proof?: string; totpCode?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  try {
    const tenant = laravelTenant();

    let session: LaravelSession;

    if (body.challengeId) {
      // Step 2 only: redeem existing challenge
      const redeem: Record<string, string> = {
        tenant_id: tenant,
        challenge_id: body.challengeId,
        code: body.proof ?? '',
      };
      if (body.totpCode !== undefined) {
        redeem['totp_code'] = body.totpCode;
      }

      const { data } = await laravelPost<LaravelSession>(null, '/customers/auth/login', redeem);
      session = data;
    } else if (body.identityId && typeof body.proof === 'string') {
      // Backwards compat: fan out (request OTP then immediately redeem)
      const { data: challenge } = await laravelPost<LaravelChallenge>(null, '/customers/auth/otp', {
        tenant_id: tenant,
        purpose: 'login',
        identity: body.identityId,
      });

      const redeem: Record<string, string> = {
        tenant_id: tenant,
        challenge_id: challenge.challenge_id,
        code: body.proof,
      };
      if (body.totpCode !== undefined) {
        redeem['totp_code'] = body.totpCode;
      }

      const { data } = await laravelPost<LaravelSession>(null, '/customers/auth/login', redeem);
      session = data;
    } else {
      return Response.json(err('VALIDATION_ERROR', 'Either challengeId or identityId+proof required.', requestId), { status: 422 });
    }

    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(
      ok({ sessionId: session.actor.session_id, expiresAt: session.expires_at }, requestId),
      { headers: { 'Set-Cookie': `navdhya-session=${session.token}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${maxAge}` } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}