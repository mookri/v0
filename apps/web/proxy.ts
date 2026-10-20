// proxy.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import type { RequestContext, Role, Tenant, DeviceType } from '@/lib/dlo-x/types';
import { deriveStableSeed } from '@/lib/dlo-x/stable-seed';
import { stranglerTarget, stranglerFlagsFromEnv } from '@/lib/strangler/route-map';
import { LEGACY_COOKIE_NAME, sessionCookieName } from '@/server/session-cookie';
import { DEV_ROLE_COOKIE, isValidDevRole } from '@/lib/auth/cookie-names';
import { canAccessConsole, consoleLandingForRole, isConsolePath } from '@/lib/product/console-access';

/**
 * MNC-Grade Proxy for Next.js 16 (replacing deprecated middleware).
 * Aligned with DLO-X (Dynamic Layout Orchestration) architecture.
 */
export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // W1: user-facing dashboards (/user /pandit /vendor /manager) live in the
  // customer app — redirect there, production only. Flag-gated like the
  // strangler below (default off) so dev/staging keep serving locally.
  if (process.env.NEXT_PUBLIC_PRODUCTION_REDIRECTS === 'true') {
    // /user and /pandit are customer-facing (app.navdhya.com); /vendor and
    // /manager are partner-facing (partner.navdhya.com) - vendor self-serve
    // and the onboarding review queue live in apps/partner.
    const customerPaths = ['/user', '/pandit'];
    const partnerPaths = ['/vendor', '/manager'];
    const customerOrigin = process.env.NEXT_PUBLIC_CUSTOMER_ORIGIN ?? 'https://app.navdhya.com';
    const partnerOrigin = process.env.NEXT_PUBLIC_PARTNER_ORIGIN ?? 'https://partner.navdhya.com';

    for (const path of customerPaths) {
      if (pathname === path || pathname.startsWith(`${path}/`)) {
        const destination = new URL(`${customerOrigin}${pathname}`);
        destination.search = request.nextUrl.search;
        return NextResponse.redirect(destination, 301);
      }
    }
    for (const path of partnerPaths) {
      if (pathname === path || pathname.startsWith(`${path}/`)) {
        const destination = new URL(`${partnerOrigin}${pathname}`);
        destination.search = request.nextUrl.search;
        return NextResponse.redirect(destination, 301);
      }
    }
  }

  // Strangler: redirect cut-over classes to their app origin. Flag-gated;
  // default off (serve-local everywhere) so this is a no-op until cutover.
  const decision = stranglerTarget(
    pathname,
    stranglerFlagsFromEnv(process.env as Record<string, string | undefined>),
  );
  if (decision.action === 'redirect') {
    const target = new URL(decision.target);
    target.search = request.nextUrl.search;
    return NextResponse.redirect(target, 307);
  }

  // W0: console access guard (G1 — docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md).
  // Placed AFTER the production dashboard redirect and the strangler cutover
  // so a console already handed to another origin hops rather than bouncing
  // through /login. Fail-closed: no credential → /login?next=<path>.
  //
  // The session token is opaque, so the edge proves *presence* only; whether
  // the token is valid and what the actor may do stay enforced server-side
  // (the BFF rejects unauthenticated calls; Laravel's capability gates are the
  // authority). The dev-role cookie is the only role signal visible here, and
  // it is a local convenience that must never act as a production credential
  // (mirrors the DevRoleSwitcher's own NODE_ENV gate).
  if (isConsolePath(pathname)) {
    const sessionToken =
      request.cookies.get(sessionCookieName())?.value ??
      request.cookies.get(LEGACY_COOKIE_NAME)?.value;
    const devRoleCookie = request.cookies.get(DEV_ROLE_COOKIE)?.value;
    const devRole =
      process.env.NODE_ENV !== 'production' && isValidDevRole(devRoleCookie) ? devRoleCookie : null;

    if (!sessionToken && devRole === null) {
      const login = request.nextUrl.clone();
      login.pathname = '/login';
      login.search = '';
      login.searchParams.set('next', pathname);
      return NextResponse.redirect(login, 302);
    }

    // A real session's role is resolved server-side, so the mismatch check
    // applies only to the dev path. Confine a dev role to its own console
    // (SuperAdmin may enter any — see canAccessConsole).
    if (!sessionToken && devRole !== null && !canAccessConsole(devRole, pathname)) {
      const landing = consoleLandingForRole(devRole);
      if (landing) {
        const own = request.nextUrl.clone();
        own.pathname = landing;
        own.search = '';
        return NextResponse.redirect(own, 302);
      }
    }
  }

  const userAgent = request.headers.get('user-agent') || '';

  // 1. Detect Request Context
  const requestId = request.headers.get('x-request-id') || crypto.randomUUID();
  const tenantId = (request.headers.get('x-tenant-id') as Tenant) || 'default';
  const userRole = (request.headers.get('x-user-role') as Role) || 'User';

  let device: DeviceType = 'desktop';
  if (/mobile|android|iphone|ipod|iemobile/i.test(userAgent)) {
    device = 'mobile';
  } else if (/tablet|ipad/i.test(userAgent)) {
    device = 'tablet';
  }

  const seed = deriveStableSeed(request.headers);

  // 2. Prepare Context
  const context: RequestContext = {
    requestId,
    tenantId,
    userRole,
    device,
    seed,
    path: pathname,
  };

  // 3. Robust Header Propagation
  // We set these on the REQUEST headers so they are available to Server Components
  // via the next/headers 'headers()' function. This pattern is critical for RSC
  // stability and avoiding "Invariant: Expected RSC response" errors.
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set('x-request-id', context.requestId);
  requestHeaders.set('x-tenant-id', context.tenantId);
  requestHeaders.set('x-user-role', context.userRole);
  requestHeaders.set('x-device', context.device);
  requestHeaders.set('x-seed', context.seed);
  requestHeaders.set('x-url', request.url);

  const response = NextResponse.next({
    request: {
      headers: requestHeaders,
    },
  });

  // Persist seed in a cookie so subsequent requests in the same day skip the
  // hash computation and stay in the same A/B bucket.
  if (!/(?:^|;\s*)navdhya-seed=/.test(request.headers.get('cookie') ?? '')) {
    response.cookies.set('navdhya-seed', seed, {
      httpOnly: false,
      sameSite: 'lax',
      secure: true,
      path: '/',
      maxAge: 60 * 60 * 24,
    });
  }

  return response;
}

// Matcher configuration
export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - api (API routes)
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    '/((?!api|_next/static|_next/image|favicon.ico).*)',
  ],
};
