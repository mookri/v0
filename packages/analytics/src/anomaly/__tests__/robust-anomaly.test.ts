// Anomaly detection unit tests (plan B6 evaluation): robust z-score over a
// median/MAD baseline, annotate-vs-page policy, affected-window annotation.

import { describe, expect, it } from 'vitest';
import {
  REVENUE_ADJACENT_FAMILIES,
  detectAnomaly,
} from '@navdhya/analytics/src/anomaly/robust-anomaly';

const WINDOW = { windowStartMs: Date.parse('2026-10-13T10:00:00.000Z'), windowEndMs: Date.parse('2026-10-13T11:00:00.000Z') };

function history(values: number[]): number[] {
  return values;
}

describe('anomaly detection (B6)', () => {
  it('10× spike on a diagnostic family → annotate, never page', () => {
    const result = detectAnomaly({
      family: 'search',
      current: 100,
      history: history([8, 9, 10, 11, 12, 9, 10, 11, 10, 9]),
      ...WINDOW,
    });
    expect(result.status).toBe('annotate');
    expect(result.z).toBeGreaterThan(3.5);
    expect(result.window).toEqual({ fromMs: WINDOW.windowStartMs, toMs: WINDOW.windowEndMs });
    expect(result.note).toContain('annotate only');
  });

  it('same spike on payment_succeeded → PAGE (revenue-adjacent)', () => {
    const result = detectAnomaly({
      family: 'payment_succeeded',
      current: 100,
      history: history([8, 9, 10, 11, 12, 9, 10, 11, 10, 9]),
      ...WINDOW,
    });
    expect(result.status).toBe('page');
    expect(REVENUE_ADJACENT_FAMILIES).toContain('payment_succeeded');
    expect(result.note).toContain('PAGE');
  });

  it('normal variation stays ok with no window annotation', () => {
    const result = detectAnomaly({
      family: 'page_view',
      current: 105,
      history: history([98, 101, 103, 99, 104, 102, 100, 106, 97, 103]),
      ...WINDOW,
    });
    expect(result.status).toBe('ok');
    expect(result.window).toBeNull();
    expect(Math.abs(result.z)).toBeLessThan(3.5);
  });

  it('a collapse (zero volume) is also anomalous — flat baseline makes it definitive', () => {
    const result = detectAnomaly({
      family: 'checkout_started',
      current: 0,
      history: history([10, 10, 10, 10, 10, 10, 10]),
      ...WINDOW,
    });
    expect(result.status).toBe('annotate');
    expect(result.z).toBe(Number.NEGATIVE_INFINITY);
    expect(result.baselineMad).toBe(0);
  });

  it('insufficient history makes no call — ok with an explicit note', () => {
    const result = detectAnomaly({
      family: 'search',
      current: 100,
      history: history([9, 10, 11]),
      ...WINDOW,
    });
    expect(result.status).toBe('ok');
    expect(result.note).toContain('baseline not trusted');
    expect(result.z).toBe(0);
  });

  it('all revenue-adjacent families page; the rest annotate', () => {
    for (const family of REVENUE_ADJACENT_FAMILIES) {
      const result = detectAnomaly({ family, current: 100, history: history([9, 10, 11, 10, 9, 10, 11]), ...WINDOW });
      expect(result.status, family).toBe('page');
    }
    const diagnostic = detectAnomaly({ family: 'view_item', current: 100, history: history([9, 10, 11, 10, 9, 10, 11]), ...WINDOW });
    expect(diagnostic.status).toBe('annotate');
  });
});
