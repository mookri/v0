import { test, expect, vi, afterEach } from 'vitest';
import { mintActorToken } from '@navdhya/identity/src/application/token-actor';
import {
  laravelEnabled,
  laravelBase,
  mapBackendCode,
} from '@apps/customer/adapters/backend/laravel-client';
import {
  CUSTOMER_TOKEN_COOKIE,
  readCustomerToken,
  resolveCustomerActor,
  buildSessionCookie,
  clearSessionCookie,
  BackendSessionAdapter,
} from '@apps/customer/server/backend-session';
import { GET as listBookings } from '@apps/customer/app/api/bookings/route';
import { GET as bookingDetail } from '@apps/customer/app/api/bookings/[bookingId]/route';
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
  return `${CUSTOMER_TOKEN_COOKIE}=${encodeURIComponent(token)}`;
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
  expect(set).toContain(`${CUSTOMER_TOKEN_COOKIE}=tok`);
  expect(set).toContain('HttpOnly');
  expect(readCustomerToken(`${CUSTOMER_TOKEN_COOKIE}=tok; other=x`)).toBe('tok');
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

test('bookings list stays on mocks without the flag', async () => {
  const res = await listBookings(new Request('https://app.navdhya.com/api/bookings'));
  expect(res.status).toBe(401);
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

test('wired login fans out to otp+redeem and sets the cookie', async () => {
  wiredEnv();
  const calls: { url: string; body: unknown }[] = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const body = init?.body !== undefined ? JSON.parse(init.body as string) : undefined;
      calls.push({ url, body });
      const payload =
        url.endsWith('/customers/auth/otp')
          ? { data: { challenge_id: 'ch_1', expires_in: 600 }, meta: { request_id: 'r1' } }
          : { data: { token: 'tok', expires_at: '2030-01-01T00:00:00.000Z', tenant_id: 'tenant_1', actor: { user_id: 'cust_1', session_id: 'sess_9' } }, meta: { request_id: 'r2' } };
      return new Response(JSON.stringify(payload), { status: 200 });
    }),
  );
  const res = await login(
    new Request('https://app.navdhya.com/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ identityId: '+911234567890', proof: '123456' }),
    }),
  );
  expect(res.status).toBe(200);
  expect(calls).toEqual([
    {
      url: 'https://api.example.test/api/v1/customers/auth/otp',
      body: { tenant_id: 'tenant_1', purpose: 'login', identity: '+911234567890' },
    },
    {
      url: 'https://api.example.test/api/v1/customers/auth/login',
      body: { tenant_id: 'tenant_1', challenge_id: 'ch_1', code: '123456' },
    },
  ]);
  const body = (await res.json()) as { data: { sessionId: string; expiresAt: string } };
  expect(body.data.sessionId).toBe('sess_9');
  expect(res.headers.get('Set-Cookie')).toContain(CUSTOMER_TOKEN_COOKIE);
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
