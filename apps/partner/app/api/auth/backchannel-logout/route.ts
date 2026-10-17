import { federationConfig, isFederated } from '@/server/federation';
import { verifyLogoutToken } from '@/server/oidc';
import { markSidTerminated, rememberLogoutJti } from '@/server/backchannel-logout';

/**
 * POST /api/auth/backchannel-logout — the OP's OIDC Back-Channel
 * Logout 1.0 receiver (A-SEC-004-6), registered on the client as its
 * `backchannel_logout_uri`.
 *
 * Server-to-server: the issuer POSTs `logout_token=<jwt>` here for
 * every RP bound to the terminated session. The token is verified
 * fail-closed (JWKS signature, typ, iss, aud, events, fresh iat, jti
 * replay) before anything is recorded; a verified token parks its
 * `sid` in the local denylist so the session's cookie stops
 * resolving an actor at its next use.
 *
 * Responses: 204 on success, a uniform 400 on ANY invalid token
 * (never which axis failed), 404 outside federation — the same
 * posture as the callback route. Local bookkeeping never blocks a
 * logout: recording is best-effort memory, and the issuer's own
 * lineage revocation is the primary kill.
 */
export async function POST(req: Request): Promise<Response> {
  if (!isFederated()) {
    return new Response(null, { status: 404 });
  }

  const contentType = req.headers.get('content-type') ?? '';
  if (!contentType.includes('application/x-www-form-urlencoded')) {
    return badRequest();
  }

  let logoutToken: string | null = null;
  try {
    const body = await req.formData();
    const value = body.get('logout_token');
    if (typeof value === 'string' && value !== '') {
      logoutToken = value;
    }
  } catch {
    return badRequest();
  }

  if (logoutToken === null) {
    return badRequest();
  }

  const config = federationConfig();
  const claims = await verifyLogoutToken(logoutToken, config.issuer, config.clientId);
  if (!claims) {
    return badRequest();
  }

  if (!rememberLogoutJti(claims.jti)) {
    return badRequest();
  }

  markSidTerminated(claims.sid);

  return new Response(null, {
    status: 204,
    headers: { 'Cache-Control': 'no-store' },
  });
}

function badRequest(): Response {
  return new Response(null, { status: 400, headers: { 'Cache-Control': 'no-store' } });
}
