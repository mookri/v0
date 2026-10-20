// proxy.ts — apps/dashboard (Next 16 edge entry point).
//
// W4 (docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md): apps/dashboard was the
// only console surface with NO edge guard at all — every route, including
// /bookings, /customers, /pujas, /settings, rendered for anyone who could
// reach the origin. This guard fails closed: a request without a federated
// session cookie is redirected to /login. Token validity stays server-side
// (the BFF's /api/auth/me re-verifies against the issuer JWKS on every boot).
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { sessionCookieName } from '@/server/session';

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Public surfaces: login + the OIDC callback + the back-channel logout
  // receiver (which answers a bare 204/400 per spec and cannot carry a
  // redirect). Everything else requires a session.
  if (
    pathname === '/login' ||
    pathname.startsWith('/login/') ||
    pathname === '/api/auth/callback' ||
    pathname === '/api/auth/backchannel-logout' ||
    pathname === '/api/auth/authorize' ||
    pathname === '/api/auth/login' ||
    pathname === '/api/auth/logout' ||
    pathname === '/api/auth/logout/start' ||
    pathname === '/api/auth/logout/landing'
  ) {
    return NextResponse.next();
  }

  const sessionToken = request.cookies.get(sessionCookieName())?.value;
  if (!sessionToken) {
    const login = request.nextUrl.clone();
    login.pathname = '/login';
    login.search = '';
    login.searchParams.set('next', pathname);
    return NextResponse.redirect(login, 302);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
