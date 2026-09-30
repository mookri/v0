import { newRequestId, ok, err } from '@/application/api-envelope';
import { logout } from '@navdhya/identity/src/application/token-session';
import { verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import { authSessions, authTokenSecret } from '@/server/auth';

export async function POST(req: Request) {
  const requestId = newRequestId();
  const header = req.headers.get('authorization');
  if (!header || !header.startsWith('Bearer ')) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const nowIso = new Date().toISOString();
  try {
    const actor = verifyActorToken(authTokenSecret(), header.slice('Bearer '.length).trim(), nowIso);
    await logout({ sessions: authSessions }, actor.sessionId, nowIso);
    return Response.json(ok({ signedOut: true }, requestId));
  } catch {
    return Response.json(err('UNAUTHENTICATED', 'Invalid session.', requestId), { status: 401 });
  }
}
