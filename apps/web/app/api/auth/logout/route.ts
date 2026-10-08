import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost } from '@/adapters/backend/laravel-client';
import { extractSessionToken, clearSessionCookie } from '@/server/session-cookie';

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
