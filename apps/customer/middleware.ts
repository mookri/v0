import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

// Session cookie minted by buildSessionCookie (server/backend-session.ts —
// CUSTOMER_TOKEN_COOKIE is the source of truth; this literal mirrors it on
// purpose because middleware runs on the edge runtime and must not pull the
// identity package's node crypto into the import graph).
const SESSION_COOKIE = '__Host-nv_customer_token';
const PUBLIC_PATHS = ['/login', '/signup', '/api/auth', '/api/health', '/api/version'];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const sessionCookie = request.cookies.get(SESSION_COOKIE);

  // Allow public paths
  if (PUBLIC_PATHS.some(p => pathname.startsWith(p))) {
    return NextResponse.next();
  }

  // Check for session cookie
  if (!sessionCookie?.value) {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('redirect', pathname);
    return NextResponse.redirect(loginUrl);
  }

  return NextResponse.next();
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