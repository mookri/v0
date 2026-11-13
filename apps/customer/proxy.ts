import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

import { ensureFreshSession } from '@/server/session-refresh';

// Session cookie names minted by buildSessionCookie (server/
// backend-session.ts — the source of truth; these literals mirror it on
// purpose because the anonymous gate is a cheap presence check and must
// not pull the identity package's node crypto into every request's
// import graph). The `__Host-` prefix requires Secure, which
// http://localhost dev cannot set, so dev writes the bare name and
// prod writes the prefixed one — accept both. This gate only routes
// anonymous browsers to login; the request handlers are the real
// judges of any cookie that arrives.
const SESSION_COOKIES = ['__Host-nv_customer_token', 'nv_customer_token'];
const PUBLIC_PATHS = ['/login', '/signup', '/api/auth', '/api/health', '/api/version'];

// A-SEC-004-11: this file used to be middleware.ts (Edge runtime,
// deprecated in Next 16). proxy.ts runs on the Node.js runtime — the
// requirement for the DPoP proof minting (node:crypto) the federated
// refresh below rides on. Logic otherwise unchanged from the gate.
export async function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Allow public paths
  if (PUBLIC_PATHS.some(p => pathname.startsWith(p))) {
    return NextResponse.next();
  }

  // Federated session continuity (D4 BFF half): BEFORE the cookie gate
  // judges this request, an expiring (or idle-lost) access token is
  // rotated through the issuer with the held refresh token. The
  // rotation is single-flight per family, presents the same DPoP key
  // that bound it, and rewrites this request's cookie header with the
  // freshly minted token so downstream handlers never present a
  // consumed one. A rotated request is authenticated by construction
  // and skips the anonymous gate below. Everything is a no-op outside
  // federated deployments, and for any request without a refresh
  // cookie.
  const refreshed = await ensureFreshSession(request.headers.get('cookie'));
  const rewritten = refreshed.rewrittenCookieHeader;

  const headers = new Headers(request.headers);
  if (rewritten !== null) {
    headers.set('cookie', rewritten);
  }
  const response = NextResponse.next(rewritten !== null ? { request: { headers } } : undefined);
  for (const cookie of refreshed.setCookies) {
    response.headers.append('set-cookie', cookie);
  }
  if (rewritten !== null) {
    response.headers.set('Cache-Control', 'private, no-store');
    response.headers.set('Vary', 'Cookie, Authorization');
    return response;
  }

  // API fetch clients that ask for JSON own their auth decision and receive
  // typed JSON 401 envelopes. Browser navigations or requests without JSON
  // accept header get redirected to /login.
  const acceptsJson = request.headers.get('accept')?.includes('application/json');
  if (pathname.startsWith('/api/') && acceptsJson) {
    response.headers.set('Cache-Control', 'private, no-store');
    response.headers.set('Vary', 'Cookie, Authorization');
    return response;
  }

  // Check for session cookie (any minted name counts here)
  if (!SESSION_COOKIES.some(name => request.cookies.get(name)?.value)) {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('redirect', pathname);
    const redirect = NextResponse.redirect(loginUrl);
    // A refresh outcome must reach the browser even on a redirect —
    // the parked retry key (or the cleared dead family) is part of
    // this request's answer, not the landing page's.
    for (const cookie of refreshed.setCookies) {
      redirect.headers.append('set-cookie', cookie);
    }
    return redirect;
  }

  response.headers.set('Cache-Control', 'private, no-store');
  response.headers.set('Vary', 'Cookie, Authorization');
  return response;
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     * - public folder
     */
    '/((?!_next/static|_next/image|favicon.ico|public).*)',
  ],
};
