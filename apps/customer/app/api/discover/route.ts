import { newRequestId, ok, err } from '../../../application/api-envelope';
import { anonymousContext } from '../../../application/request-context';
import { getDiscoverFeed } from '../../../application/discover/discover-feed';

export async function GET() {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.discover', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const data = getDiscoverFeed({});
  return Response.json(ok(data, requestId), {
    headers: { 'Cache-Control': 'private, max-age=60' },
  });
}
