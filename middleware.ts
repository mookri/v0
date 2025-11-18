// middleware.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

// This function can be marked `async` if using `await` inside
export function middleware(request: NextRequest) {
  // Create a response object
  const response = NextResponse.next();
  
  // Compute and forward headers
  const requestId = request.headers.get('x-request-id') || crypto.randomUUID();
  const userAgent = request.headers.get('user-agent') || '';
  const tenantId = request.headers.get('x-tenant-id') || 'default';
  
  // Determine device type based on user agent
  let device: 'mobile' | 'desktop' | 'tablet' = 'desktop';
  if (/mobile|android|iphone|ipod|iemobile/i.test(userAgent)) {
    device = 'mobile';
  } else if (/tablet|ipad/i.test(userAgent)) {
    device = 'tablet';
  }
  
  // For role, we'll set a default for now - in a real app this would come from auth
  const userRole = request.headers.get('x-user-role') || 'User';
  
  // Generate a seed for A/B testing
  const seed = request.headers.get('x-seed') || crypto.randomUUID();
  
  // Set headers for the kernel to use
  response.headers.set('x-request-id', requestId);
  response.headers.set('x-tenant-id', tenantId);
  response.headers.set('x-user-role', userRole);
  response.headers.set('x-device', device);
  response.headers.set('x-seed', seed);
  
  return response;
}

// Configure which paths the middleware should run on
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