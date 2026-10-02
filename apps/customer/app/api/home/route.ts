import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { getCustomerHome } from '@/application/home/get-customer-home';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { toBookingCard, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    return laravelHome(req, requestId);
  }
  const ctx = anonymousContext('customer.home', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  const data = getCustomerHome({ displayName: 'Friend' });
  return Response.json(ok(data, requestId), {
    headers: { 'Cache-Control': 'private, max-age=30' },
  });
}

// Wired home composes four backend reads (profile, next booking,
// recommendations, recent activity). Fail-closed: any dependency down
// renders 503, never a partial home.
async function laravelHome(req: Request, requestId: string) {
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const [profileRes, upcomingRes, discoverRes, activityRes] = await Promise.all([
      laravelGet<{ display_name?: string | null; name?: string }>(actor.token, '/customers/profile'),
      laravelGet<LaravelReservation[]>(actor.token, '/booking/reservations', { section: 'upcoming', limit: '1' }),
      laravelGet<{ recommended?: { id: string; title: string }[] }>(actor.token, '/customers/discover'),
      laravelGet<{ items: { id: string; label: string }[] }>(actor.token, '/customers/activity', { limit: '5' }),
    ]);
    const upcoming = (upcomingRes.data ?? [])[0];
    const data = getCustomerHome({
      displayName: profileRes.data.display_name ?? profileRes.data.name ?? 'Friend',
      upcoming: upcoming ? toBookingCard(upcoming) : undefined,
      recommendations: (discoverRes.data.recommended ?? []).map((item) => ({ id: item.id, title: item.title })),
      recentActivity: (activityRes.data.items ?? []).map((item) => ({ id: item.id, text: item.label })),
    });
    return Response.json(ok(data, requestId), {
      headers: { 'Cache-Control': 'private, max-age=30' },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Home is unavailable.', requestId);
  }
}
