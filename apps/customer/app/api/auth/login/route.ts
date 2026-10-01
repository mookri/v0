import { newRequestId, ok, err } from '@/application/api-envelope';
import { login } from '@navdhya/identity/src/application/token-session';
import { authIdentities, authSessions, authVerifier, authTokenSecret, seedDevIdentity } from '@/server/auth';

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
