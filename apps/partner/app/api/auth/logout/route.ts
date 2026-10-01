import { newRequestId, ok, err } from '@/application/api-envelope';
import { logout } from '@navdhya/identity/src/application/token-session';
import { verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import { partnerSessions, partnerTokenSecret, SESSION_COOKIE } from '@/server/auth';
import { readSessionCookie } from '@/application/actor';

export async function POST(req: Request) {
  const requestId = newRequestId();
  const header = req.headers.get('authorization');
  const token = header?.startsWith('Bearer ')
    ? header.slice('Bearer '.length).trim()
    : readSessionCookie(req.headers.get('cookie'));
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const nowIso = new Date().toISOString();
  try {
    const actor = verifyActorToken(partnerTokenSecret(), token, nowIso);
    await logout({ sessions: partnerSessions }, actor.sessionId, nowIso);
    const res = Response.json(ok({ signedOut: true }, requestId));
    res.headers.append(
      'Set-Cookie',
      `${SESSION_COOKIE}=; HttpOnly; Path=/; SameSite=Lax; Max-Age=0${process.env.NODE_ENV === 'production' ? '; Secure' : ''}`,
    );
    return res;
  } catch {
    return Response.json(err('UNAUTHENTICATED', 'Invalid session.', requestId), { status: 401 });
  }
}
