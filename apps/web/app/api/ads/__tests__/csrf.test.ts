/**
 * @jest-environment node
 */
import { POST } from "@/app/api/ads/decision/route";
import { issueCsrfToken, CSRF_CONSTANTS } from "@/lib/security/csrf";

describe("ad decision CSRF protection", () => {
  const ORIGINAL_ENV = process.env;

  beforeEach(() => {
    process.env = { ...ORIGINAL_ENV, CSRF_SECRET: "test-secret-for-csrf" };
  });

  afterAll(() => {
    process.env = ORIGINAL_ENV;
  });

  function makeRequest(opts: {
    method?: string;
    body?: string;
    csrfCookie?: string;
    csrfHeader?: string;
  } = {}): Request {
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
    };
    if (opts.csrfCookie) {
      headers.cookie = `${CSRF_CONSTANTS.COOKIE}=${opts.csrfCookie}`;
    }
    if (opts.csrfHeader) {
      headers[CSRF_CONSTANTS.HEADER] = opts.csrfHeader;
    }
    return new Request("http://localhost/api/ads/decision", {
      method: opts.method ?? "POST",
      body: opts.body ?? JSON.stringify({
        requestId: "req_test",
        opportunities: [{ slotId: "slot_1" }],
      }),
      headers,
    });
  }

  it("rejects when no CSRF token is present", async () => {
    const res = await POST(makeRequest());
    expect(res.status).toBe(403);
    const body = await res.json();
    expect(body.error).toMatch(/CSRF/);
  });

  it("accepts when cookie + header match", async () => {
    const { token } = issueCsrfToken();
    const res = await POST(makeRequest({ csrfCookie: token, csrfHeader: token }));
    // 200 (empty decisions) or 500 (ad runtime not init in test) — both OK,
    // we just need to be past the CSRF check.
    expect(res.status).not.toBe(403);
  });

  it("rejects when cookie and header mismatch", async () => {
    const { token: a } = issueCsrfToken();
    const { token: b } = issueCsrfToken();
    const res = await POST(makeRequest({ csrfCookie: a, csrfHeader: b }));
    expect(res.status).toBe(403);
  });

  it("skips CSRF check when CSRF_SECRET is unset (dev mode)", async () => {
    // Use a fresh process.env (replacing, not mutating) so neither
    // CSRF_SECRET nor NODE_ENV=production is set. This is the path
    // exercised by `next dev` and by Vercel preview deploys without
    // a configured secret.
    const env = { ...ORIGINAL_ENV } as Record<string, string | undefined>;
    delete env.CSRF_SECRET;
    env.NODE_ENV = "development";
    process.env = env as unknown as NodeJS.ProcessEnv;
    const res = await POST(makeRequest());
    expect(res.status).not.toBe(403);
  });
});
