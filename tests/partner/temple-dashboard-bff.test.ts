import { test, expect, vi, afterEach } from 'vitest';
import { mintActorToken, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { GET as dashboardOverview } from '@apps/partner/app/api/temples/[templeId]/dashboard/overview/route';
import { GET as dashboardPujas } from '@apps/partner/app/api/temples/[templeId]/dashboard/pujas/route';
import { GET as dashboardBookings } from '@apps/partner/app/api/temples/[templeId]/dashboard/bookings/route';
import { GET as dashboardCalendar } from '@apps/partner/app/api/temples/[templeId]/dashboard/calendar/route';
import { GET as dashboardAnalytics } from '@apps/partner/app/api/temples/[templeId]/dashboard/analytics/route';
import { GET as dashboardSettings } from '@apps/partner/app/api/temples/[templeId]/dashboard/settings/route';

// Temple dashboard BFF (all six sections): mock default answers the
// section payload with no backend call, wired mode forwards bearer +
// tenant + section path with the page-facing reshape (kernel `data`
// unwrapped, then re-wrapped per section), and kernel codes map to the
// stable envelopes the pages catch. Pinned at the BFF boundary — the
// backend semantics live in TempleDashboardBookingTest (S14).

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
});

type SectionHandler = (
  req: Request,
  ctx: { params: Promise<{ templeId: string }> },
) => Promise<Response>;

const SECTIONS: Array<[string, SectionHandler]> = [
  ['overview', dashboardOverview],
  ['pujas', dashboardPujas],
  ['bookings', dashboardBookings],
  ['calendar', dashboardCalendar],
  ['analytics', dashboardAnalytics],
  ['settings', dashboardSettings],
];

const TOKEN = mintActorToken(
  INSECURE_DEV_SECRET,
  { userId: 'partner_1', sessionId: 'sess_1', roles: ['partner'] },
  new Date().toISOString(),
  3600,
);
const bearer = () => `Bearer ${TOKEN}`;

const authed = (url: string) => new Request(url, { headers: { authorization: bearer() } });
const ctx = () => ({ params: Promise.resolve({ templeId: 't1' }) });
const base = (section: string, query = '') =>
  `https://partner.navdhya.com/api/temples/t1/dashboard/${section}${query}`;
const json = async (res: Response) => (await res.json()) as Record<string, any>;

const kernel = (data: unknown, meta: Record<string, unknown> = { request_id: 'kernel-1' }) =>
  new Response(JSON.stringify({ data, meta }), { status: 200 });
const kernelError = (code: string, status: number) =>
  new Response(JSON.stringify({ error: { code, message: 'backend says no', requestId: 'r' } }), { status });

function wiredEnv() {
  vi.stubEnv('PARTNER_BACKEND', 'laravel');
  vi.stubEnv('NAVDHYA_API_BASE', 'https://api.example.test');
  vi.stubEnv('PARTNER_TENANT_ID', 'tenant_1');
}

const MOCK_PAYLOADS: Record<string, unknown> = {
  overview: {
    total_pujas: 0,
    active_pujas: 0,
    total_bookings: 0,
    upcoming_bookings: 0,
    revenue_minor: 0,
    avg_occupancy: 0,
    recent_bookings: [],
  },
  pujas: { data: [], meta: { page: { limit: 20, has_more: false, next_cursor: null } } },
  bookings: { data: [], meta: { page: { limit: 20, has_more: false, next_cursor: null } } },
  calendar: { items: [] },
  analytics: { revenue_by_month: [], occupancy_by_puja: [], peak_hours: [] },
  settings: {
    temple: expect.objectContaining({ id: '', status: 'INACTIVE', verification_status: 'PENDING' }),
    managers: [],
    amenities: [],
  },
};

test('mock sections answer 200 with their payload and never call the backend', async () => {
  const fetchMock = vi.fn();
  vi.stubGlobal('fetch', fetchMock);

  for (const [section, get] of SECTIONS) {
    const res = await get(authed(base(section)), ctx());
    expect(res.status, section).toBe(200);
    expect((await json(res)).data, section).toEqual(MOCK_PAYLOADS[section]);
  }
  expect(fetchMock).not.toHaveBeenCalled();
});

test('mock sections require an actor', async () => {
  for (const [section, get] of SECTIONS) {
    const res = await get(new Request(base(section)), ctx());
    expect(res.status, section).toBe(401);
    expect((await json(res)).error.code, section).toBe('UNAUTHENTICATED');
  }
});

test('wired overview forwards bearer + tenant and passes the kernel data through', async () => {
  wiredEnv();
  const calls: { url: string; authorization?: string; tenant?: string }[] = [];
  const overview = {
    total_pujas: 2,
    active_pujas: 1,
    total_bookings: 7,
    upcoming_bookings: 3,
    revenue_minor: 420000,
    avg_occupancy: 0,
    recent_bookings: [
      { id: 'b1', puja_name: 'Ganesh Puja', user_name: 'Asha', start_at: '2030-05-01T10:00:00', status: 'CONFIRMED' },
    ],
  };
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const headers = (init?.headers ?? {}) as Record<string, string>;
      calls.push({ url: String(url), authorization: headers['Authorization'], tenant: headers['X-Tenant-Id'] });
      return kernel(overview);
    }),
  );

  const res = await dashboardOverview(authed(base('overview')), ctx());
  expect(res.status).toBe(200);
  expect((await json(res)).data).toEqual(overview);
  expect(calls).toEqual([
    {
      url: 'https://api.example.test/api/v1/temple/partner/temples/t1/dashboard/overview',
      authorization: bearer(),
      tenant: 'tenant_1',
    },
  ]);
});

test('wired pujas and bookings re-wrap rows under {data, meta}', async () => {
  wiredEnv();
  const rows: Record<string, unknown[]> = {
    pujas: [{ id: 'as1', puja_name: 'Ganesh Puja', bookings_count: 3 }],
    bookings: [{ id: 'b1', puja_name: 'Ganesh Puja', user_name: 'Asha', status: 'CONFIRMED' }],
  };
  const page = { limit: 20, has_more: false, next_cursor: null };
  const seen: string[] = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string) => {
      const target = String(url);
      seen.push(target.split('/dashboard/')[1] ?? '');
      const section = target.endsWith('/pujas') ? 'pujas' : 'bookings';
      return kernel(rows[section], { request_id: 'kernel-1', page });
    }),
  );

  for (const [section, get] of [
    ['pujas', dashboardPujas],
    ['bookings', dashboardBookings],
  ] as [string, SectionHandler][]) {
    const res = await get(authed(base(section)), ctx());
    expect(res.status, section).toBe(200);
    const body = await json(res);
    expect(body.data.data, section).toEqual(rows[section]);
    expect(body.data.meta, section).toEqual({ request_id: 'kernel-1', page });
  }
  expect(seen).toEqual(['pujas', 'bookings']);
});

test('wired calendar defaults to the current month and wraps the day list', async () => {
  wiredEnv();
  const days = [
    {
      date: '2030-05-01',
      slots: [
        { id: 's1', puja_name: 'Ganesh Puja', start: '2030-05-01T10:00:00', end: '2030-05-01T11:00:00', capacity: 10, booked: 3 },
      ],
    },
  ];
  let seen = '';
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string) => {
      seen = String(url);
      return kernel(days);
    }),
  );

  const res = await dashboardCalendar(authed(base('calendar')), ctx());
  expect(res.status).toBe(200);
  // The page reads `{items}`; the backend requires year/month, so the BFF
  // defaults to the current month when the page passes none.
  expect((await json(res)).data).toEqual({ items: days });
  const now = new Date();
  expect(seen).toContain(`?year=${now.getUTCFullYear()}&month=${now.getUTCMonth() + 1}`);
});

test('wired calendar forwards explicit year/month untouched', async () => {
  wiredEnv();
  let seen = '';
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string) => {
      seen = String(url);
      return kernel([]);
    }),
  );

  const res = await dashboardCalendar(authed(base('calendar', '?year=2027&month=3')), ctx());
  expect(res.status).toBe(200);
  expect(seen).toBe('https://api.example.test/api/v1/temple/partner/temples/t1/dashboard/calendar?year=2027&month=3');
  expect((await json(res)).data).toEqual({ items: [] });
});

test('wired analytics forwards months when asked and omits it otherwise', async () => {
  wiredEnv();
  const analytics = { revenue_by_month: [], occupancy_by_puja: [], peak_hours: [] };
  const seen: string[] = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string) => {
      seen.push(String(url));
      return kernel(analytics);
    }),
  );

  const withMonths = await dashboardAnalytics(authed(base('analytics', '?months=6')), ctx());
  expect(withMonths.status).toBe(200);
  expect((await json(withMonths)).data).toEqual(analytics);

  const without = await dashboardAnalytics(authed(base('analytics')), ctx());
  expect(without.status).toBe(200);

  expect(seen[0]).toContain('?months=6');
  expect(seen[1]).not.toContain('months');
});

test('wired settings passes the temple payload through untouched', async () => {
  wiredEnv();
  const settings = {
    temple: { id: 't1', slug: 'sri-ram', name: 'Sri Ram Mandir', status: 'ACTIVE', verification_status: 'VERIFIED' },
    managers: [{ id: 'm1', role: 'owner' }],
    amenities: ['parking'],
  };
  vi.stubGlobal('fetch', vi.fn(async () => kernel(settings)));

  const res = await dashboardSettings(authed(base('settings')), ctx());
  expect(res.status).toBe(200);
  expect((await json(res)).data).toEqual(settings);
});

test('wired dashboard maps not_found to NOT_FOUND (overview resolves the temple row)', async () => {
  wiredEnv();
  vi.stubGlobal('fetch', vi.fn(async () => kernelError('not_found', 404)));

  const res = await dashboardOverview(authed(base('overview')), ctx());
  expect(res.status).toBe(404);
  expect((await json(res)).error.code).toBe('NOT_FOUND');
});

test('wired dashboard maps permission_missing to FORBIDDEN', async () => {
  wiredEnv();
  vi.stubGlobal('fetch', vi.fn(async () => kernelError('permission_missing', 403)));

  const res = await dashboardSettings(authed(base('settings')), ctx());
  expect(res.status).toBe(403);
  expect((await json(res)).error.code).toBe('FORBIDDEN');
});

test('wired dashboard maps validation_failed to VALIDATION_ERROR', async () => {
  wiredEnv();
  vi.stubGlobal('fetch', vi.fn(async () => kernelError('validation_failed', 422)));

  const res = await dashboardCalendar(authed(base('calendar', '?year=2027&month=13')), ctx());
  expect(res.status).toBe(422);
  expect((await json(res)).error.code).toBe('VALIDATION_ERROR');
});

test('wired section without an actor never reaches the backend', async () => {
  wiredEnv();
  const fetchMock = vi.fn();
  vi.stubGlobal('fetch', fetchMock);

  const res = await dashboardOverview(new Request(base('overview')), ctx());
  expect(res.status).toBe(401);
  expect((await json(res)).error.code).toBe('UNAUTHENTICATED');
  expect(fetchMock).not.toHaveBeenCalled();
});
