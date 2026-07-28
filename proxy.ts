// proxy.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import type { RequestContext, Role, Tenant, DeviceType } from '@/lib/dlo-x/types';
import { deriveStableSeed } from '@/lib/dlo-x/stable-seed';

/**
 * MNC-Grade Proxy for Next.js 16 (replacing deprecated middleware).
 * Aligned with DLO-X (Dynamic Layout Orchestration) architecture.
 */
export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
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
