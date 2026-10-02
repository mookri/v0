import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

// Wired logout (CUSTOMER_BACKEND=laravel): kills the backend session.
// Without the flag the surface is unauthenticated-only.
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ loggedOut: true }, requestId));
  }

  try {
    const tenant = laravelTenant();
    await laravelPost(null, '/customers/auth/logout', { tenant_id: tenant });

    // Clear the session cookie
    return Response.json(ok({ loggedOut: true }, requestId), {
      headers: { 'Set-Cookie': 'navdhya-session=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0' },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Logout failed.', requestId);
  }
}