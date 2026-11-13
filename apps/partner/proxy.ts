import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

const PUBLIC_PATHS = ['/api/health', '/api/version'];

/**
 * Ensures every authenticated / BFF API response from the partner portal
 * carries `Cache-Control: private, no-store` and `Vary: Cookie, Authorization`
 * so shared caches never retain partner operational or fiscal payloads (T-AP03-04).
 */
export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const response = NextResponse.next();

  if (PUBLIC_PATHS.some((prefix) => pathname.startsWith(prefix))) {
    return response;
  }

  if (
    pathname.startsWith('/api/') ||
    request.cookies.get('__session')?.value ||
    request.headers.has('authorization')
  ) {
    response.headers.set('Cache-Control', 'private, no-store');
    response.headers.set('Vary', 'Cookie, Authorization');
  }

  return response;
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};

