// SRM check units (plan B8): chi-square math sanity, skewed fixtures
// flagged automatically, zero-weight/unregistered variants flagged,
// insufficient volume never flagged.

import { describe, expect, it } from 'vitest';
import {
  SRM_MIN_EXPOSED,
  chiSquarePValue,
  checkSrm,
} from '@navdhya/analytics/src/experiments/srm';

function exposures(variant: string, count: number): Array<{ variant: string }> {
  return Array.from({ length: count }, () => ({ variant }));
}

function balancedSplit(total: number, skewPct: number): Array<{ variant: string }> {
  const control = Math.round((total * skewPct) / 100);
  return [...exposures('control', control), ...exposures('treatment', total - control)];
}

describe('SRM check (B8)', () => {
  it('chi-square p-value matches known quantiles', () => {
    // Classic critical values: P(X² >= 3.841, df=1) ≈ 0.05;
    // P(X² >= 6.635, df=1) ≈ 0.01; larger chi² ⇒ smaller p.
    expect(chiSquarePValue(3.841, 1)).toBeCloseTo(0.05, 2);
    expect(chiSquarePValue(6.635, 1)).toBeCloseTo(0.01, 2);
    expect(chiSquarePValue(0, 1)).toBeCloseTo(1, 6);
    expect(chiSquarePValue(20, 1)).toBeLessThan(1e-5);
  });

  it('a balanced split passes', () => {
    const result = checkSrm(balancedSplit(1000, 50), { control: 1, treatment: 1 });
    expect(result.valid).toBe(true);
    expect(result.totalExposed).toBe(1000);
    expect(result.pValue).toBeGreaterThan(0.5);
  });

  it('SKEWED ALLOCATION (rigged fixture) is flagged invalid automatically', () => {
    const result = checkSrm(balancedSplit(1000, 70), { control: 1, treatment: 1 });
    expect(result.valid).toBe(false);
    expect(result.pValue).toBeLessThan(0.001);
    expect(result.reason).toContain('flagged invalid');
  });

  it('exposures in zero-weight or unregistered variants are flagged', () => {
    const zeroWeight = checkSrm(
      [...exposures('control', 500), ...exposures('treatment', 50)],
      { control: 1, treatment: 0 },
    );
    expect(zeroWeight.valid).toBe(false);
    expect(zeroWeight.reason).toContain('zero-weight');

    const unregistered = checkSrm(
      [...exposures('control', 500), ...exposures('ghost', 50)],
      { control: 1, treatment: 1 },
    );
    expect(unregistered.valid).toBe(false);
    expect(unregistered.reason).toContain('unregistered');
  });

  it('low exposure volume is recorded, never flagged', () => {
    const result = checkSrm(balancedSplit(SRM_MIN_EXPOSED - 1, 65), { control: 1, treatment: 1 });
    expect(result.valid).toBe(true);
    expect(result.reason).toContain('no power');
    expect(checkSrm([], { control: 1, treatment: 1 }).valid).toBe(true);
  });
});
