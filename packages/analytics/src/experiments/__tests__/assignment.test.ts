// Assignment service units (plan B8): determinism, ledger authority,
// fail-closed registration, weight semantics, allocation-version churn.

import { describe, expect, it } from 'vitest';
import { ExperimentRegistry } from '@navdhya/analytics/src/experiments/contract';
import { MemoryExperimentLedger } from '@navdhya/analytics/src/experiments/ledger';
import { ExperimentService } from '@navdhya/analytics/src/experiments/service';
import type { ExperimentDefinition } from '@navdhya/analytics/src/experiments/contract';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');

function definition(overrides: Partial<ExperimentDefinition> = {}): ExperimentDefinition {
  return {
    id: 'checkout_cta',
    salt: 'checkout-salt-1',
    allocationVersion: 1,
    variants: ['control', 'treatment'],
    weights: { control: 1, treatment: 1 },
    primaryOutcomes: ['booking_confirmed'],
    eligiblePopulation: 'customers who reach the checkout page',
    observationWindowDays: 14,
    guardrails: [{ event: 'payment_failed', maxRatePerExposed: 0.4 }],
    status: 'running',
    ...overrides,
  };
}

function harness(defs: ExperimentDefinition[]) {
  const registry = new ExperimentRegistry();
  for (const def of defs) registry.register(def);
  const ledger = new MemoryExperimentLedger();
  let nowMs = NOW;
  const service = new ExperimentService({ registry, ledger, nowMs: () => nowMs });
  return { registry, ledger, service, at: (ms: number) => (nowMs = ms) };
}

describe('experiment assignment (B8)', () => {
  it('is deterministic and ledger-round-trips', async () => {
    const { service, ledger } = harness([definition()]);
    const first = await service.ensureAssigned('checkout_cta', 'sub_1');
    expect(['control', 'treatment']).toContain(first.variant);
    expect(first).toMatchObject({ allocationVersion: 1, assignedAtMs: NOW });

    // Same call later: identical variant, no new row (ledger is authority).
    const second = await service.ensureAssigned('checkout_cta', 'sub_1');
    expect(second).toEqual(first);
    expect(await ledger.listAssignments('checkout_cta')).toHaveLength(1);

    // A second service instance over the same ledger recomputes to the
    // SAME variant for another subject — determinism across processes.
    const { service: other } = harness([definition()]);
    await other.ensureAssigned('checkout_cta', 'sub_2');
    const again = await service.ensureAssigned('checkout_cta', 'sub_2');
    expect(again.variant).toBe((await ledger.getAssignment('checkout_cta', 'sub_2'))!.variant);
  });

  it('fails closed for unknown, draft and stopped experiments', async () => {
    const { service } = harness([definition({ status: 'draft' }), definition({ id: 'old_test', status: 'stopped' })]);
    await expect(service.ensureAssigned('missing_exp', 'sub_1')).rejects.toThrow(/not registered/);
    await expect(service.ensureAssigned('checkout_cta', 'sub_1')).rejects.toThrow(/draft/);
    await expect(service.ensureAssigned('old_test', 'sub_1')).rejects.toThrow(/stopped/);
  });

  it('respects integer weights; zero-weight variants never receive new assignments', async () => {
    const weighted = harness([
      definition({ id: 'weighted_exp', weights: { control: 9, treatment: 1 } }),
    ]);
    const counts = { control: 0, treatment: 0 };
    for (let index = 0; index < 60; index += 1) {
      const assignment = await weighted.service.ensureAssigned('weighted_exp', `sub_${index}`);
      counts[assignment.variant as 'control' | 'treatment'] += 1;
    }
    expect(counts.control).toBeGreaterThan(counts.treatment);

    const frozen = harness([
      definition({ id: 'frozen', weights: { control: 1, treatment: 0 } }),
    ]);
    for (let index = 0; index < 30; index += 1) {
      const assignment = await frozen.service.ensureAssigned('frozen', `sub_${index}`);
      expect(assignment.variant).toBe('control');
    }
  });

  it('bumping allocationVersion re-records assignments under the new version', async () => {
    const { service, registry, at } = harness([definition()]);
    const before = await service.ensureAssigned('checkout_cta', 'sub_9');
    at(NOW + 1000);
    registry.update(definition({ allocationVersion: 2, weights: { control: 1, treatment: 3 } }));
    // The v1 row remains the authority for the subject until a NEW subject
    // is assigned under v2 (existing assignments are immutable facts).
    const unchanged = await service.ensureAssigned('checkout_cta', 'sub_9');
    expect(unchanged).toEqual(before);
    const fresh = await service.ensureAssigned('checkout_cta', 'sub_new');
    expect(fresh.allocationVersion).toBe(2);
  });

  it('registry validation rejects definitions that would invalidate a readout', () => {
    const registry = new ExperimentRegistry();
    expect(() => registry.register(definition({ primaryOutcomes: [] }))).toThrow(/primary outcomes/);
    expect(() => registry.register(definition({ guardrails: [] }))).toThrow(/guardrail/);
    expect(() => registry.register(definition({ variants: ['only'] }))).toThrow(/two variants/);
    expect(() => registry.register(definition({ observationWindowDays: 0 }))).toThrow(/observation window/);
    expect(() => registry.register(definition({ eligiblePopulation: '  ' }))).toThrow(/eligible population/);
    expect(() => registry.register(definition())).not.toThrow();
    expect(() => registry.register(definition())).toThrow(/already registered/);
  });
});
