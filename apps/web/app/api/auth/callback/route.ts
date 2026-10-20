import { newRequestId, err } from '@/application/api-envelope';
import {
  isFederated,
  federationConfig,
  callbackUrl,
  readFlowCookie,
  clearFlowCookie,
  timingSafeCompare,
  safeNext,
} from '@/server/federation';
import { verifyIdToken } from '@/server/oidc';
import { setSessionCookie } from '@/server/session-cookie';

/**
 * GET /api/auth/callback — the OAuth redirect target (ADR-0020 cutover).
 *
 * Completes the handoff the issuer bounced back: validate state against
 * the flow cookie (timing-safe), require the RFC 9207 `iss` parameter to
 * name our issuer byte for byte (response mix-up defence — a response
 * from another authorization server can never complete here), then
 * exchange the authorization code at the token endpoint server-to-server
 * (PKCE verifier + client secret never reach the browser). The returned
 * id_token is verified (RS256 via the issuer JWKS, iss/aud/exp/iat/nonce)
 * before the session exists — a login that cannot prove its id_token
 * fails closed. On success the issuer's access token becomes the BFF
 * session cookie; upstream stays the sole authentication authority
 * (I18) — every proxied call carries it and Laravel judges it. Session
 * identity derives from the access token / userinfo, never the
 * id_token's sub.
 *
 * No refresh lineage is stored here yet: the session lives the access
 * token's lifetime; re-authentication bounces through the issuer's
 * persistent SSO session (no OTP re-entry). Failures are uniform — the
 * code/state/verifier are never echoed, logged or reflected.
 */
export async function GET(req: Request): Promise<Response> {
  const requestId = newRequestId();

  if (!isFederated()) {
    return Response.json(err('NOT_FOUND', 'Federation callback does not exist in direct deployments.', requestId), {
      status: 404,
    });
  }

  const url = new URL(req.url);
  const flow = readFlowCookie(req.headers.get('cookie'));
  if (!flow) {
    return expired(requestId, 'Sign-in session expired. Please try again.');
  }

  const config = federationConfig();

  const issuerError = url.searchParams.get('error');
  if (issuerError) {
    // The issuer refused or the user cancelled — same uniform outcome,
    // no reflection of issuer-provided text.
    return expired(requestId, 'Sign-in could not be completed. Please try again.');
  }

  const state = url.searchParams.get('state') ?? '';
  const code = url.searchParams.get('code') ?? '';
  if (!code || !timingSafeCompare(state, flow.state)) {
    return expired(requestId, 'Sign-in session mismatch. Please try again.');
  }

  // 9a (ADR-0021 D2): RFC 9207 — every redirect-based authorization
  // response carries `iss`, and this BFF REQUIRES it (exact match,
  // timing-safe). A response without iss, or with another issuer's,
  // is a mix-up or a pre-9a server: either way it cannot complete.
  const iss = url.searchParams.get('iss') ?? '';
  if (!iss || !timingSafeCompare(iss, config.issuer)) {
    return expired(requestId, 'Sign-in could not be completed. Please try again.');
  }

  const redirect = safeNext(flow.redirect) ?? '/';

  let tokens: { access_token?: string; expires_in?: number; id_token?: string };
  try {
    const response = await fetch(`${config.issuer}/oauth/token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
      body: new URLSearchParams({
        grant_type: 'authorization_code',
        code,
        redirect_uri: callbackUrl(url.origin),
        client_id: config.clientId,
        client_secret: config.clientSecret,
        code_verifier: flow.verifier,
      }).toString(),
    });
    if (!response.ok) {
      return Response.json(err('DEPENDENCY_UNAVAILABLE', 'Sign-in could not be completed.', requestId), {
        status: 502,
        headers: { 'Set-Cookie': clearFlowCookie() },
      });
    }
    tokens = (await response.json()) as { access_token?: string; expires_in?: number; id_token?: string };
  } catch {
    return Response.json(err('DEPENDENCY_UNAVAILABLE', 'Sign-in could not be completed.', requestId), {
      status: 502,
      headers: { 'Set-Cookie': clearFlowCookie() },
    });
  }

  if (!tokens.access_token) {
    return Response.json(err('DEPENDENCY_UNAVAILABLE', 'Sign-in could not be completed.', requestId), {
      status: 502,
      headers: { 'Set-Cookie': clearFlowCookie() },
    });
  }

  // 9a (ADR-0021 D3): the openid scope was requested, so an id_token
  // MUST be present and MUST verify (signature, issuer, audience,
  // timestamps, exact nonce). Anything less is a failed sign-in.
  if (!tokens.id_token) {
    return Response.json(err('DEPENDENCY_UNAVAILABLE', 'Sign-in could not be completed.', requestId), {
      status: 502,
      headers: { 'Set-Cookie': clearFlowCookie() },
    });
  }

  const claims = await verifyIdToken(tokens.id_token, config.issuer, config.clientId, flow.nonce);
  if (!claims) {
    return Response.json(err('UNAUTHENTICATED', 'Sign-in could not be verified. Please try again.', requestId), {
      status: 401,
      headers: { 'Set-Cookie': clearFlowCookie() },
    });
  }

  const maxAge = Math.max(60, Math.floor(tokens.expires_in ?? 900));
  const headers = new Headers({ Location: redirect, 'Cache-Control': 'no-store' });
  headers.append('Set-Cookie', setSessionCookie(tokens.access_token, maxAge));
  headers.append('Set-Cookie', clearFlowCookie());
  return new Response(null, { status: 302, headers });
}

function expired(requestId: string, message: string): Response {
  return Response.json(err('UNAUTHENTICATED', message, requestId), {
    status: 401,
    headers: { 'Set-Cookie': clearFlowCookie() },
  });
}
