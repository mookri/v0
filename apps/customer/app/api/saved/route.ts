import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { listSavedItems } from '@/application/saved/saved-items';

export async function GET() {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.saved', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const data = listSavedItems([], () => null);
  return Response.json(ok({ items: data, pagination: { hasMore: false, nextCursor: null } }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=30' },
  });
}
