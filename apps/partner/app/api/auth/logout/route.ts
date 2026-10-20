import { newRequestId, ok, err } from '@/application/api-envelope';
import { logout } from '@navdhya/identity/src/application/token-session';
import { verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import { partnerSessions, partnerTokenSecret, SESSION_COOKIE } from '@/server/auth';
import { readSessionCookie } from '@/application/actor';
import { isFederated, federationConfig } from '@/server/federation';
import { clearSessionCookie } from '@/server/session';
import { TIMEOUT_MS } from '@/application/api-policy';

export async function POST(req: Request) {
  const requestId = newRequestId();

  if (isFederated()) {
    return federatedLogout(req, requestId);
  }

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

// Federated logout (ADR-0020 cutover): revoke the presented access token
// at the issuer (back-channel, client-authenticated, best-effort) and
// always clear the cookie. Idempotent — the response never depends on
// the issuer's answer; the browser must never keep a session it asked
// to end. Nothing token-shaped is logged.
async function federatedLogout(req: Request, requestId: string) {
  const token = readSessionCookie(req.headers.get('cookie'));
  if (token) {
    try {
      const config = federationConfig();
      // Client auth travels in the BODY (client_secret_post): the
      // revoke endpoint validates request input, not the Authorization
      // header — Basic-only requests are refused before any revocation.
      await fetch(`${config.issuer}/oauth/revoke`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          Accept: 'application/json',
        },
        body: new URLSearchParams({
          token,
          token_type_hint: 'access_token',
          client_id: config.clientId,
          client_secret: config.clientSecret,
        }).toString(),
        signal: AbortSignal.timeout(TIMEOUT_MS.fast),
      });
    } catch {
      // Best-effort by design — the local session still ends.
    }
  }
  return Response.json(ok({ signedOut: true }, requestId), {
    headers: { 'Set-Cookie': clearSessionCookie() },
  });
}
