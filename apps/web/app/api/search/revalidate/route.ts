// POST /api/search/revalidate — platform ping to bust the in-process
// corpus cache (S63). Guarded by SEARCH_REVALIDATE_SECRET.
import { invalidateSearchIndex } from '@/lib/search/federated';
import { newRequestId, ok, err } from '@/application/api-envelope';

export async function POST(req: Request) {
  const requestId = newRequestId();
  const secret = process.env.SEARCH_REVALIDATE_SECRET;
  if (secret) {
    const provided = req.headers.get('x-search-revalidate-secret');
    if (provided !== secret) {
      return Response.json(err('FORBIDDEN', 'Invalid revalidate secret.', requestId), { status: 403 });
    }
  }

  invalidateSearchIndex();
  return Response.json(ok({ invalidated: true }, requestId), { status: 200 });
}