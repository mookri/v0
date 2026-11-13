import { describe, expect, it } from 'vitest';
import {
  CANONICAL_GOTRAS,
  CANONICAL_NAKSHATRAS,
  SERVICE_VENUES,
  isValidIfsc,
  isValidIndianPhone,
  isValidPan,
  isValidPinCode,
  normalizeCanonicalGotra,
  normalizeCanonicalNakshatra,
  normalizeIndianPhone,
  validateRitualParticipantInput,
} from './index';

describe('@navdhya/validation canonical schemas (T-AP06-04)', () => {
  it('aligns canonical gotras and 27 nakshatras with Laravel Pandit taxonomy', () => {
    expect(CANONICAL_GOTRAS).toContain('KASHYAPA');
    expect(CANONICAL_GOTRAS).toContain('BHARADWAJA');
    expect(CANONICAL_GOTRAS).toHaveLength(20);
    expect(CANONICAL_NAKSHATRAS).toHaveLength(27);
    expect(SERVICE_VENUES).toEqual(['home', 'temple', 'online']);

    expect(normalizeCanonicalGotra('kashyapa')).toBe('KASHYAPA');
    expect(normalizeCanonicalGotra('nonexistent')).toBeNull();
    expect(normalizeCanonicalNakshatra('purva bhadrapada')).toBe('PURVA_BHADRAPADA');
  });

  it('validates Indian phone, IFSC, PIN code, and PAN cleanly', () => {
    expect(normalizeIndianPhone('9876543210')).toBe('+919876543210');
    expect(isValidIndianPhone('+91 98765-43210')).toBe(true);
    expect(isValidIndianPhone('1234567890')).toBe(false);

    expect(isValidIfsc('HDFC0001234')).toBe(true);
    expect(isValidIfsc('INVALID123')).toBe(false);
    expect(isValidPinCode('560001')).toBe(true);
    expect(isValidPinCode('060001')).toBe(false);
    expect(isValidPan('ABCDE1234F')).toBe(true);
    expect(isValidPan('12345ABCDE')).toBe(false);
  });

  it('validates service_venue + optional canonical gotra/nakshatra inputs', () => {
    expect(
      validateRitualParticipantInput({
        service_venue: 'home',
        gotra: 'Bharadwaja',
        nakshatra: 'Rohini',
      }),
    ).toEqual([]);

    expect(
      validateRitualParticipantInput({
        service_venue: 'beach',
        gotra: 'unknown_lineage',
        nakshatra: 'bogus_star',
      }),
    ).toEqual(['service_venue', 'gotra', 'nakshatra']);
  });
});

