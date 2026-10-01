import { newRequestId, ok, err } from '@/application/api-envelope';
import { logout } from '@navdhya/identity/src/application/token-session';
import { verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import { authSessions, authTokenSecret } from '@/server/auth';
import { laravelEnabled, laravelPost } from '@/adapters/backend/laravel-client';
import { readCustomerToken, clearSessionCookie } from '@/server/backend-session';

export async function POST(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    return laravelLogout(req, requestId);
  }
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

// Wired logout: backend invalidation is best-effort (jti store pending),
// so failures never block the client sign-out; the cookie always clears.
async function laravelLogout(req: Request, requestId: string) {
  const token = readCustomerToken(req.headers.get('cookie'));
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  try {
    await laravelPost(token, '/customers/auth/logout', {});
  } catch {
    // Best-effort by design — fall through to cookie clearing.
  }
  return Response.json(ok({ signedOut: true }, requestId), {
    headers: { 'Set-Cookie': clearSessionCookie() },
  });
}
