import { randomBytes } from 'node:crypto';

import { isFederated, federationConfig } from '@/server/federation';
import { buildLogoutStateCookie } from '@/server/session-cookie';

/**
 * GET /api/auth/logout/start — the browser-facing RP-initiated logout
 * hand-off (OIDC RP-Initiated Logout 1.0, A-SEC-004-6). Mints a fresh
 * correlation state, parks it in a short-lived HttpOnly cookie, and
 * redirects the browser to the issuer's end-session endpoint identified
 * by `client_id` — the OP resolves the RP without an id_token_hint, so
 * NO token material ever rides the URL. The OP terminates the SSO
 * session, fans out back-channel logout, and returns the browser to the
 * registered landing below with the same state.
 *
 * 404 outside federation: direct-mode sign-out is the existing
 * POST /api/auth/logout route (local cookie only — there is no OP
 * session to end). This route is a pure hand-off: it mutates nothing
 * locally beyond the correlation cookie.
 */
export async function GET(req: Request): Promise<Response> {
  if (!isFederated()) {
    return new Response(null, { status: 404 });
  }

  const state = randomBytes(32).toString('base64url');
  const target = new URL('/oauth/logout', federationConfig().issuer);
  target.searchParams.set('client_id', federationConfig().clientId);
  target.searchParams.set(
    'post_logout_redirect_uri',
    `${new URL(req.url).origin}/api/auth/logout/landing`,
  );
  target.searchParams.set('state', state);

  return new Response(null, {
    status: 307,
    headers: {
      Location: target.toString(),
      'Set-Cookie': buildLogoutStateCookie(state),
      'Cache-Control': 'no-store',
    },
  });
}
