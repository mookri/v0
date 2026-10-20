import { newRequestId, ok, err } from '@/application/api-envelope';
import { logout } from '@navdhya/identity/src/application/token-session';
import { verifyActorToken } from '@navdhya/identity/src/application/token-actor';
import { authSessions, authTokenSecret } from '@/server/auth';
import { laravelEnabled, laravelPost } from '@/adapters/backend/laravel-client';
import {
  readCustomerToken,
  readCustomerRefreshToken,
  clearSessionCookie,
  clearRefreshCookie,
} from '@/server/backend-session';
import { isFederated, federationConfig, type FederationConfig } from '@/server/federation';
import { TIMEOUT_MS } from '@/application/api-policy';

export async function POST(req: Request) {
  const requestId = newRequestId();
  if (isFederated()) {
    return federatedLogout(req, requestId);
  }
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
// so failures never block the client sign-out; the cookies always clear
// (both halves of the session family — a surviving refresh cookie would
// resurrect the session at its next request).
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
  const headers = new Headers();
  headers.append('Set-Cookie', clearSessionCookie());
  headers.append('Set-Cookie', clearRefreshCookie());
  return Response.json(ok({ signedOut: true }, requestId), { headers });
}

// Federated logout (ADR-0020 cutover): revoke the session's credentials
// at the issuer (back-channel, client-authenticated, best-effort) and
// always clear the cookies. Both halves of the family are revoked — the
// access token AND the refresh token the proxy now holds (revoking the
// refresh token is what actually retires the lineage). Idempotent — the
// response never depends on the issuer's answer; the browser must never
// keep a session it asked to end. Nothing token-shaped is logged.
async function federatedLogout(req: Request, requestId: string) {
  const cookieHeader = req.headers.get('cookie');
  const token = readCustomerToken(cookieHeader);
  const refreshToken = readCustomerRefreshToken(cookieHeader);
  if (token || refreshToken) {
    try {
      const config = federationConfig();
      if (refreshToken) {
        await revokeAtIssuer(config, refreshToken, 'refresh_token');
      }
      if (token) {
        await revokeAtIssuer(config, token, 'access_token');
      }
    } catch {
      // Best-effort by design — the local session still ends.
    }
  }
  const headers = new Headers();
  headers.append('Set-Cookie', clearSessionCookie());
  headers.append('Set-Cookie', clearRefreshCookie());
  return Response.json(ok({ signedOut: true }, requestId), { headers });
}

// Client auth travels in the BODY (client_secret_post): the revoke
// endpoint validates request input, not the Authorization header —
// Basic-only requests are refused before any revocation.
async function revokeAtIssuer(
  config: FederationConfig,
  token: string,
  hint: 'access_token' | 'refresh_token',
): Promise<void> {
  try {
    await fetch(`${config.issuer}/oauth/revoke`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        Accept: 'application/json',
      },
      body: new URLSearchParams({
        token,
        token_type_hint: hint,
        client_id: config.clientId,
        client_secret: config.clientSecret,
      }).toString(),
      signal: AbortSignal.timeout(TIMEOUT_MS.fast),
    });
  } catch {
    // Best-effort by design — the caller falls through to cookie clearing.
  }
}
