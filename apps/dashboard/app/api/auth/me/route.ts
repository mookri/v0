import { federationConfig, isFederated } from '@/server/federation';
import { readSessionToken, verifiedSessionClaims } from '@/server/session';
import { backendErrorResponse, dashboardFetch } from '@/server/backend';

interface User {
  id: string;
  email: string;
  name: string;
  role: 'admin' | 'manager' | 'staff';
}

/**
 * GET /api/auth/me — session validation for the SPA boot (useAuth).
 *
 *  - federated: the cookie's issuer access token is re-verified HERE
 *    (JWKS signature, exp/iat/aud) before anything is rendered, then
 *    confirmed against the issuer's userinfo endpoint — the upstream
 *    stays the final judge of what the app displays (I18), and a
 *    userinfo sub that disagrees with the verified token's sub fails
 *    closed. Returns the User shape the client already speaks.
 *  - direct: proxied to the configured dashboard backend (503 when
 *    unset — same fail-closed posture as login).
 *
 * Roles: issuer access tokens carry no partner/console role claim, so
 * every federated identity resolves to the LEAST-privileged role
 * ('staff') until console-role provisioning lands (roadmap) — role
 * gating fails closed, never open.
 */
export async function GET(req: Request): Promise<Response> {
  if (!isFederated()) {
    try {
      const user = await dashboardFetch<User>('/auth/me', {
        method: 'GET',
        authorization: req.headers.get('authorization'),
      });
      return Response.json(user);
    } catch (error) {
      return backendErrorResponse(error);
    }
  }

  const token = readSessionToken(req.headers.get('cookie'));
  if (!token) {
    return Response.json({ message: 'Sign in to continue.' }, { status: 401 });
  }

  const claims = await verifiedSessionClaims(token);
  if (!claims) {
    return Response.json({ message: 'Your session is no longer valid. Please sign in again.' }, { status: 401 });
  }

  // Upstream confirmation: the issuer judges the token too (userinfo
  // requires a live, unrevoked access token).
  let info: { sub?: unknown; name?: unknown; email?: unknown };
  try {
    const config = federationConfig();
    const response = await fetch(`${config.issuer}/oauth/userinfo`, {
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
      signal: AbortSignal.timeout(5000),
    });
    if (!response.ok) {
      return Response.json({ message: 'Your session is no longer valid. Please sign in again.' }, { status: 401 });
    }
    info = (await response.json()) as typeof info;
  } catch {
    return Response.json({ message: 'Identity provider unavailable.' }, { status: 502 });
  }

  // The userinfo subject must be the subject we verified — anything
  // else is a confused response, never a login.
  if (typeof info.sub !== 'string' || info.sub !== claims.sub) {
    return Response.json({ message: 'Your session is no longer valid. Please sign in again.' }, { status: 401 });
  }

  return Response.json({
    id: claims.sub,
    email: typeof info.email === 'string' ? info.email : '',
    name: typeof info.name === 'string' && info.name !== '' ? info.name : info.sub,
    role: 'staff' as const,
  });
}
