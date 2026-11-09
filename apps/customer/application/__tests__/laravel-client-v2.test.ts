import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { laravelGet, laravelPost, DEFAULT_BACKEND_CODE_MAP, mapBackendCode } from '@/adapters/backend/laravel-client';
import { CustomerApiError } from '@/adapters/backend/customer-api-client';
import { toBookingDetails, type LaravelReservation } from '@/application/bookings/laravel-booking-mappers';

describe('Laravel Client V2 Versioning & Headers (WP4)', () => {
  const originalFetch = global.fetch;

  beforeEach(() => {
    process.env.CUSTOMER_BACKEND = 'laravel';
    process.env.NAVDHYA_API_BASE = 'https://api.navdhya.test';
  });

  afterEach(() => {
    global.fetch = originalFetch;
  });

  it('routes v2 endpoints to /api/v2 and captures ETag', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      headers: new Headers({
        etag: 'W/"v5"',
        'content-type': 'application/json',
      }),
      json: async () => ({
        data: { id: 'bkg_123', version: 5 },
        meta: { request_id: 'req_test_1' },
      }),
    } as unknown as Response);

    const res = await laravelGet<{ id: string; version: number }>(
      'test-token',
      '/booking/reservations/bkg_123',
      {},
      {},
      {},
      'v2'
    );

    expect(global.fetch).toHaveBeenCalledWith(
      'https://api.navdhya.test/api/v2/booking/reservations/bkg_123',
      expect.objectContaining({
        method: 'GET',
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          Authorization: 'Bearer test-token',
        }),
      })
    );

    expect(res.data).toEqual({ id: 'bkg_123', version: 5 });
    expect(res.etag).toBe('W/"v5"');
  });

  it('forwards If-Match header and parses problem details error', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 409,
      headers: new Headers({
        'content-type': 'application/problem+json',
      }),
      json: async () => ({
        type: 'https://api.navdhya.com/problems/quote_stale',
        title: 'Quote Stale',
        status: 409,
        detail: 'Commercial pricing or tax rate drifted.',
        code: 'quote_stale',
        request_id: 'req_stale',
      }),
    } as unknown as Response);

    await expect(
      laravelPost(
        'test-token',
        '/booking/reservations/bkg_123/checkout',
        {},
        {},
        { 'If-Match': 'W/"v2"' },
        'v2'
      )
    ).rejects.toThrow('Commercial pricing or tax rate drifted.');
  });

  it('maps backend codes accurately', () => {
    expect(mapBackendCode('quote_stale')).toBe('CONFLICT');
    expect(mapBackendCode('precondition_required')).toBe('STALE_RESOURCE');
    expect(mapBackendCode('resource_unavailable')).toBe('CONFLICT');
  });
});

describe('toBookingDetails V2 enrichment', () => {
  it('maps commercial details, shipment tracking, and countdown hold', () => {
    const raw: LaravelReservation = {
      id: 'bkg_test_v2',
      status: 'PENDING_PAYMENT',
      service_sku: 'SRV-SATYANARAYAN',
      scheduled_at: '2026-12-01T10:00:00Z',
      version: 2,
      amount_minor: 590000,
      currency: 'INR',
      checkout_expires_at: '2026-12-01T09:15:00Z',
      accepted_quote_hash: 'hash_abc_123',
      actions: ['cancel'],
      shipment: {
        id: 'shp_01',
        carrier_name: 'Bluedart Express',
        tracking_code: 'TRK-98765',
        status: 'DISPATCHED',
      },
    };

    const details = toBookingDetails(raw);
    expect(details.id).toBe('bkg_test_v2');
    expect(details.version).toBe(2);
    expect(details.status).toBe('PENDING_PAYMENT');
    expect(details.amountMinor).toBe(590000);
    expect(details.currency).toBe('INR');
    expect(details.checkoutExpiresAt).toBe('2026-12-01T09:15:00Z');
    expect(details.acceptedQuoteHash).toBe('hash_abc_123');
    expect(details.shipment?.trackingCode).toBe('TRK-98765');
    expect(details.actions).toContain('cancel');
  });
});
