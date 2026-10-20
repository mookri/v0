import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse, laravelTenant } from '@/adapters/backend/laravel-client';
import { extractSessionToken } from '@/server/session-cookie';

/**
 * POST /api/admin/search/reindex — W3 capability surfacing.
 *
 * Proxies Laravel POST /api/v1/search/reindex (`capability:search:index:reindex`).
 * Body: `{ type?, fresh?, chunk? }`. The backend returns reindex stats.
 */
export async function POST(request: Request) {
  const requestId = newRequestId();

  const body = (await request.json().catch(() => ({}))) as {
    type?: string;
    fresh?: boolean;
    chunk?: number;
  };

  if (!laravelEnabled()) {
    return Response.json(ok({ data: { total: 0, upserted: 0, deleted: 0, terms: 0, version: 0, fallback: true }, meta: { fallback: true } }, requestId), {
      status: 200,
    });
  }

  const { token } = extractSessionToken(request);
  if (!token) {
    return Response.json(err('UNAUTHENTICATED', 'Admin session required', requestId), {
      status: 401,
    });
  }

  try {
    const { data, meta } = await laravelPost(
      token,
      '/search/reindex',
      {
        type: body.type,
        fresh: body.fresh ?? false,
        chunk: body.chunk ?? 500,
      },
      {},
      { timeout: 'normal', requestId },
    );

    return Response.json(ok({ data, meta }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to reindex search', requestId);
  }
}
