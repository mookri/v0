import { newRequestId, ok, err } from '../../../application/api-envelope';
import { anonymousContext } from '../../../application/request-context';

export async function GET() {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.profile', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ profile: null }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=30' },
  });
}
