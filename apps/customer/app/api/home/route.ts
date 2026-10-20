import { newRequestId, ok } from '@/application/api-envelope';
import { getCustomerHome } from '@/application/home/get-customer-home';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';
import { toBookingCard, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    return laravelHome(req, requestId);
  }
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
      laravelGet<LaravelReservation[]>(actor.token, '/booking/reservations', { section: 'upcoming', limit: '50' }),
      laravelGet<{ recommended?: { id: string; title: string }[] }>(actor.token, '/customers/discover'),
      laravelGet<{ items: { id: string; label: string }[] }>(actor.token, '/customers/activity', { limit: '5' }),
    ]);
    // The reservation endpoint paginates by created_at DESC, so a limit:1
    // read returns the newest booking, not the next ritual. Over-fetch the
    // upcoming section and pick the earliest scheduled_at.
    const upcoming = [...(upcomingRes.data ?? [])]
      .filter((booking) => Boolean(booking.scheduled_at))
      .sort(
        (a, b) =>
          Date.parse(String(a.scheduled_at)) - Date.parse(String(b.scheduled_at)),
      )[0];
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
