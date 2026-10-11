import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/stats — super admin platform control center stats.
 * Requires super admin session with platform-wide access.
 * Proxies to Laravel super admin endpoints.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ stats: {}, meta: { fallback: true } }, requestId), {
      status: 200,
    });
  }

  const { token } = extractSessionToken(request);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Super admin session required', requestId), {
      status: 401,
    });
  }

  try {
    // Fetch platform-wide stats
    const [usersRes, revenueRes, systemRes, citiesRes, securityRes] = await Promise.allSettled([
      laravelGet(token, '/platform/stats/users', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/stats/revenue', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/stats/system', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/stats/cities', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/stats/security', { tenant_id: '' }, {}, { timeout: 'normal', requestId }),
    ]);

    const stats = {
      users: usersRes.status === 'fulfilled' ? usersRes.value.data : {},
      revenue: revenueRes.status === 'fulfilled' ? revenueRes.value.data : {},
      system: systemRes.status === 'fulfilled' ? systemRes.value.data : {},
      cities: citiesRes.status === 'fulfilled' ? citiesRes.value.data : [],
      security: securityRes.status === 'fulfilled' ? securityRes.value.data : {},
    };

    return Response.json(ok({ stats }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch platform stats', requestId);
  }
}