import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/admin/stats — platform admin dashboard overview stats (W2).
 *
 * Re-pointed: the three dangling upstreams (`/platform/bookings`,
 * `/platform/content`, `/platform/payouts`) now read the real
 * `/platform/ops/*` aggregates, and the tenant is named instead of ''.
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
    return Response.json(err('UNAUTHENTICATED', 'Admin session required', requestId), {
      status: 401,
    });
  }

  const tenantId = laravelTenant();
  if (!tenantId) {
    return Response.json(
      err('DEPENDENCY_UNAVAILABLE', 'No console tenant configured: set CUSTOMER_TENANT_ID.', requestId),
      { status: 503 },
    );
  }

  try {
    const [usersRes, rolesRes, bookingsRes, contentRes, payoutsRes] = await Promise.allSettled([
      laravelGet(token, '/platform/users', { tenant_id: tenantId }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/roles', { tenant_id: tenantId }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/ops/bookings', { tenant_id: tenantId }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/ops/content', {}, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/ops/payouts', { tenant_id: tenantId }, {}, { timeout: 'normal', requestId }),
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
