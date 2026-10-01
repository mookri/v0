import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { listCustomerBookings } from '@/application/bookings/list-customer-bookings';
import { laravelEnabled, laravelGet, laravelErrorResponse, type BackendCodeMap, type LaravelResponse } from '@/adapters/backend/laravel-client';
import { readCustomerToken, resolveCustomerActor, customerTokenSecret } from '@/server/backend-session';
import { toBookingCard, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

const LIST_CODE_MAP: BackendCodeMap = {};

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    return laravelBookings(req, requestId);
  }
  const ctx = anonymousContext('customer.bookings', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const data = listCustomerBookings([]);
  return Response.json(
    ok({ items: data, pagination: { hasMore: false, nextCursor: null } }, requestId),
    { headers: { 'Cache-Control': 'private, max-age=30' } },
  );
}

async function laravelBookings(req: Request, requestId: string) {
  const cookie = req.headers.get('cookie');
  const actor = resolveCustomerActor(cookie, customerTokenSecret());
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  try {
    const page: LaravelResponse<LaravelReservation[]> = await laravelGet<LaravelReservation[]>(readCustomerToken(cookie), '/booking/reservations', { limit: '25' }, LIST_CODE_MAP);
    const items = (page.data ?? []).map(toBookingCard);
    return Response.json(
      ok(
        {
          items,
          pagination: {
            hasMore: page.meta?.page?.has_more ?? false,
            nextCursor: page.meta?.page?.next_cursor ?? null,
          },
        },
        requestId,
      ),
      { headers: { 'Cache-Control': 'private, max-age=30' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Bookings are unavailable.', requestId);
  }
}
