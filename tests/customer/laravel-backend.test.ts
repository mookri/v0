import { test, expect, vi, afterEach } from 'vitest';
import { mintActorToken } from '@navdhya/identity/src/application/token-actor';
import {
  laravelEnabled,
  laravelBase,
  mapBackendCode,
} from '@apps/customer/adapters/backend/laravel-client';
import {
  customerTokenCookieName,
  readCustomerToken,
  resolveCustomerActor,
  buildSessionCookie,
  clearSessionCookie,
  BackendSessionAdapter,
} from '@apps/customer/server/backend-session';
import { GET as listBookings } from '@apps/customer/app/api/bookings/route';
import { GET as bookingDetail } from '@apps/customer/app/api/bookings/[bookingId]/route';
import { GET as templeDetail } from '@apps/customer/app/api/temples/[templeId]/route';
import { GET as listTemples } from '@apps/customer/app/api/temples/route';
import { GET as templePujas } from '@apps/customer/app/api/temples/[templeId]/pujas/route';
import { GET as templeAvailability } from '@apps/customer/app/api/temples/[templeId]/availability/route';
import { GET as panditDetail } from '@apps/customer/app/api/pandits/[panditId]/route';
import { GET as listPandits } from '@apps/customer/app/api/pandits/route';
import { GET as panditAvailability } from '@apps/customer/app/api/pandits/[panditId]/availability/route';
import { GET as instantSearch } from '@apps/customer/app/api/instant-search/route';
import { POST as login } from '@apps/customer/app/api/auth/login/route';
import { GET as getProfile } from '@apps/customer/app/api/profile/route';
import { GET as listSaved } from '@apps/customer/app/api/saved/route';
import { GET as listNotifications } from '@apps/customer/app/api/notifications/route';
import { GET as getDiscover } from '@apps/customer/app/api/discover/route';
import { GET as getSettings } from '@apps/customer/app/api/settings/route';
import { POST as cancelBooking } from '@apps/customer/app/api/bookings/[bookingId]/cancel/route';
import { POST as rescheduleBooking } from '@apps/customer/app/api/bookings/[bookingId]/reschedule/route';

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
});

const SECRET = 'f2-test-secret';

function wiredEnv() {
  vi.stubEnv('CUSTOMER_BACKEND', 'laravel');
  vi.stubEnv('NAVDHYA_API_BASE', 'https://api.example.test');
  vi.stubEnv('CUSTOMER_TENANT_ID', 'tenant_1');
  vi.stubEnv('SESSION_SIGNING_KEY', SECRET);
}

function backendToken() {
  return mintActorToken(
    SECRET,
    { userId: 'cust_1', roles: [], sessionId: 'sess_1' },
    new Date().toISOString(),
    3600,
  );
}

function cookieHeader(token: string) {
  return `${customerTokenCookieName()}=${encodeURIComponent(token)}`;
}

function stubLaravel(handler: (url: string, init?: RequestInit) => unknown) {
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const body = handler(url, init);
      return new Response(JSON.stringify(body), { status: 200 });
    }),
  );
}

function stubLaravelStatuses(handler: (url: string, init?: RequestInit) => { status: number; body: unknown }) {
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const { status, body } = handler(url, init);
      return new Response(JSON.stringify(body), { status });
    }),
  );
}

function authed(url: string, init?: RequestInit) {
  return new Request(url, { headers: { cookie: cookieHeader(backendToken()) }, ...init });
}

test('wiring is off by default', () => {
  vi.stubEnv('CUSTOMER_BACKEND', '');
  expect(laravelEnabled()).toBe(false);
  expect(laravelBase()).toBe('');
});

test('backend code map covers auth-shaped errors', () => {
  expect(mapBackendCode('unauthenticated')).toBe('UNAUTHENTICATED');
  expect(mapBackendCode('membership_inactive')).toBe('UNAUTHENTICATED');
  expect(mapBackendCode('validation_failed')).toBe('VALIDATION_ERROR');
  expect(mapBackendCode('not_found', { not_found: 'BOOKING_NOT_FOUND' })).toBe('BOOKING_NOT_FOUND');
  expect(mapBackendCode('version_conflict')).toBe('STALE_RESOURCE');
  expect(mapBackendCode('nope')).toBe('INTERNAL_ERROR');
});

test('session cookie helpers round-trip', () => {
  const set = buildSessionCookie('tok', 3600);
  expect(set).toContain(`${customerTokenCookieName()}=tok`);
  expect(set).toContain('HttpOnly');
  expect(readCustomerToken(`${customerTokenCookieName()}=tok; other=x`)).toBe('tok');
  expect(readCustomerToken(null)).toBeNull();
  expect(clearSessionCookie()).toContain('Max-Age=0');
});

test('resolver accepts backend-minted bearers', async () => {
  const token = backendToken();
  const actor = resolveCustomerActor(cookieHeader(token), SECRET);
  expect(actor).toEqual({ userId: 'cust_1', sessionId: 'sess_1' });
  expect(resolveCustomerActor(cookieHeader('garbage'), SECRET)).toBeNull();
  const adapter = new BackendSessionAdapter(cookieHeader(token), SECRET);
  expect(await adapter.getSession()).toEqual({ authenticated: true, actor: { userId: 'cust_1' } });
});

test('bookings list maps backend reservations when wired', async () => {
  wiredEnv();
  stubLaravel((url) => {
    expect(url).toContain('/api/v1/booking/reservations');
    return {
      data: [
        { id: 'b1', status: 'CONFIRMED', service_sku: 'PUJA-X', scheduled_at: '2030-05-01T10:00:00', version: 1, actions: ['cancel', 'reschedule'] },
        { id: 'b2', status: 'COMPLETED', service_sku: null, scheduled_at: null, version: 3, actions: [] },
      ],
      meta: { request_id: 'req1', page: { limit: 25, has_more: false, next_cursor: null } },
    };
  });
  const res = await listBookings(new Request('https://app.navdhya.com/api/bookings', { headers: { cookie: cookieHeader(backendToken()) } }));
  expect(res.status).toBe(200);
  const body = (await res.json()) as { data: { items: { id: string; status: string; date: string; itemCount: number }[] } };
  expect(body.data.items).toEqual([
    { id: 'b1', status: 'CONFIRMED', date: '2030-05-01', itemCount: 1 },
    { id: 'b2', status: 'COMPLETED', date: '', itemCount: 1 },
  ]);
});

test('bookings list serves the empty mock fixture without the flag', async () => {
  const res = await listBookings(new Request('https://app.navdhya.com/api/bookings'));
  expect(res.status).toBe(200);
  const body = (await res.json()) as { data: { items: unknown[] } };
  expect(body.data.items).toEqual([]);
});

test('booking detail maps actions and static entries', async () => {
  wiredEnv();
  stubLaravel(() => ({
    data: { id: 'b1', status: 'CONFIRMED', service_sku: 'PUJA-X', scheduled_at: '2030-05-01T10:00:00', version: 1, actions: ['cancel', 'reschedule'] },
    meta: { request_id: 'req1' },
  }));
  const res = await bookingDetail(
    new Request('https://app.navdhya.com/api/bookings/b1', { headers: { cookie: cookieHeader(backendToken()) } }),
    { params: Promise.resolve({ bookingId: 'b1' }) },
  );
  expect(res.status).toBe(200);
  const body = (await res.json()) as { data: { puja: { id: string }; actions: string[]; schedule: { date: string } } };
  expect(body.data.puja.id).toBe('PUJA-X');
  expect(body.data.actions).toEqual(['cancel', 'reschedule', 'view-puja', 'contact-support']);
  expect(body.data.schedule.date).toBe('2030-05-01');
});

test('wired login redeems the challenge token in one call and sets the cookie', async () => {
  wiredEnv();
  const calls: { url: string; body: unknown }[] = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const body = init?.body !== undefined ? JSON.parse(init.body as string) : undefined;
      calls.push({ url, body });
      return new Response(
        JSON.stringify({
          data: { token: 'tok', expires_at: '2030-01-01T00:00:00.000Z', tenant_id: 'tenant_1', actor: { user_id: 'cust_1', session_id: 'sess_9' } },
          meta: { request_id: 'r2' },
        }),
        { status: 200 },
      );
    }),
  );
  const res = await login(
    new Request('https://app.navdhya.com/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ challengeId: 'ch_1', token: 'tok_widget_abc' }),
    }),
  );
  expect(res.status).toBe(200);
  expect(calls).toEqual([
    {
      url: 'https://api.example.test/api/v1/customers/auth/login',
      body: { tenant_id: 'tenant_1', challenge_id: 'ch_1', token: 'tok_widget_abc' },
    },
  ]);
  const body = (await res.json()) as { data: { sessionId: string; expiresAt: string } };
  expect(body.data.sessionId).toBe('sess_9');
  expect(res.headers.get('Set-Cookie')).toContain(customerTokenCookieName());
});

test('wired reads pass backend data through with BFF shapes', async () => {
  wiredEnv();
  stubLaravel((url) => {
    if (url.includes('/customers/profile')) {
      return { data: { id: 'p1', object: 'buyer_profile', status: 'active' }, meta: { request_id: 'r' } };
    }
    if (url.includes('/customers/saved')) {
      return {
        data: [{ id: 's1', ref_type: 'puja', ref_id: 'ABCDEF1234567890ABCDEF12', saved_at: null }],
        meta: { request_id: 'r', page: { limit: 25, has_more: false, next_cursor: null } },
      };
    }
    if (url.includes('/customers/notifications')) {
      return {
        data: [{ id: 'n1', kind: 'booking', title: 'T', read_at: null }],
        meta: { request_id: 'r', page: { limit: 25, has_more: false, next_cursor: null } },
      };
    }
    if (url.includes('/customers/discover')) {
      return {
        data: { recommended: [], popular: [{ id: 'c1', title: 'Puja', kind: 'puja' }], recent: [] },
        meta: { request_id: 'r' },
      };
    }
    return {
      data: { object: 'customer_settings', settings: { locale: 'hi' } },
      meta: { request_id: 'r' },
    };
  });

  const profile = await getProfile(authed('https://app.navdhya.com/api/profile'));
  expect(profile.status).toBe(200);
  expect(((await profile.json()) as { data: { profile: { status: string } } }).data.profile.status).toBe('active');

  const saved = await listSaved(authed('https://app.navdhya.com/api/saved'));
  const savedBody = (await saved.json()) as { data: { items: { id: string; type: string; title: string }[] } };
  expect(savedBody.data.items).toEqual([
    { id: 's1', type: 'puja', title: 'ABCDEF1234567890ABCDEF12', href: '/pujas/ABCDEF1234567890ABCDEF12' },
  ]);

  const notifications = await listNotifications(authed('https://app.navdhya.com/api/notifications'));
  expect(notifications.status).toBe(200);

  const discover = await getDiscover(authed('https://app.navdhya.com/api/discover'));
  const discoverBody = (await discover.json()) as { data: { popular: { id: string }[] } };
  expect(discoverBody.data.popular).toEqual([{ id: 'c1', title: 'Puja', kind: 'puja' }]);

  const settings = await getSettings(authed('https://app.navdhya.com/api/settings'));
  const settingsBody = (await settings.json()) as { data: { values: { locale: string } } };
  expect(settingsBody.data.values.locale).toBe('hi');
});

test('wired cancel maps not_found to BOOKING_NOT_FOUND', async () => {
  wiredEnv();
  stubLaravelStatuses(() => ({
    status: 404,
    body: { error: { code: 'not_found', message: 'Missing.', requestId: 'r' } },
  }));
  const res = await cancelBooking(authed('https://app.navdhya.com/api/bookings/nope/cancel', { method: 'POST' }), {
    params: Promise.resolve({ bookingId: 'nope' }),
  });
  expect(res.status).toBe(404);
  expect(((await res.json()) as { error: { code: string } }).error.code).toBe('BOOKING_NOT_FOUND');
});

test('wired temple detail maps TEMPLE_NOT_FOUND to NOT_FOUND', async () => {
  wiredEnv();
  stubLaravelStatuses(() => ({
    status: 404,
    body: { error: { code: 'TEMPLE_NOT_FOUND', message: 'Temple not found.', requestId: 'r' } },
  }));
  const res = await templeDetail(authed('https://app.navdhya.com/api/temples/t1'), {
    params: Promise.resolve({ templeId: 't1' }),
  });
  expect(res.status).toBe(404);
  expect(((await res.json()) as { error: { code: string } }).error.code).toBe('NOT_FOUND');
});

test('wired pandit detail maps PANDIT_NOT_FOUND to NOT_FOUND', async () => {
  wiredEnv();
  stubLaravelStatuses(() => ({
    status: 404,
    body: { error: { code: 'PANDIT_NOT_FOUND', message: 'Pandit not found.', requestId: 'r' } },
  }));
  const res = await panditDetail(authed('https://app.navdhya.com/api/pandits/p1'), {
    params: Promise.resolve({ panditId: 'p1' }),
  });
  expect(res.status).toBe(404);
  expect(((await res.json()) as { error: { code: string } }).error.code).toBe('NOT_FOUND');
});

test('wired pandit list forwards cursor and limit untouched and returns next cursor', async () => {
  wiredEnv();
  stubLaravel((url) => {
    expect(url).toContain('/api/v1/pandit/profiles');
    expect(url).toContain('limit=2');
    expect(url).toContain('cursor=CUR_PAGE_ONE');
    return {
      data: [{ id: 'p1' }, { id: 'p2' }],
      meta: { page: { limit: 2, has_more: true, next_cursor: 'CUR_PAGE_TWO' } },
    };
  });

  const res = await listPandits(authed('https://app.navdhya.com/api/pandits?limit=2&cursor=CUR_PAGE_ONE'));
  expect(res.status).toBe(200);
  const body = (await res.json()) as {
    data: { items: { id: string }[]; pagination: { hasMore: boolean; nextCursor: string | null } };
  };
  expect(body.data.items.map((i) => i.id)).toEqual(['p1', 'p2']);
  expect(body.data.pagination).toEqual({ hasMore: true, nextCursor: 'CUR_PAGE_TWO' });
});

test('wired pandit list reports an exhausted last page', async () => {
  wiredEnv();
  stubLaravel(() => ({
    data: [{ id: 'p3' }],
    meta: { page: { limit: 2, has_more: false, next_cursor: null } },
  }));

  const res = await listPandits(authed('https://app.navdhya.com/api/pandits?limit=2&cursor=CUR_PAGE_TWO'));
  expect(res.status).toBe(200);
  const body = (await res.json()) as {
    data: { items: { id: string }[]; pagination: { hasMore: boolean; nextCursor: string | null } };
  };
  expect(body.data.items).toHaveLength(1);
  expect(body.data.pagination).toEqual({ hasMore: false, nextCursor: null });
});

test('wired pandit availability maps PANDIT_NOT_FOUND to NOT_FOUND', async () => {
  wiredEnv();
  stubLaravelStatuses(() => ({
    status: 404,
    body: { error: { code: 'PANDIT_NOT_FOUND', message: 'Pandit not found.', requestId: 'r' } },
  }));
  const res = await panditAvailability(authed('https://app.navdhya.com/api/pandits/p1/availability'), {
    params: Promise.resolve({ panditId: 'p1' }),
  });
  expect(res.status).toBe(404);
  expect(((await res.json()) as { error: { code: string } }).error.code).toBe('NOT_FOUND');
});

test('wired reschedule reads version then moves', async () => {
  wiredEnv();
  const calls: { url: string; body: unknown }[] = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const body = init?.body !== undefined ? JSON.parse(init.body as string) : undefined;
      calls.push({ url, body });
      const payload = url.endsWith(':reschedule')
        ? { data: { id: 'b1', status: 'CONFIRMED', service_sku: null, scheduled_at: '2030-06-01T10:00:00', version: 2, actions: [] }, meta: { request_id: 'r' } }
        : { data: { id: 'b1', status: 'CONFIRMED', service_sku: null, scheduled_at: '2030-05-01T10:00:00', version: 1, actions: ['reschedule'] }, meta: { request_id: 'r' } };
      return new Response(JSON.stringify(payload), { status: 200 });
    }),
  );
  const res = await rescheduleBooking(
    authed('https://app.navdhya.com/api/bookings/b1/reschedule', {
      method: 'POST',
      body: JSON.stringify({ newStartTime: '2030-06-01T10:00:00.000Z' }),
    }),
    { params: Promise.resolve({ bookingId: 'b1' }) },
  );
  expect(res.status).toBe(200);
  expect(calls).toEqual([
    { url: 'https://api.example.test/api/v1/booking/reservations/b1', body: undefined },
    {
      url: 'https://api.example.test/api/v1/booking/reservations/b1:reschedule',
      body: { new_start_time: '2030-06-01T10:00:00.000Z', version: 1 },
    },
  ]);
  const moved = (await res.json()) as { data: { schedule: { date: string }; actions: string[] } };
  expect(moved.data.schedule.date).toBe('2030-06-01');
});

test('wired home composes four backend reads', async () => {
  wiredEnv();
  stubLaravel((url) => {
    if (url.includes('/customers/profile')) {
      return { data: { display_name: 'Ravi', name: 'Ravi' }, meta: { request_id: 'r' } };
    }
    if (url.includes('/booking/reservations')) {
      return {
        data: [{ id: 'b1', status: 'CONFIRMED', service_sku: null, scheduled_at: '2030-05-01T10:00:00', version: 1, actions: [] }],
        meta: { request_id: 'r', page: { limit: 1, has_more: false, next_cursor: null } },
      };
    }
    if (url.includes('/customers/discover')) {
      return {
        data: { recommended: [{ id: 'c1', title: 'Puja', kind: 'puja' }], popular: [], recent: [] },
        meta: { request_id: 'r' },
      };
    }
    return {
      data: {
        items: [{ id: 'a1', kind: 'login', label: 'Signed in', at: null }],
        pagination: { hasMore: false, nextCursor: null },
      },
      meta: { request_id: 'r', page: { limit: 5, has_more: false, next_cursor: null } },
    };
  });
  const { GET: getHome } = await import('@apps/customer/app/api/home/route');
  const res = await getHome(authed('https://app.navdhya.com/api/home'));
  expect(res.status).toBe(200);
  const body = (await res.json()) as {
    data: {
      greeting: { displayName: string };
      upcomingBooking?: { id: string };
      recommendations: { id: string }[];
      recentActivity: { id: string; text: string }[];
    };
  };
  expect(body.data.greeting.displayName).toBe('Ravi');
  expect(body.data.upcomingBooking?.id).toBe('b1');
  expect(body.data.recommendations).toEqual([{ id: 'c1', title: 'Puja' }]);
  expect(body.data.recentActivity).toEqual([{ id: 'a1', text: 'Signed in' }]);
});

test('new wired surfaces pass through with BFF shapes', async () => {
  wiredEnv();
  stubLaravel((url, init) => {
    if (url.includes('/customers/addresses') && init?.method !== 'POST') {
      return {
        data: [{ id: 'a1', label: 'home' }],
        meta: { request_id: 'r', page: { limit: 25, has_more: false, next_cursor: null } },
      };
    }
    if (url.includes('/customers/activity')) {
      return {
        data: [{ id: 'a1', kind: 'login', label: 'Signed in' }],
        meta: { request_id: 'r', page: { limit: 25, has_more: false, next_cursor: null } },
      };
    }
    if (url.includes('/customers/support/tickets') && !url.includes('/messages') && !url.includes(':close')) {
      return { data: { id: 't1', status: 'OPEN' }, meta: { request_id: 'r' } };
    }
    if (url.includes('/customers/referrals')) {
      return { data: { code: 'REF-ABC', referred_count: 0, referrals: [] }, meta: { request_id: 'r' } };
    }
    if (url.includes('/booking/reviews')) {
      return {
        data: [{ id: 'r1', rating: 5 }],
        meta: { request_id: 'r', page: { limit: 25, has_more: false, next_cursor: null } },
      };
    }
    return { data: { id: 'x1', status: 'OPEN' }, meta: { request_id: 'r' } };
  });
  const { GET: listAddresses } = await import('@apps/customer/app/api/addresses/route');
  const { GET: listActivity } = await import('@apps/customer/app/api/activity/route');
  const { GET: listTickets } = await import('@apps/customer/app/api/support/tickets/route');
  const { GET: getReferrals } = await import('@apps/customer/app/api/referrals/route');
  const { GET: listReviews } = await import('@apps/customer/app/api/reviews/route');

  const addresses = await listAddresses(authed('https://app.navdhya.com/api/addresses'));
  expect(addresses.status).toBe(200);
  expect(((await addresses.json()) as { data: { items: unknown[] } }).data.items).toHaveLength(1);

  const activity = await listActivity(authed('https://app.navdhya.com/api/activity'));
  expect(activity.status).toBe(200);

  const tickets = await listTickets(authed('https://app.navdhya.com/api/support/tickets'));
  expect(tickets.status).toBe(200);

  const referrals = await getReferrals(authed('https://app.navdhya.com/api/referrals'));
  const referralsBody = (await referrals.json()) as { data: { code: string } };
  expect(referralsBody.data.code).toBe('REF-ABC');

  const reviews = await listReviews(authed('https://app.navdhya.com/api/reviews'));
  expect(reviews.status).toBe(200);

  // Flag off: new surfaces are unauthenticated-only (no mock data).
  vi.stubEnv('CUSTOMER_BACKEND', '');
  const cold = await listAddresses(new Request('https://app.navdhya.com/api/addresses'));
  expect(cold.status).toBe(401);
});

test('wired signup intent and verify set the session cookie', async () => {
  wiredEnv();
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string) => {
      const body = url.endsWith('/customers/auth/signup')
        ? { data: { challenge_id: 'ch_9', expires_in: 600 }, meta: { request_id: 'r1' } }
        : { data: { token: 'tok', expires_at: '2030-01-01T00:00:00.000Z', tenant_id: 'tenant_1', actor: { user_id: 'cust_9', session_id: 'sess_9' } }, meta: { request_id: 'r2' } };
      return new Response(JSON.stringify(body), { status: 200 });
    }),
  );
  const { POST: signup } = await import('@apps/customer/app/api/auth/signup/route');
  const { POST: verify } = await import('@apps/customer/app/api/auth/verify/route');

  const intent = await signup(
    new Request('https://app.navdhya.com/api/auth/signup', {
      method: 'POST',
      body: JSON.stringify({ phone: '+911234567890', name: 'Ravi' }),
    }),
  );
  expect(intent.status).toBe(202);
  expect(((await intent.json()) as { data: { challengeId: string } }).data.challengeId).toBe('ch_9');

  const done = await verify(
    new Request('https://app.navdhya.com/api/auth/verify', {
      method: 'POST',
      body: JSON.stringify({ challengeId: 'ch_9', token: 'tok_widget_xyz' }),
    }),
  );
  expect(done.status).toBe(201);
  const doneBody = (await done.json()) as { data: { sessionId: string } };
  expect(doneBody.data.sessionId).toBe('sess_9');
  expect(done.headers.get('Set-Cookie')).toContain(customerTokenCookieName());

  // Flag off: unauthenticated-only.
  vi.stubEnv('CUSTOMER_BACKEND', '');
  const cold = await signup(
    new Request('https://app.navdhya.com/api/auth/signup', {
      method: 'POST',
      body: JSON.stringify({ phone: '+911234567890', name: 'Ravi' }),
    }),
  );
  expect(cold.status).toBe(401);
});

test('wired temple directory returns the {data, meta} payload the page reads', async () => {
  wiredEnv();
  stubLaravel((url) => {
    expect(url).toContain('/api/v1/temple/profiles');
    expect(url).toContain('limit=20');
    return {
      data: [{ id: 't1', name: 'Kashi Vishwanath' }],
      meta: { request_id: 'kernel-req-1', page: { limit: 20, has_more: true, next_cursor: 'CUR_PAGE_TWO' } },
    };
  });

  const res = await listTemples(authed('https://app.navdhya.com/api/temples?limit=20'));
  expect(res.status).toBe(200);
  const body = (await res.json()) as {
    data: { data: { id: string }[]; meta: { page: { has_more: boolean; next_cursor: string | null } } };
  };
  // (protected)/temples/page.tsx reads payload.data — a bare array here
  // would render an empty directory in wired mode.
  expect(body.data.data.map((t) => t.id)).toEqual(['t1']);
  expect(body.data.meta.page).toEqual({ limit: 20, has_more: true, next_cursor: 'CUR_PAGE_TWO' });
});

test('wired temple pujas forward limit/cursor and return the page payload', async () => {
  wiredEnv();
  stubLaravel((url) => {
    expect(url).toContain('/api/v1/temple/profiles/t1/pujas');
    expect(url).toContain('limit=2');
    expect(url).toContain('cursor=CUR1');
    return {
      data: [
        {
          id: 'as1',
          temple_id: 't1',
          puja_id: 'p1',
          price_override_minor: 210000,
          capacity: 40,
          schedule_json: null,
          status: 'ACTIVE',
          created_at: '2030-01-01T00:00:00.000Z',
        },
      ],
      meta: { request_id: 'kernel-req-2', page: { limit: 2, has_more: true, next_cursor: 'CUR2' } },
    };
  });

  const res = await templePujas(authed('https://app.navdhya.com/api/temples/t1/pujas?limit=2&cursor=CUR1'), {
    params: Promise.resolve({ templeId: 't1' }),
  });
  expect(res.status).toBe(200);
  const body = (await res.json()) as {
    data: { data: { id: string; puja_id: string }[]; meta: { page: { has_more: boolean; next_cursor: string | null } } };
  };
  expect(body.data.data[0]).toMatchObject({ id: 'as1', puja_id: 'p1' });
  expect(body.data.meta.page).toEqual({ limit: 2, has_more: true, next_cursor: 'CUR2' });
});

test('wired temple pujas map TEMPLE_NOT_FOUND to NOT_FOUND', async () => {
  wiredEnv();
  stubLaravelStatuses(() => ({
    status: 404,
    body: { error: { code: 'TEMPLE_NOT_FOUND', message: 'Temple not found.', requestId: 'r' } },
  }));
  const res = await templePujas(authed('https://app.navdhya.com/api/temples/t404/pujas'), {
    params: Promise.resolve({ templeId: 't404' }),
  });
  expect(res.status).toBe(404);
  expect(((await res.json()) as { error: { code: string } }).error.code).toBe('NOT_FOUND');
});

test('wired temple availability forwards dates and unwraps to a bare slots array', async () => {
  wiredEnv();
  stubLaravel((url) => {
    expect(url).toContain('/api/v1/temple/profiles/t1/availability');
    expect(url).toContain('start_date=2030-05-01');
    expect(url).toContain('end_date=2030-05-07');
    return {
      data: [
        {
          assignment_id: 'as1',
          puja_id: 'p1',
          day_of_week: 3,
          start: '09:00',
          end: '11:00',
          capacity: 40,
          price_override_minor: null,
        },
      ],
      meta: { request_id: 'kernel-req-3' },
    };
  });

  const res = await templeAvailability(
    authed('https://app.navdhya.com/api/temples/t1/availability?start_date=2030-05-01&end_date=2030-05-07'),
    { params: Promise.resolve({ templeId: 't1' }) },
  );
  expect(res.status).toBe(200);
  const body = (await res.json()) as { data: { assignment_id: string; day_of_week: number }[] };
  expect(body.data).toHaveLength(1);
  expect(body.data[0]).toMatchObject({ assignment_id: 'as1', day_of_week: 3 });
});

test('wired temple availability maps 404 and 422 to BFF codes', async () => {
  wiredEnv();
  stubLaravelStatuses((url) =>
    url.includes('start_date=broken')
      ? { status: 422, body: { error: { code: 'validation_failed', message: 'Invalid dates.', requestId: 'r' } } }
      : { status: 404, body: { error: { code: 'TEMPLE_NOT_FOUND', message: 'Temple not found.', requestId: 'r' } } },
  );

  const missing = await templeAvailability(authed('https://app.navdhya.com/api/temples/t1/availability'), {
    params: Promise.resolve({ templeId: 't1' }),
  });
  expect(missing.status).toBe(404);
  expect(((await missing.json()) as { error: { code: string } }).error.code).toBe('NOT_FOUND');

  const invalid = await templeAvailability(
    authed('https://app.navdhya.com/api/temples/t1/availability?start_date=broken'),
    { params: Promise.resolve({ templeId: 't1' }) },
  );
  expect(invalid.status).toBe(422);
  expect(((await invalid.json()) as { error: { code: string } }).error.code).toBe('VALIDATION_ERROR');
});

test('wired pandit availability unwraps to a bare slots array', async () => {
  wiredEnv();
  stubLaravel((url) => {
    expect(url).toContain('/api/v1/pandit/profiles/p1/availability');
    return {
      data: [
        {
          id: 'sl1',
          day_of_week: 3,
          start_time: '09:00',
          end_time: '11:00',
          slot_duration_minutes: 30,
          is_recurring: true,
          specific_date: null,
        },
      ],
      meta: { request_id: 'kernel-req-4' },
    };
  });

  const res = await panditAvailability(authed('https://app.navdhya.com/api/pandits/p1/availability'), {
    params: Promise.resolve({ panditId: 'p1' }),
  });
  expect(res.status).toBe(200);
  const body = (await res.json()) as { data: { id: string }[] };
  expect(body.data.map((s) => s.id)).toEqual(['sl1']);
});

test('new temple surfaces stay unauthenticated-only on mocks', async () => {
  vi.stubEnv('CUSTOMER_BACKEND', '');

  const pujas = await templePujas(new Request('https://app.navdhya.com/api/temples/t1/pujas'), {
    params: Promise.resolve({ templeId: 't1' }),
  });
  expect(pujas.status).toBe(401);
  expect(((await pujas.json()) as { error: { code: string } }).error.code).toBe('UNAUTHENTICATED');

  const availability = await templeAvailability(new Request('https://app.navdhya.com/api/temples/t1/availability'), {
    params: Promise.resolve({ templeId: 't1' }),
  });
  expect(availability.status).toBe(401);
});

test('wired instant search forwards the window untouched and unwraps the results', async () => {
  wiredEnv();
  stubLaravel((rawUrl) => {
    const u = new URL(rawUrl);
    expect(u.pathname).toBe('/api/v1/booking/instant-search');
    expect(u.searchParams.get('date')).toBe('2030-05-01');
    expect(u.searchParams.get('start')).toBe('10:00');
    expect(u.searchParams.get('end')).toBe('11:00');
    expect(u.searchParams.get('puja_id')).toBe('PUJA123');
    return {
      data: {
        object: 'instant_search',
        date: '2030-05-01',
        start: '10:00',
        end: '11:00',
        puja_id: 'PUJA123',
        pandits: [
          { id: 'pd1', display_name: 'Acharya Dev', rating_avg: 4.8, rating_count: 12, specializations: ['griha-pravesh'], base_rate_minor: 150000, currency: 'INR' },
        ],
        temple_slots: [
          { temple_id: 't1', temple_name: 'Sri Ram Temple', city: 'Delhi', puja_id: 'PUJA123', day_of_week: 5, start: '10:00', end: '12:00', capacity: 7, price_override_minor: null },
        ],
      },
      meta: { request_id: 'r1' },
    };
  });

  const res = await instantSearch(
    authed('https://app.navdhya.com/api/instant-search?date=2030-05-01&start=10:00&end=11:00&puja_id=PUJA123'),
  );
  expect(res.status).toBe(200);
  const body = (await res.json()) as {
    data: { pandits: { id: string }[]; temple_slots: { temple_id: string }[] };
  };
  expect(body.data.pandits.map((p) => p.id)).toEqual(['pd1']);
  expect(body.data.temple_slots[0].temple_id).toBe('t1');
  // Search results are window-sensitive: never cacheable at the edge.
  expect(res.headers.get('Cache-Control')).toContain('max-age=0');
});

test('wired instant search maps backend validation failures to VALIDATION_ERROR', async () => {
  wiredEnv();
  stubLaravelStatuses(() => ({
    status: 422,
    body: { error: { code: 'validation_failed', message: 'Invalid window.', requestId: 'r' } },
  }));

  const res = await instantSearch(authed('https://app.navdhya.com/api/instant-search?date=yesterday&start=10:00&end=11:00'));
  expect(res.status).toBe(422);
  expect(((await res.json()) as { error: { code: string } }).error.code).toBe('VALIDATION_ERROR');
});

test('instant search serves an empty mock result without the flag', async () => {
  vi.stubEnv('CUSTOMER_BACKEND', '');

  const res = await instantSearch(
    new Request('https://app.navdhya.com/api/instant-search?date=2030-05-01&start=10:00&end=11:00'),
  );
  // The mock branch is a documented empty result, not an auth failure:
  // there is no actor gate to satisfy in the default backend mode.
  expect(res.status).toBe(200);
  const body = (await res.json()) as {
    data: { object: string; pandits: unknown[]; temple_slots: unknown[] };
  };
  expect(body.data.object).toBe('instant_search');
  expect(body.data.pandits).toEqual([]);
  expect(body.data.temple_slots).toEqual([]);
});
