import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { listCustomerBookings } from '@/application/bookings/list-customer-bookings';

export async function GET() {
  const requestId = newRequestId();
  const ctx = anonymousContext('customer.bookings', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const data = listCustomerBookings([]);
  return Response.json(
    ok({ items: data, pagination: { hasMore: false, nextCursor: null } }, requestId),
    { headers: { 'Cache-Control': 'private, max-age=30' } },
  );
}
