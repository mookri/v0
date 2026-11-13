import { describe, expect, it } from 'vitest';
import { bayesianAverage, confidenceTier, decayedAverage, wilsonLowerBound } from './quality';

describe('Trust honest quality aggregation', () => {
  it('pulls small samples toward the category prior rather than returning 5.0', () => {
    const score = bayesianAverage([5], 'default', 10);
    expect(score).toBe(4.27);
  });

  it('hides numeric headline below N=5', () => {
    expect(confidenceTier(0)).toEqual({ tier: 'NO_REVIEWS', label: 'No reviews yet', showNumericHeadline: false });
    expect(confidenceTier(4)).toEqual({ tier: 'NEW_PROVIDER', label: 'New provider', showNumericHeadline: false });
    expect(confidenceTier(5)).toEqual({ tier: 'EMERGING_CONFIDENCE', label: 'Emerging confidence', showNumericHeadline: true });
    expect(confidenceTier(20)).toEqual({ tier: 'ESTABLISHED_SAMPLE', label: 'Established sample', showNumericHeadline: true });
  });

  it('penalizes tiny denominators via Wilson 95% lower bound', () => {
    const tiny = wilsonLowerBound(1, 1);
    const strong = wilsonLowerBound(19, 20);
    expect(tiny).not.toBeNull();
    expect(strong).not.toBeNull();
    expect(tiny!).toBeLessThan(strong!);
  });

  it('weights recent ratings higher than older ratings via exponential decay', () => {
    const decayed = decayedAverage([
      { rating: 5, ageDays: 1 },
      { rating: 1, ageDays: 180 },
    ]);
    expect(decayed).not.toBeNull();
    expect(decayed!).toBeGreaterThan(3.5);
  });
});

