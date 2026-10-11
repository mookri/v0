import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/stats — platform admin dashboard overview stats.
 * Requires admin session with platform:user:view capability.
 * Proxies to Laravel admin endpoints.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ stats: {}, meta: { fallback: true } }, requestId), {
      status: 200,
    });
  }

  // Extract session token
  const { token, source } = extractSessionToken(request);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Admin session required', requestId), {
      status: 401,
    });
  }

  try {
    // Fetch multiple admin stats in parallel
    const [usersRes, rolesRes, bookingsRes, contentRes, payoutsRes] = await Promise.allSettled([
      laravelGet(token, '/platform/users', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/roles', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/bookings', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/content', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/payouts', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
    ]);

    const stats = {
      users: usersRes.status === 'fulfilled' ? usersRes.value.data : [],
      roles: rolesRes.status === 'fulfilled' ? rolesRes.value.data : [],
      bookings: bookingsRes.status === 'fulfilled' ? bookingsRes.value.data : [],
      content: contentRes.status === 'fulfilled' ? contentRes.value.data : [],
      payouts: payoutsRes.status === 'fulfilled' ? payoutsRes.value.data : [],
    };

    return Response.json(ok({ stats }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch admin stats', requestId);
  }
}