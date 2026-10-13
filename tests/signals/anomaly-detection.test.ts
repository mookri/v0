// B6 acceptance — anomaly detection (plan §B6 evaluation): an injected 10×
// spike on a diagnostic family ANNOTATES the affected window without
// paging; the same spike on payment_succeeded PAGES. The annotation carries
// the window and baseline numbers.

import { describe, expect, it } from 'vitest';
import {
  REVENUE_ADJACENT_FAMILIES,
  detectAnomaly,
} from '@navdhya/analytics/src/anomaly/robust-anomaly';

const BUCKET = {
  windowStartMs: Date.parse('2026-10-13T09:00:00.000Z'),
  windowEndMs: Date.parse('2026-10-13T10:00:00.000Z'),
};

/** Seven quiet hourly buckets — the rolling baseline. */
const QUIET_BASELINE = [9, 11, 10, 12, 8, 10, 11, 10, 9, 11, 10, 12, 9, 10];

describe('anomaly detection acceptance (B6)', () => {
  it('injected 10× spike on `search` → annotate the window, NO page', () => {
    const result = detectAnomaly({
      family: 'search',
      current: 110, // ~10× the ~10-count baseline
      history: QUIET_BASELINE,
      ...BUCKET,
    });

    expect(result.status).toBe('annotate'); // not 'page', not 'ok'
    expect(result.z).toBeGreaterThan(3.5);
    expect(result.window).toEqual({ fromMs: BUCKET.windowStartMs, toMs: BUCKET.windowEndMs });
    expect(result.note).toContain('diagnostic family: annotate only');
    expect(result.historyLength).toBe(QUIET_BASELINE.length);
  });

  it('the same injected spike on `payment_succeeded` → PAGE', () => {
    const result = detectAnomaly({
      family: 'payment_succeeded',
      current: 110,
      history: QUIET_BASELINE,
      ...BUCKET,
    });

    expect(result.status).toBe('page');
    expect(REVENUE_ADJACENT_FAMILIES).toContain('payment_succeeded');
    expect(result.revenueAdjacent).toBe(true);
    expect(result.note).toContain('revenue-adjacent: PAGE');
  });

  it('a quiet bucket on either family is ok — the baseline absorbs normal variance', () => {
    for (const family of ['search', 'payment_succeeded']) {
      const result = detectAnomaly({ family, current: 10, history: QUIET_BASELINE, ...BUCKET });
      expect(result.status, family).toBe('ok');
      expect(result.window).toBeNull();
    }
  });

  it('the annotation is self-explaining: median, MAD, threshold, and raw current are on the record', () => {
    const result = detectAnomaly({
      family: 'checkout_started',
      current: 100,
      history: QUIET_BASELINE,
      ...BUCKET,
    });
    expect(result.baselineMedian).toBeGreaterThan(0);
    expect(result.baselineMad).toBeGreaterThanOrEqual(0);
    expect(result.current).toBe(100);
    expect(result.zThreshold).toBe(3.5);
    expect(result.note).toContain('median');
  });
});
