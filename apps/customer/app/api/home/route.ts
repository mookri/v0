import { newRequestId, ok, err } from '../../../application/api-envelope';
import { anonymousContext } from '../../../application/request-context';
import { getCustomerHome } from '../../../application/home/get-customer-home';

export async function GET() {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.home', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const data = getCustomerHome({ displayName: 'Friend' });
  return Response.json(ok(data, requestId), {
    headers: { 'Cache-Control': 'private, max-age=30' },
  });
}
