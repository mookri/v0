import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ suggestions: [] }, requestId), {
      headers: { 'Cache-Control': 'public, max-age=30' },
    });
  }

  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) query[k] = v;

    const result = await laravelGet<any>(null, '/search/suggest', query, {
      validation_failed: 'VALIDATION_ERROR',
    });

    return Response.json(ok(result.data ?? { suggestions: [] }, requestId), {
      headers: { 'Cache-Control': 'public, max-age=30' },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Suggestions are unavailable.', requestId);
  }
}