import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ results: [], facets: { type: {}, city: {} }, next_cursor: null, took_ms: 0, search_version: 0, did_you_mean: null }, requestId));
  }
  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) query[k] = v;
    const result = await laravelGet<any>(null, '/search', query);
    return Response.json(ok(result.data ?? { results: [], facets: { type: {}, city: {} }, next_cursor: null, took_ms: 0 }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Search is unavailable.', requestId);
  }
}