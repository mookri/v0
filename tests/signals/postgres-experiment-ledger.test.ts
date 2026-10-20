// B8 acceptance — Postgres experiment ledger (statement-level, via fake
// client — the B2/B6 posture: no database credential in this repo):
// first-write-wins assignments, single exposure per subject, ordered lists,
// and the ExperimentService running end-to-end over the Postgres store.

import { describe, expect, it } from 'vitest';
import { ExperimentRegistry, type ExperimentDefinition } from '@navdhya/analytics/src/experiments/contract';
import { ExperimentService } from '@navdhya/analytics/src/experiments/service';
import { PostgresExperimentLedgerStore } from '@infra/experiments/PostgresExperimentLedgerStore';
import { FakeExperimentClient } from './helpers/fake-experiment-client';

const NOW = Date.parse('2026-10-13T12:00:00.000Z');

const DEFINITION: ExperimentDefinition = {
  id: 'checkout_cta',
  salt: 's1',
  allocationVersion: 1,
  variants: ['control', 'treatment'],
  weights: { control: 1, treatment: 1 },
  primaryOutcomes: ['booking_confirmed'],
  eligiblePopulation: 'checkout visitors',
  observationWindowDays: 14,
  guardrails: [{ event: 'payment_failed', maxRatePerExposed: 0.4 }],
  status: 'running',
};

function store() {
  const db = new FakeExperimentClient();
  return { db, ledger: new PostgresExperimentLedgerStore(db) };
}

describe('B8 acceptance — Postgres experiment ledger', () => {
  it('assignment round-trips; first-write-wins never overwrites', async () => {
    const { ledger, db } = store();
    const stored = await ledger.putAssignment({
      experimentId: 'checkout_cta',
      subjectIdHash: 'sub_1',
      variant: 'control',
      allocationVersion: 1,
      assignedAtMs: NOW,
    });
    expect(stored).toBe('stored');
    // A racing assignment computes 'treatment' — the first row is authority.
    const dup = await ledger.putAssignment({
      experimentId: 'checkout_cta',
      subjectIdHash: 'sub_1',
      variant: 'treatment',
      allocationVersion: 2,
      assignedAtMs: NOW + 1000,
    });
    expect(dup).toBe('already_exists');
    expect(await ledger.getAssignment('checkout_cta', 'sub_1')).toMatchObject({
      variant: 'control',
      allocationVersion: 1,
      assignedAtMs: NOW,
    });
    expect(db.assignments.size).toBe(1);

    await ledger.putAssignment({
      experimentId: 'checkout_cta',
      subjectIdHash: 'sub_0',
      variant: 'treatment',
      allocationVersion: 1,
      assignedAtMs: NOW,
    });
    const all = await ledger.listAssignments('checkout_cta');
    expect(all.map((row) => row.subjectIdHash)).toEqual(['sub_0', 'sub_1']); // ordered
    expect(await ledger.listAssignments('other_exp')).toHaveLength(0);
  });

  it('exposures are first-write-wins per subject and list per experiment', async () => {
    const { ledger } = store();
    expect(
      await ledger.recordExposure({
        experimentId: 'checkout_cta',
        subjectIdHash: 'sub_1',
        variant: 'control',
        exposedAtMs: NOW,
      }),
    ).toBe('stored');
    expect(
      await ledger.recordExposure({
        experimentId: 'checkout_cta',
        subjectIdHash: 'sub_1',
        variant: 'control',
        exposedAtMs: NOW + 5000, // re-display
      }),
    ).toBe('already_exists');
    const exposures = await ledger.listExposures('checkout_cta');
    expect(exposures).toHaveLength(1);
    expect(exposures[0]!.exposedAtMs).toBe(NOW);
  });

  it('ExperimentService runs end-to-end over the Postgres store', async () => {
    const { ledger, db } = store();
    const registry = new ExperimentRegistry();
    registry.register(DEFINITION);
    const service = new ExperimentService({ registry, ledger, nowMs: () => NOW });

    const assignment = await service.ensureAssigned('checkout_cta', 'sub_9');
    expect(db.assignments.get('checkout_cta::sub_9')).toMatchObject({ variant: assignment.variant });
    expect(await service.logExposure('checkout_cta', 'sub_9')).toBe('recorded');
    expect(await service.logExposure('checkout_cta', 'sub_ghost')).toBe('no_assignment');
    expect(db.exposures.size).toBe(1);
  });
});
