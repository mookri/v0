import { newRequestId, ok, err } from '../../../application/api-envelope';
import { anonymousContext } from '../../../application/request-context';

export async function GET() {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.notifications', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ items: [], pagination: { hasMore: false, nextCursor: null } }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=15' },
  });
}
