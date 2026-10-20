import { isFederated, federationConfig } from '@/server/federation';
import { clearSessionCookie, readSessionToken } from '@/server/session';

/**
 * POST /api/auth/logout — session teardown (ADR-0020 cutover).
 *
 *  - federated: revoke the presented access token at the issuer
 *    (back-channel, client-authenticated, best-effort) and always clear
 *    the cookie. Idempotent — the response never depends on the
 *    issuer's answer; the browser must never keep a session it asked to
 *    end. Nothing token-shaped is logged.
 *  - direct: no server-side session exists in direct mode (the SPA owns
 *    its token); clear the cookie defensively and return the same
 *    success shape so the client's local teardown is unconditional.
 *
 * Error bodies are `{ message }` — the SPA client's failure contract.
 */
export async function POST(req: Request): Promise<Response> {
  if (isFederated()) {
    const token = readSessionToken(req.headers.get('cookie'));
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
          signal: AbortSignal.timeout(5000),
        });
      } catch {
        // Best-effort by design — the local session still ends.
      }
    }
  }

  const headers = new Headers({ 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
  for (const cookie of clearSessionCookie()) {
    headers.append('Set-Cookie', cookie);
  }
  return new Response(JSON.stringify({ signedOut: true }), { status: 200, headers });
}
