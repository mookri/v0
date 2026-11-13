import { describe, expect, it, vi } from 'vitest';
import { validateProfileUpdate } from '@/application/profile/customer-profile';
import { confirmBookingDraft } from '@/application/bookings/confirm-booking-draft';

describe('Customer profile & booking Sacred Sankalpa validation (E-AP-08)', () => {
  it('validates and normalizes canonical gotra and nakshatra on profile updates', () => {
    const valid = validateProfileUpdate({
      name: 'Arjun Sharma',
      gotra: 'bharadwaja',
      nakshatra: 'purva bhadrapada',
    });
    expect(valid.ok).toBe(true);
    if (valid.ok) {
      expect(valid.input.gotra).toBe('BHARADWAJA');
      expect(valid.input.nakshatra).toBe('PURVA_BHADRAPADA');
    }

    const invalid = validateProfileUpdate({
      name: 'Arjun Sharma',
      gotra: 'not_a_real_gotra',
      nakshatra: 'not_a_star',
    });
    expect(invalid.ok).toBe(false);
    if (!invalid.ok) {
      expect(Object.keys(invalid.fieldErrors)).toEqual(['gotra', 'nakshatra']);
    }
  });

  it('forwards validated service_venue and canonical sankalpa metadata in confirmBookingDraft', async () => {
    const getPuja = vi.fn().mockResolvedValue({ sku: 'PUJA-GANESH-01' });
    const createBooking = vi.fn().mockResolvedValue({ id: 'bk_01' });

    await confirmBookingDraft(
      { getPuja, createBooking },
      {
        pujaId: 'item_1',
        slotRef: '2026-11-20T06:00:00.000Z',
        idempotencyKey: 'ik_123',
        serviceVenue: 'temple',
        gotra: 'kashyapa',
        nakshatra: 'rohini',
      },
    );

    expect(createBooking).toHaveBeenCalledWith(
      {
        service_sku: 'PUJA-GANESH-01',
        scheduled_at: '2026-11-20T06:00:00.000Z',
        duration_minutes: 60,
        service_venue: 'temple',
        metadata: { gotra: 'KASHYAPA', nakshatra: 'ROHINI' },
      },
      'ik_123',
    );
  });

  it('rejects unsupported service_venue values before dispatching API requests', async () => {
    const getPuja = vi.fn().mockResolvedValue({ sku: 'PUJA-GANESH-01' });
    const createBooking = vi.fn();

    await expect(
      confirmBookingDraft(
        { getPuja, createBooking },
        {
          pujaId: 'item_1',
          slotRef: '2026-11-20T06:00:00.000Z',
          idempotencyKey: 'ik_123',
          serviceVenue: 'invalid_place',
        },
      ),
    ).rejects.toThrow('INVALID_SERVICE_VENUE');

    expect(createBooking).not.toHaveBeenCalled();
  });
});

