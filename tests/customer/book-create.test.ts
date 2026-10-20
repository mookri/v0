import { test, expect, vi, afterEach } from 'vitest';
import { readFileSync } from 'node:fs';
import { mintActorToken } from '@navdhya/identity/src/application/token-actor';
import {
  encodeBookingIntent,
  type BookingIntent,
} from '@navdhya/booking/src/contracts/BookingIntent';
import { pujaBookStartUrl } from '@apps/customer/application/bookings/puja-book-start-url';
import { resolveBookStart } from '@apps/customer/application/bookings/resolve-book-start';
import { confirmBookingDraft } from '@apps/customer/application/bookings/confirm-booking-draft';
import { POST as createBooking } from '@apps/customer/app/api/bookings/route';
import { customerTokenCookieName } from '@apps/customer/server/backend-session';

// Book-create vertical (S15): the detail page emits a decodable intent
// (raw JSON never decoded), the codec is browser-safe (no Buffer), the
// review step posts the catalog SKU — never the item id — and the BFF
// create route forwards body + Idempotency-Key, mapping backend codes.

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
});

const NOW = '2026-10-01T00:00:00.000Z';

test('puja detail builds a decodable, expiring book-start intent', () => {
  const url = pujaBookStartUrl('cat_01H', 'idem_1', NOW);
  expect(url.startsWith('/book/start?intent=')).toBe(true);

  const param = decodeURIComponent(url.slice('/book/start?intent='.length));
  const res = resolveBookStart(param, NOW);
  expect(res.status).toBe('incomplete');
  if (res.status === 'incomplete') {
    // A puja without a slot: the customer picks the time (never invented).
    expect(res.needs).toEqual(['slot']);
    expect(res.intent.pujaId).toBe('cat_01H');
    expect(res.intent.source).toBe('puja-detail');
    expect(res.intent.idempotencyKey).toBe('idem_1');
  }

  // Past the 60-minute TTL the link stops working.
  const stale = resolveBookStart(param, '2026-10-01T01:01:00.000Z');
  expect(stale.status).toBe('invalid');
  if (stale.status === 'invalid') expect(stale.code).toBe('INTENT_EXPIRED');
});

test('intent codec does not depend on Buffer (it runs in the browser)', () => {
  const source = readFileSync('packages/booking/src/contracts/BookingIntent.ts', 'utf8');
  expect(source).not.toMatch(/\bBuffer\b/);

  const intent: BookingIntent = {
    v: 1,
    pujaId: 'cat_01H',
    source: 'puja-detail',
    idempotencyKey: 'idem_1',
    createdAtIso: NOW,
    expiresAtIso: '2026-10-01T01:00:00.000Z',
  };

  // Round-trip with Buffer removed entirely — what a browser sees.
  vi.stubGlobal('Buffer', undefined);
  try {
    const raw = encodeBookingIntent(intent);
    expect(raw).not.toMatch(/[+/=]/);
    expect(decodeURIComponent(encodeURIComponent(raw))).toBe(raw);
    expect(resolveBookStart(raw, NOW).status).toBe('incomplete');
  } finally {
    vi.unstubAllGlobals();
  }
});

test('confirmBookingDraft posts the catalog SKU, never the item id', async () => {
  const calls: Array<{ payload: Record<string, unknown>; key: string }> = [];
  const deps = {
    getPuja: vi.fn(async () => ({ id: 'cat_01H', sku: 'PUJA-LAKSHMI-01' })),
    createBooking: vi.fn(async (payload: Record<string, unknown>, key: string) => {
      calls.push({ payload, key });
      return { id: 'b9' };
    }),
  };

  const out = await confirmBookingDraft(deps, {
    pujaId: 'cat_01H',
    slotRef: '2030-05-01T10:00:00.000Z',
    idempotencyKey: 'idem_7',
  });

  expect(out).toEqual({ id: 'b9' });
  expect(deps.getPuja).toHaveBeenCalledWith('cat_01H');
  expect(calls).toHaveLength(1);
  expect(calls[0]!.payload).toEqual({
    service_sku: 'PUJA-LAKSHMI-01',
    scheduled_at: '2030-05-01T10:00:00.000Z',
    duration_minutes: 60,
  });
  expect(calls[0]!.key).toBe('idem_7');
});

test('confirmBookingDraft fails closed when the item carries no SKU', async () => {
  const createBooking = vi.fn();
  await expect(
    confirmBookingDraft(
      { getPuja: async () => ({ sku: undefined }), createBooking },
      { pujaId: 'cat_01H', slotRef: '2030-05-01T10:00:00.000Z', idempotencyKey: 'idem_7' },
    ),
  ).rejects.toThrow('PUJA_SKU_UNAVAILABLE');
  expect(createBooking).not.toHaveBeenCalled();
});

// --- BFF POST /api/bookings -------------------------------------------------

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

const json = async (res: Response) => (await res.json()) as Record<string, any>;

const createRequest = (headers: Record<string, string>) =>
  new Request('https://app.navdhya.com/api/bookings', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...headers },
    body: JSON.stringify({
      service_sku: 'PUJA-LAKSHMI-01',
      scheduled_at: '2030-05-01T10:00:00.000Z',
      duration_minutes: 60,
    }),
  });

test('mock create stays closed without an actor', async () => {
  const res = await createBooking(createRequest({}));
  // Mock mode has no booking store; the handler fails closed instead of
  // minting a reservation. The old 401 came from the unreachable actor
  // gate, not from a real authentication decision.
  expect(res.status).toBe(422);
  expect((await json(res)).error.code).toBe('VALIDATION_ERROR');
});

test('wired create forwards body + Idempotency-Key and unwraps the 201', async () => {
  wiredEnv();
  const token = backendToken();
  const calls: { url: string; method?: string; headers: Record<string, string>; body: unknown }[] = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      calls.push({
        url: String(url),
        method: init?.method,
        headers: (init?.headers ?? {}) as Record<string, string>,
        body: JSON.parse(String(init?.body)),
      });
      return new Response(
        JSON.stringify({
          data: {
            id: 'b1',
            status: 'DRAFT',
            service_sku: 'PUJA-LAKSHMI-01',
            scheduled_at: '2030-05-01 10:00:00',
            version: 1,
            actions: [],
          },
          meta: { request_id: 'kernel-1' },
        }),
        { status: 201 },
      );
    }),
  );

  const res = await createBooking(
    createRequest({
      cookie: cookieHeader(token),
      'Idempotency-Key': 'idem_7',
    }),
  );

  expect(res.status).toBe(201);
  expect((await json(res)).data).toMatchObject({ id: 'b1', status: 'DRAFT' });

  expect(calls).toHaveLength(1);
  expect(calls[0]!.url).toBe('https://api.example.test/api/v1/booking/reservations');
  expect(calls[0]!.method).toBe('POST');
  expect(calls[0]!.headers['Idempotency-Key']).toBe('idem_7');
  expect(calls[0]!.headers['X-Tenant-Id']).toBe('tenant_1');
  expect(calls[0]!.headers['Authorization']).toBe(`Bearer ${token}`);
  expect(calls[0]!.body).toEqual({
    service_sku: 'PUJA-LAKSHMI-01',
    scheduled_at: '2030-05-01T10:00:00.000Z',
    duration_minutes: 60,
  });
});

test('wired create maps backend 422 to VALIDATION_ERROR', async () => {
  wiredEnv();
  vi.stubGlobal(
    'fetch',
    vi.fn(
      async () =>
        new Response(
          JSON.stringify({
            error: {
              code: 'validation_failed',
              message: 'Invalid or inactive service SKU.',
              requestId: 'r',
            },
          }),
          { status: 422 },
        ),
    ),
  );

  const res = await createBooking(
    createRequest({
      cookie: cookieHeader(backendToken()),
      'Idempotency-Key': 'idem_7',
    }),
  );
  expect(res.status).toBe(422);
  expect((await json(res)).error.code).toBe('VALIDATION_ERROR');
});
