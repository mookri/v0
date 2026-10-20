import {
  isFederated,
  federationConfig,
  parMode,
  authorizeParams,
  buildAuthorizeUrl,
  buildRequestUriUrl,
  pushAuthorizationRequest,
  callbackUrl,
  buildFlowCookie,
  randomBase64Url,
  pkceChallenge,
  safeNext,
} from '@/server/federation';

/**
 * GET /api/auth/authorize — the AUTH_MODE cutover's browser handoff
 * (ADR-0020), partner entry point.
 *
 *  - direct: partner has no legacy browser ceremony (its login route is
 *    API-driven), so this route simply sends the browser to the app
 *    root — nothing to hand off to.
 *  - federated: mint state + PKCE verifier, park them in a short-lived
 *    HttpOnly flow cookie, and 302 to the auth subdomain's
 *    /oauth/authorize. The BFF never renders a credential form again:
 *    the issuer's unified login is the only ceremony that exists.
 *    With PARTNER_AUTH_PAR=required (RFC 9126, A-SEC-004-8) the
 *    parameters are first pushed over the authenticated back channel
 *    and the browser presents only the returned request_uri; a failed
 *    push fails closed — never a front-channel fallback.
 *
 * The issuer's redirect_uri is derived from the request origin and must
 * be registered for the client; the client secret never leaves the BFF.
 */
export async function GET(req: Request): Promise<Response> {
  const url = new URL(req.url);
  const redirect = safeNext(url.searchParams.get('redirect'));

  if (!isFederated()) {
    const target = redirect ? `/?redirect=${encodeURIComponent(redirect)}` : '/';
    return new Response(null, { status: 302, headers: { Location: target } });
  }

  const config = federationConfig();
  const state = randomBase64Url();
  const verifier = randomBase64Url();
  const nonce = randomBase64Url();
  const challenge = pkceChallenge(verifier);

  const params = { redirectUri: callbackUrl(url.origin), state, challenge, nonce };

  // A-SEC-004-8: under PAR the parameters are authenticated on the
  // back channel and the browser presents only the returned
  // capability; a failed push throws (fail closed) — a mandate
  // client never falls back to front-channel parameters. The issuer's
  // request_uri TTL is short (120 s): the ceremony must complete
  // inside it or the resume dies at the OP and login restarts.
  const location =
    parMode() === 'required'
      ? buildRequestUriUrl(config, await pushAuthorizationRequest(config, authorizeParams(config, params)))
      : buildAuthorizeUrl(config, params.redirectUri, state, challenge, nonce);

  return new Response(null, {
    status: 302,
    headers: {
      Location: location,
      'Set-Cookie': buildFlowCookie({ state, verifier, nonce, redirect }),
      'Cache-Control': 'no-store',
    },
  });
}
