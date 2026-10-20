import { test, expect, vi, afterEach } from 'vitest';
import { mintActorToken, INSECURE_DEV_SECRET } from '@navdhya/identity/src/application/token-actor';
import { GET as showPuja } from '@apps/partner/app/api/temples/[templeId]/pujas/[pujaId]/route';

// Temple puja show BFF behavior (P11g): mock default is 401 without an
// actor / 404 with one (no fixtures); the laravel branch (first partner
// wired test) forwards the signed bearer + tenant and maps the kernel's
// TEMPLE_PUJA_NOT_FOUND to the stable NOT_FOUND envelope.

afterEach(() => {
  vi.unstubAllEnvs();
  vi.unstubAllGlobals();
});

function wiredEnv() {
  vi.stubEnv('PARTNER_BACKEND', 'laravel');
  vi.stubEnv('NAVDHYA_API_BASE', 'https://api.example.test');
  vi.stubEnv('PARTNER_TENANT_ID', 'tenant_1');
}

// Minted once so the forwarded Authorization header can be asserted
// against a stable value.
const TOKEN = mintActorToken(
  INSECURE_DEV_SECRET,
  { userId: 'partner_1', sessionId: 'sess_1', roles: ['partner'] },
  new Date().toISOString(),
  3600,
);

const bearer = () => `Bearer ${TOKEN}`;

const authed = (url: string) =>
  new Request(url, { headers: { authorization: bearer() } });

const params = (templeId: string, pujaId: string) => ({
  params: Promise.resolve({ templeId, pujaId }),
});

const json = async (res: Response) => (await res.json()) as Record<string, any>;

test('mock puja show is unauthenticated without an actor', async () => {
  const res = await showPuja(
    new Request('https://partner.navdhya.com/api/temples/t1/pujas/as1'),
    params('t1', 'as1'),
  );
  expect(res.status).toBe(401);
  expect((await json(res)).error.code).toBe('UNAUTHENTICATED');
});

test('mock puja show reads as not-found for an authenticated actor', async () => {
  const res = await showPuja(authed('https://partner.navdhya.com/api/temples/t1/pujas/as1'), params('t1', 'as1'));
  expect(res.status).toBe(404);
  const body = await json(res);
  expect(body.error.code).toBe('NOT_FOUND');
  expect(body.error.message).toBe('Puja assignment not found.');
});

test('wired puja show forwards bearer + tenant and unwraps the assignment', async () => {
  wiredEnv();
  const calls: { url: string; authorization?: string; tenant?: string }[] = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const headers = (init?.headers ?? {}) as Record<string, string>;
      calls.push({ url: String(url), authorization: headers['Authorization'], tenant: headers['X-Tenant-Id'] });
      return new Response(
        JSON.stringify({
          data: {
            id: 'as1',
            temple_id: 't1',
            puja_id: 'p1',
            price_override_minor: 210000,
            capacity: 40,
            schedule_json: null,
            status: 'ACTIVE',
            created_at: '2030-01-01T00:00:00.000Z',
          },
          meta: { request_id: 'kernel-req-1' },
        }),
        { status: 200 },
      );
    }),
  );

  const res = await showPuja(authed('https://partner.navdhya.com/api/temples/t1/pujas/as1'), params('t1', 'as1'));
  expect(res.status).toBe(200);
  const body = await json(res);
  expect(body.data).toMatchObject({ id: 'as1', temple_id: 't1', puja_id: 'p1', status: 'ACTIVE' });

  expect(calls).toHaveLength(1);
  expect(calls[0]!.url).toBe('https://api.example.test/api/v1/temple/partner/temples/t1/pujas/as1');
  expect(calls[0]!.authorization).toBe(bearer());
  expect(calls[0]!.tenant).toBe('tenant_1');
});

test('wired puja show maps TEMPLE_PUJA_NOT_FOUND to NOT_FOUND', async () => {
  wiredEnv();
  vi.stubGlobal(
    'fetch',
    vi.fn(
      async () =>
        new Response(
          JSON.stringify({
            error: { code: 'TEMPLE_PUJA_NOT_FOUND', message: 'Puja assignment not found.', requestId: 'r' },
          }),
          { status: 404 },
        ),
    ),
  );

  const res = await showPuja(
    authed('https://partner.navdhya.com/api/temples/t1/pujas/as_missing'),
    params('t1', 'as_missing'),
  );
  expect(res.status).toBe(404);
  expect((await json(res)).error.code).toBe('NOT_FOUND');
});

test('wired puja show without an actor never reaches the backend', async () => {
  wiredEnv();
  const fetchMock = vi.fn();
  vi.stubGlobal('fetch', fetchMock);

  const res = await showPuja(
    new Request('https://partner.navdhya.com/api/temples/t1/pujas/as1'),
    params('t1', 'as1'),
  );
  expect(res.status).toBe(401);
  expect(fetchMock).not.toHaveBeenCalled();
});
