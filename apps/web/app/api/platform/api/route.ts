import { newRequestId, ok, err } from '@/application/api-envelope';
import {
  laravelEnabled,
  laravelGet,
  laravelErrorResponse,
  laravelTenant,
} from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * GET /api/platform/api — the API-management console read (W1a).
 *
 * This route used to proxy `/api/v1/platform/api`, a path Laravel has never
 * served, so `/platform/api` always answered 404 and the failure disappeared
 * into the handler. It now reads the two surfaces that do exist:
 *
 *   - `GET /platform/api-keys`  — machine credentials, `platform:apikey:view`
 *     enforced inside `ApiKeyController::scopeToTenant`;
 *   - `GET /platform/oauth/clients` — federation clients, `platform:user:view`
 *     via route middleware.
 *
 * Both are read with `Promise.all` on purpose. `allSettled` + a partial body
 * would hide a denial as "no OAuth clients exist", which is the exact class of
 * silent degradation the console plan forbids: one leg failing surfaces as the
 * mapped error so the panel can say so.
 */
export async function GET(request: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ api_keys: [], oauth_clients: [], meta: { fallback: true } }, requestId), {
      status: 200,
    });
  }

  const { token } = extractSessionToken(request);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Super admin session required', requestId), {
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
    const [keys, clients] = await Promise.all([
      laravelGet(token, '/platform/api-keys', { tenant_id: tenantId }, {}, { timeout: 'normal', requestId }),
      laravelGet(token, '/platform/oauth/clients', { tenant_id: tenantId }, {}, { timeout: 'normal', requestId }),
    ]);

    return Response.json(
      ok(
        {
          api_keys: keys.data ?? [],
          oauth_clients: clients.data ?? [],
          meta: keys.meta,
        },
        requestId,
      ),
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch API management data', requestId);
  }
}
