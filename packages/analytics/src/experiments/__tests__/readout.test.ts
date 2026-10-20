// Readout units (plan B8 evaluation criterion 1): reproducible, window-
// honest, guardrail-aware, SRM-propagating.

import { describe, expect, it } from 'vitest';
import type { ExperimentDefinition } from '@navdhya/analytics/src/experiments/contract';
import type { ExperimentExposure } from '@navdhya/analytics/src/experiments/ledger';
import {
  READOUT_BASIS,
  computeReadout,
  twoProportionPValue,
  type OutcomeEvent,
} from '@navdhya/analytics/src/experiments/readout';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');
const DAY = 24 * 60 * 60 * 1000;

const DEFINITION: ExperimentDefinition = {
  id: 'checkout_cta',
  salt: 's1',
  allocationVersion: 1,
  variants: ['control', 'treatment'],
  weights: { control: 1, treatment: 1 },
  primaryOutcomes: ['booking_confirmed'],
  eligiblePopulation: 'checkout visitors',
  observationWindowDays: 14,
  guardrails: [{ event: 'payment_failed', maxRatePerExposed: 0.5 }],
  status: 'running',
};

function exposure(subject: string, variant: string, atMs = NOW): ExperimentExposure {
  return { experimentId: 'checkout_cta', subjectIdHash: subject, variant, exposedAtMs: atMs };
}

describe('experiment readout (B8)', () => {
  it('is reproducible: identical inputs produce identical output', () => {
    const exposures = [
      ...Array.from({ length: 40 }, (_, i) => exposure(`s${i}`, 'control')),
      ...Array.from({ length: 40 }, (_, i) => exposure(`t${i}`, 'treatment')),
    ];
    const outcomes: OutcomeEvent[] = [
      ...Array.from({ length: 10 }, (_, i) => ({ subjectIdHash: `s${i}`, event: 'booking_confirmed', atMs: NOW + DAY })),
      ...Array.from({ length: 18 }, (_, i) => ({ subjectIdHash: `t${i}`, event: 'booking_confirmed', atMs: NOW + DAY })),
      { subjectIdHash: 't0', event: 'payment_failed', atMs: NOW + DAY },
    ];
    const first = computeReadout({ definition: DEFINITION, exposures, outcomes });
    const second = computeReadout({ definition: DEFINITION, exposures, outcomes });
    expect(second).toEqual(first);
    expect(first.basis).toBe(READOUT_BASIS);
    expect(first.valid).toBe(true);
    expect(first.variants[0]!.outcomes.booking_confirmed!.count).toBe(10);
    expect(first.variants[1]!.outcomes.booking_confirmed!.count).toBe(18);
    expect(first.primaryComparison!.relativeLift).toBeCloseTo(0.8, 6);
    expect(first.primaryComparison!.pValue).not.toBeNull();
  });

  it('counts only outcomes inside the pre-registered observation window', () => {
    const exposures = [exposure('s1', 'control'), exposure('t1', 'treatment')];
    const outcomes: OutcomeEvent[] = [
      { subjectIdHash: 's1', event: 'booking_confirmed', atMs: NOW + 13 * DAY }, // in window
      { subjectIdHash: 't1', event: 'booking_confirmed', atMs: NOW + 40 * DAY }, // out of window
    ];
    const readout = computeReadout({ definition: DEFINITION, exposures, outcomes });
    expect(readout.variants[0]!.outcomes.booking_confirmed!.count).toBe(1);
    expect(readout.variants[1]!.outcomes.booking_confirmed!.count).toBe(0);
  });

  it('guardrail breaches are flagged; within-threshold is not', () => {
    const exposures = Array.from({ length: 10 }, (_, i) => exposure(`t${i}`, 'treatment'));
    const outcomes: OutcomeEvent[] = Array.from({ length: 6 }, (_, i) => ({
      subjectIdHash: `t${i}`,
      event: 'payment_failed',
      atMs: NOW + DAY,
    }));
    const readout = computeReadout({ definition: DEFINITION, exposures, outcomes });
    expect(readout.guardrailBreaches).toContain('treatment:payment_failed');
    expect(readout.variants[1]!.guardrails.payment_failed!.breached).toBe(true);
  });

  it('an SRM-invalid experiment yields an invalid readout (numbers recorded, verdict distrusts them)', () => {
    const exposures = [
      ...Array.from({ length: 700 }, (_, i) => exposure(`s${i}`, 'control')),
      ...Array.from({ length: 300 }, (_, i) => exposure(`t${i}`, 'treatment')),
    ];
    const readout = computeReadout({ definition: DEFINITION, exposures, outcomes: [] });
    expect(readout.valid).toBe(false);
    expect(readout.srm.valid).toBe(false);
  });

  it('two-proportion z-test sanity: identical rates → p≈1, large gap → small p', () => {
    expect(twoProportionPValue(50, 100, 50, 100)!).toBeCloseTo(1, 6);
    expect(twoProportionPValue(60, 100, 40, 100)!).toBeLessThan(0.01);
    expect(twoProportionPValue(0, 0, 5, 100)).toBeNull();
  });
});
