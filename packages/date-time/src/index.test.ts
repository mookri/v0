import { describe, expect, it } from 'vitest';
import {
  IST_TIMEZONE,
  formatIstDate,
  formatIstDateTime,
  formatIstTime,
  formatMuhuratWindow,
  toIstIsoDate,
} from './index';

describe('@navdhya/date-time Panchang & IST helpers (T-AP06-03)', () => {
  it('converts UTC midnight rollover cleanly into Asia/Kolkata calendar date', () => {
    // 2026-11-11T20:00:00Z is 2026-11-12 01:30 AM IST
    expect(IST_TIMEZONE).toBe('Asia/Kolkata');
    expect(toIstIsoDate('2026-11-11T20:00:00Z')).toBe('2026-11-12');
    expect(formatIstDate('2026-11-11T20:00:00Z')).toContain('Nov');
    expect(formatIstTime('2026-11-11T20:00:00Z')).toContain('IST');
    expect(formatIstDateTime('2026-11-11T03:30:00Z')).toContain('IST');
  });

  it('formats Muhurat window bounds and rejects inverted intervals', () => {
    const windowStr = formatMuhuratWindow('2026-11-12T01:00:00Z', '2026-11-12T02:30:00Z');
    expect(windowStr).toContain('IST');
    expect(() =>
      formatMuhuratWindow('2026-11-12T03:00:00Z', '2026-11-12T02:00:00Z'),
    ).toThrow(/precede start/i);
  });
});

