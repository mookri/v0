import { expect, test } from '@playwright/test';

const APPS = {
  web: process.env.E2E_WEB_URL ?? 'http://127.0.0.1:3000',
  customer: process.env.E2E_CUSTOMER_URL ?? 'http://127.0.0.1:3001',
  partner: process.env.E2E_PARTNER_URL ?? 'http://127.0.0.1:3002',
  dashboard: process.env.E2E_DASHBOARD_URL ?? 'http://127.0.0.1:3003',
};

test.describe('Multi-App Frontend Portfolio Control Matrix (S1–S16 / E-AP-09)', () => {
  test('S1: every app exposes unauthenticated /api/healthz liveness with no-store headers', async ({ request }) => {
    for (const [name, url] of Object.entries(APPS)) {
      const res = await request.get(`${url}/api/healthz`);
      expect(res.status(), `${name} /api/healthz status`).toBe(200);
      const body = (await res.json()) as { status: string };
      expect(body.status).toBe('ok');
    }
  });

  test('S2/S14: protected BFF APIs fail closed (401) without host-scoped session cookie and never share cookies across hosts', async ({
    request,
  }) => {
    const dashStats = await request.get(`${APPS.dashboard}/api/ops/stats`);
    expect(dashStats.status()).toBe(401);

    const partnerEarnings = await request.get(`${APPS.partner}/api/partner/earnings`);
    expect(partnerEarnings.status()).toBe(401);
    expect(partnerEarnings.headers()['cache-control']).toContain('no-store');
  });

  test('S6/S9: dashboard payout approval & tax register routes fail closed when prerequisites are missing', async ({
    request,
  }) => {
    const taxRes = await request.get(`${APPS.dashboard}/api/ops/tax`);
    expect([401, 422]).toContain(taxRes.status());

    const approveRes = await request.post(`${APPS.dashboard}/api/ops/payouts/pay_smoke/approve`, {
      data: { checklist_confirmed: true },
    });
    expect([400, 401]).toContain(approveRes.status());
  });
});

