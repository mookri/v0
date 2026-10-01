import { newRequestId, ok, err } from '@/application/api-envelope';
import { login } from '@navdhya/identity/src/application/token-session';
import { partnerIdentities, partnerSessions, partnerVerifier, partnerTokenSecret, SESSION_COOKIE } from '@/server/auth';

export async function POST(req: Request) {
  const requestId = newRequestId();
  let body: { identityId?: string; proof?: string } = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 400 });
  }
  if (!body.identityId || typeof body.proof !== 'string') {
    return Response.json(err('VALIDATION_ERROR', 'identityId and proof are required.', requestId), { status: 400 });
  }
  const nowIso = new Date().toISOString();
  try {
    const { session, token } = await login(
      { identities: partnerIdentities, sessions: partnerSessions, verifier: partnerVerifier, tokenSecret: partnerTokenSecret() },
      {
        identityId: body.identityId,
        proof: body.proof,
        audience: 'partner',
        roles: ['partner'],
        sessionId: `sess_${requestId}`,
        tokenTtlSeconds: 3600,
        sessionTtlSeconds: 86400,
        nowIso,
      },
    );
    const res = Response.json(ok({ sessionId: session.id, expiresAt: session.expiresAt }, requestId));
    res.headers.append(
      'Set-Cookie',
      `${SESSION_COOKIE}=${token}; HttpOnly; Path=/; SameSite=Lax; Max-Age=3600${process.env.NODE_ENV === 'production' ? '; Secure' : ''}`,
    );
    return res;
  } catch (e) {
    const code = e instanceof Error ? e.message : '';
    if (code === 'IDENTITY_INACTIVE' || code === 'CREDENTIAL_INVALID') {
      return Response.json(err('UNAUTHENTICATED', 'Invalid credentials.', requestId), { status: 401 });
    }
    throw e;
  }
}
