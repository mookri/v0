import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost } from '@/adapters/backend/laravel-client';
import { extractSessionToken, clearSessionCookie } from '@/server/session-cookie';
import { isFederated, federationConfig } from '@/server/federation';
import { TIMEOUT_MS } from '@/application/api-policy';

/**
 * POST /api/auth/logout — end the session and clear the cookie.
 *
 * Contract: auth/p0-contract.md §6 (I16: logout is idempotent — always
 * 200, always clears the cookie regardless of backend state).
 *
 * The cookie is cleared even when the backend call fails: the browser
 * must never keep a session it asked to end. The backend-side session
 * revocation failure is logged, not surfaced (a retry of logout is
 * meaningless to the user).
 */
export async function POST(req: Request) {
  const requestId = newRequestId();

  // Always clear, unconditionally, first — the response shape does not
  // depend on backend state (idempotency).
  const clearHeaders = () => ({ 'Set-Cookie': clearSessionCookie() });

  if (isFederated()) {
    return federatedLogout(req, requestId);
  }

  if (!laravelEnabled()) {
    return Response.json(ok({ loggedOut: true }, requestId), { headers: clearHeaders() });
  }

  const extraction = extractSessionToken(req);
  if (extraction.token === null || extraction.source !== 'cookie') {
    // Nothing to revoke server-side, but still emit the clear so a
    // stale cookie gets removed (idempotent no-op).
    return Response.json(ok({ loggedOut: true }, requestId), { headers: clearHeaders() });
  }

  try {
    await laravelPost(
      extraction.token,
      '/customers/auth/logout',
      { tenant_id: process.env.CUSTOMER_TENANT_ID ?? '' },
      {},
      { timeout: 'fast', requestId },
    );
    return Response.json(ok({ loggedOut: true }, requestId), { headers: clearHeaders() });
  } catch (e) {
    // Backend revocation failed — log for ops, but the user's local
    // session is still cleared. Never leave the cookie in place.
    console.error(`[auth] logout backend call failed (requestId=${requestId}):`, e);
    return Response.json(ok({ loggedOut: true }, requestId), { headers: clearHeaders() });
  }
}

// Federated logout (ADR-0020 cutover): revoke the presented access token
// at the issuer (back-channel, client-authenticated, best-effort) and
// always clear the cookie. Idempotent — the response never depends on
// the issuer's answer; the browser must never keep a session it asked
// to end. Nothing token-shaped is logged.
async function federatedLogout(req: Request, requestId: string) {
  const clearHeaders = () => ({ 'Set-Cookie': clearSessionCookie() });
  const extraction = extractSessionToken(req);
  if (extraction.token) {
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
          token: extraction.token,
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
  return Response.json(ok({ loggedOut: true }, requestId), { headers: clearHeaders() });
}
