import fs from 'node:fs';
import path from 'node:path';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { GET as getPanditEarnings } from '@/app/api/pandit/earnings/route';
import { GET as getPartnerEarnings } from '@/app/api/partner/earnings/route';
import { __issuePartnerTestToken } from '@/server/auth';
import { describeAssignmentError } from '@/presentation/pandit/conflict-messages';
import { detectActiveEntity } from '@/components/EntitySwitcher';

describe('Partner Courtyard Mode, EntitySwitcher & Ledger Earnings (E-AP-05)', () => {
  const originalFetch = global.fetch;
  const originalEnv = { ...process.env };

  afterEach(() => {
    global.fetch = originalFetch;
    process.env = { ...originalEnv };
    vi.restoreAllMocks();
  });

  it('rejects unauthenticated requests to /api/partner/earnings with 401 and no-store cache control', async () => {
    const req = new Request('https://partner.navdhya.com/api/partner/earnings');
    const res = await getPartnerEarnings(req);
    expect(res.status).toBe(401);
    expect(res.headers.get('cache-control')).toBe('private, no-store');
  });

  it('fails loudly with 503 DEPENDENCY_UNAVAILABLE when Laravel backend is disabled (no fake zeros)', async () => {
    delete process.env.PARTNER_BACKEND;
    delete process.env.NAVDHYA_API_BASE;
    const token = await __issuePartnerTestToken('usr_pandit_01', ['partner', 'pandit']);

    const req = new Request('https://partner.navdhya.com/api/pandit/earnings', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const res = await getPanditEarnings(req);
    expect(res.status).toBe(503);
    expect(res.headers.get('cache-control')).toBe('private, no-store');

    const json = (await res.json()) as { error: { code: string } };
    expect(json.error.code).toBe('DEPENDENCY_UNAVAILABLE');
  });

  it('proxies subledger earnings and TDS withholding cleanly with private no-store headers', async () => {
    process.env.PARTNER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'https://api.navdhya.test';
    const token = await __issuePartnerTestToken('usr_pandit_01', ['partner', 'pandit']);

    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      headers: new Headers({ 'content-type': 'application/json' }),
      json: async () => ({
        data: {
          pandit_profile_id: 'pnd_01',
          source: 'subledger',
          ledger: {
            gross_amount_minor: 500000,
            commission_minor: 50000,
            tax_withheld_minor: 50000,
            net_payable_minor: 400000,
            paid_out_minor: 200000,
          },
        },
        meta: { request_id: 'req_e_1' },
      }),
    } as unknown as Response);

    const req = new Request('https://partner.navdhya.com/api/partner/earnings', {
      headers: { Authorization: `Bearer ${token}` },
    });
    const res = await getPartnerEarnings(req);
    expect(res.status).toBe(200);
    expect(res.headers.get('cache-control')).toBe('private, no-store');
    expect(res.headers.get('vary')).toBe('Cookie, Authorization');

    const json = (await res.json()) as {
      data: { source: string; ledger: { tax_withheld_minor: number; net_payable_minor: number } };
    };
    expect(json.data.source).toBe('subledger');
    expect(json.data.ledger.tax_withheld_minor).toBe(50000);
    expect(json.data.ledger.net_payable_minor).toBe(400000);
  });

  it('maps assignment conflict error codes to human-readable courtyard guidance', () => {
    expect(describeAssignmentError('PANDIT_ASSIGNMENT_BOOKING_CONFLICT')).toMatch(/travel buffer|overlap/i);
    expect(describeAssignmentError('PANDIT_BOOKING_WRONG_STATE')).toMatch(/reassigned|cancelled/i);
    expect(describeAssignmentError('DEPENDENCY_UNAVAILABLE')).toMatch(/offline|network/i);
  });

  it('detects active persona entity across Pandit, Temple, and Vendor routes', () => {
    expect(detectActiveEntity('/pandit')).toBe('pandit');
    expect(detectActiveEntity('/assignments')).toBe('pandit');
    expect(detectActiveEntity('/temples/tmp_1/dashboard')).toBe('temple');
    expect(detectActiveEntity('/vendors/vnd_1/orders')).toBe('vendor');
  });

  it('ships courtyard PWA manifest with Sacred Ember colors and read-only service worker excluding APIs & PII', () => {
    const manifestPath = path.resolve(process.cwd(), 'public/manifest.webmanifest');
    const swPath = path.resolve(process.cwd(), 'public/sw.js');

    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8')) as {
      background_color: string;
      theme_color: string;
      start_url: string;
    };
    expect(manifest.background_color).toBe('#FFF7ED');
    expect(manifest.theme_color).toBe('#2C2033');
    expect(manifest.start_url).toBe('/pandit');

    const sw = fs.readFileSync(swPath, 'utf8');
    expect(sw).toContain("req.method !== 'GET'");
    expect(sw).toContain("url.pathname.startsWith('/api/')");
    expect(sw).toContain("url.pathname.startsWith('/earnings')");
  });
});

