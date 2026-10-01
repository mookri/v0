import { newRequestId, ok, err } from '@/application/api-envelope';
import { login } from '@navdhya/identity/src/application/token-session';
import { authIdentities, authSessions, authVerifier, authTokenSecret, seedDevIdentity } from '@/server/auth';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { buildSessionCookie } from '@/server/backend-session';

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

export async function POST(req: Request) {
  const requestId = newRequestId();
  seedDevIdentity();
  let body: { identityId?: string; proof?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  if (!body.identityId || typeof body.proof !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'identityId and proof are required.', requestId), { status: 422 });
  }
  if (laravelEnabled()) {
    return laravelLogin(body.identityId, body.proof, requestId);
  }
  const nowIso = new Date().toISOString();
  try {
    const { session, token } = await login(
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
    return Response.json(ok({ token, sessionId: session.id, expiresAt: session.expiresAt }, requestId));
  } catch (e) {
    // Known domain failures are auth failures; anything else propagates to
    // the framework 500 (per BFF convention: no explicit 500 envelopes).
    const code = e instanceof Error ? e.message : '';
    if (code === 'IDENTITY_INACTIVE' || code === 'CREDENTIAL_INVALID') {
      return Response.json(err('UNAUTHENTICATED', 'Invalid credentials.', requestId), { status: 401 });
    }
    throw e;
  }
}

// Wired login (CUSTOMER_BACKEND=laravel): the single-call BFF shape fans
// out to the backend two-step (otp challenge with the proof as code,
// then redeem). The bearer goes to an HttpOnly cookie — never to the
// client — so the wired response omits `token` by design (parity mirror
// records the delta); deactivated accounts collapse to 401 like the mock
// path's IDENTITY_INACTIVE.
async function laravelLogin(identityId: string, proof: string, requestId: string) {
  const tenant = laravelTenant();
  try {
    const { data: challenge } = await laravelPost<LaravelChallenge>(null, '/customers/auth/otp', {
      tenant_id: tenant,
      purpose: 'login',
      identity: identityId,
    });
    const { data: session } = await laravelPost<LaravelSession>(null, '/customers/auth/login', {
      tenant_id: tenant,
      challenge_id: challenge.challenge_id,
      code: proof,
    });
    const maxAge = Math.max(60, Math.floor((Date.parse(session.expires_at) - Date.now()) / 1000));
    return Response.json(ok({ sessionId: session.actor.session_id, expiresAt: session.expires_at }, requestId), {
      headers: { 'Set-Cookie': buildSessionCookie(session.token, maxAge) },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Invalid credentials.', requestId);
  }
}
