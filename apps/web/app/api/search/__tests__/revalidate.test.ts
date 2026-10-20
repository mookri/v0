/**
 * @jest-environment node
 *
 * Regression pin for the fail-open secret check on the platform-facing
 * cache-flush endpoint: `if (secret)` used to mean a deployment that
 * forgot SEARCH_REVALIDATE_SECRET exposed an UNAUTHENTICATED cache flush
 * to the internet (cache-thrash DoS), and `!==` on the header leaked a
 * valid prefix through response timing.
 */
import { POST } from '@/app/api/search/revalidate/route';

const SECRET = 'reval-secret-for-tests';

function makeRequest(secret?: string): Request {
  const headers: Record<string, string> = {};
  if (secret !== undefined) headers['x-search-revalidate-secret'] = secret;
  return new Request('http://localhost/api/search/revalidate', { method: 'POST', headers });
}

function envWithoutSecret(): NodeJS.ProcessEnv {
  const env = { ...process.env } as Record<string, string | undefined>;
  delete env['SEARCH_REVALIDATE_SECRET'];
  return env as unknown as NodeJS.ProcessEnv;
}

describe('POST /api/search/revalidate', () => {
  const ORIGINAL_ENV = process.env;

  beforeEach(() => {
    process.env = { ...ORIGINAL_ENV, SEARCH_REVALIDATE_SECRET: SECRET };
  });

  afterAll(() => {
    process.env = ORIGINAL_ENV;
  });

  it('fails closed with 503 when the secret is not configured', async () => {
    process.env = envWithoutSecret();

    const res = await POST(makeRequest());
    expect(res.status).toBe(503);
    expect((await res.json()).error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('fails closed even when a secret is supplied to an unconfigured endpoint', async () => {
    process.env = envWithoutSecret();

    const res = await POST(makeRequest('anything'));
    expect(res.status).toBe(503);
  });

  it('rejects a wrong secret', async () => {
    const res = await POST(makeRequest('not-the-secret'));
    expect(res.status).toBe(403);
    expect((await res.json()).error.code).toBe('FORBIDDEN');
  });

  it('rejects a missing secret header', async () => {
    const res = await POST(makeRequest());
    expect(res.status).toBe(403);
    expect((await res.json()).error.code).toBe('FORBIDDEN');
  });

  it('accepts the configured secret', async () => {
    const res = await POST(makeRequest(SECRET));
    expect(res.status).toBe(200);
    expect(await res.json()).toMatchObject({ data: { invalidated: true } });
  });

  it('treats an empty secret as unconfigured rather than as a match', async () => {
    process.env = { ...ORIGINAL_ENV, SEARCH_REVALIDATE_SECRET: '' };

    const res = await POST(makeRequest(''));
    expect(res.status).toBe(503);
  });
});
