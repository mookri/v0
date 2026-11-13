import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { NextRequest } from 'next/server';
import { GET as getTax } from '@/app/api/ops/tax/route';
import { POST as approvePayout } from '@/app/api/ops/payouts/[payoutId]/approve/route';
import { GET as getStats } from '@/app/api/ops/stats/route';
import { sessionCookieName } from '@/server/session';

describe('dashboard ops BFF contracts (E-AP-04)', () => {
  const ORIGINAL_ENV = { ...process.env };

  beforeEach(() => {
    process.env.DASHBOARD_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'http://laravel.test';
    process.env.DASHBOARD_TENANT_ID = '01JTENANT0000000000000001';
  });

  afterEach(() => {
    process.env = { ...ORIGINAL_ENV };
    vi.restoreAllMocks();
  });

  it('rejects unauthenticated ops stats requests with 401', async () => {
    const req = new NextRequest('http://localhost/api/ops/stats');
    const res = await getStats(req);
    expect(res.status).toBe(401);
  });

  it('fails loudly with 503 when NAVDHYA_API_BASE is unset (no fake KPI fallback)', async () => {
    delete process.env.NAVDHYA_API_BASE;
    delete process.env.DASHBOARD_API_BASE;
    const req = new NextRequest('http://localhost/api/ops/stats', {
      headers: { cookie: `${sessionCookieName()}=valid-token` },
    });
    const res = await getStats(req);
    expect(res.status).toBe(503);
    const body = (await res.json()) as { error: string };
    expect(body.error).toBe('backend_unavailable');
  });

  it('rejects /api/ops/tax without an explicit >= 5 char compliance purpose', async () => {
    const req = new NextRequest('http://localhost/api/ops/tax?purpose=abc', {
      headers: { cookie: `${sessionCookieName()}=valid-token` },
    });
    const res = await getTax(req);
    expect(res.status).toBe(422);
    const body = (await res.json()) as { error: string };
    expect(body.error).toBe('tax_purpose_required');
  });

  it('requires Idempotency-Key on payout approval before proxying upstream', async () => {
    const req = new NextRequest('http://localhost/api/ops/payouts/pay_1/approve', {
      method: 'POST',
      headers: { cookie: `${sessionCookieName()}=valid-token` },
      body: JSON.stringify({ checklist_confirmed: true }),
    });
    const res = await approvePayout(req, { params: Promise.resolve({ payoutId: 'pay_1' }) });
    expect(res.status).toBe(400);
    const body = (await res.json()) as { error: string };
    expect(body.error).toBe('idempotency_key_required');
  });
});

