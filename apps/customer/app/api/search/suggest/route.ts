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

    // The suggest contract caps q at 80 characters; reject before the
    // backend normalises/truncates it.
    const q = searchParams.get('q') ?? '';
    if (q.length > 80) {
      return Response.json(
        err('VALIDATION_ERROR', 'Search query must be 80 characters or fewer.', requestId),
        { status: 422 },
      );
    }

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