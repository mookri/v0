// B8 acceptance — experiment ledger (plan B8):
//   1. Assignment determinism (incl. output parity with the app primitive
//      `deterministicHash` from apps/web/lib/dlo-x/ab-testing.ts) + ledger
//      round-trip.
//   2. Exposure-without-display produces NO exposure row; the ledger is the
//      assignment authority across service instances.
//   3. SRM fixture: a skewed allocation is flagged invalid automatically.
//   4. A readout is reproducible from the ledger + first-party data alone.
import { describe, expect, it } from 'vitest';
import { createHash } from 'node:crypto';
import { ExperimentRegistry, type ExperimentDefinition } from '@navdhya/analytics/src/experiments/contract';
import { MemoryExperimentLedger } from '@navdhya/analytics/src/experiments/ledger';
import { ExperimentService } from '@navdhya/analytics/src/experiments/service';
import { READOUT_BASIS } from '@navdhya/analytics/src/experiments/readout';
import { deterministicHash as packageDeterministicHash } from '@navdhya/analytics/src/experiments/deterministic';

// The plan names apps/web/lib/dlo-x/ab-testing.ts deterministicHash as THE
// primitive. Its implementation is node:crypto (forbidden in
// packages/analytics — Web Crypto only), and the import-alias gate forbids
// parent-directory imports, so this is a LOCAL reimplementation of the
// documented algorithm (SHA-256 → first 4 bytes big-endian u32 →
// rejection-sample into [0,100]). If the app function ever changes
// algorithm, this reference must change with it — that coupling is the
// cost of keeping the gate honest instead of routing around it.
function referenceDeterministicHash(input: string): number {
  if (typeof input !== 'string' || input.length === 0) return 0;
  const digest = createHash('sha256').update(input, 'utf8').digest();
  const u32 = digest.readUInt32BE(0);
  const maxUnbiased = Math.floor(0x1_0000_0000 / 101) * 101;
  if (u32 < maxUnbiased) return u32 % 101;
  const u32b = digest.readUInt32BE(4);
  if (u32b < maxUnbiased) return u32b % 101;
  return u32b % 101;
}

const NOW = Date.parse('2026-10-13T12:00:00.000Z');
const DAY = 24 * 60 * 60 * 1000;

function definition(overrides: Partial<ExperimentDefinition> = {}): ExperimentDefinition {
  return {
    id: 'checkout_cta',
    salt: 'checkout-salt-1',
    allocationVersion: 1,
    variants: ['control', 'treatment'],
    weights: { control: 1, treatment: 1 },
    primaryOutcomes: ['booking_confirmed'],
    eligiblePopulation: 'customers who reach checkout',
    observationWindowDays: 14,
    guardrails: [{ event: 'payment_failed', maxRatePerExposed: 0.4, note: 'refund/support load' }],
    status: 'running',
    ...overrides,
  };
}

function harness(defs: ExperimentDefinition[] = [definition()]) {
  const registry = new ExperimentRegistry();
  for (const def of defs) registry.register(def);
  const ledger = new MemoryExperimentLedger();
  let nowMs = NOW;
  const service = new ExperimentService({ registry, ledger, nowMs: () => nowMs });
  return { registry, ledger, service, at: (ms: number) => (nowMs = ms) };
}

describe('B8 acceptance — assignment + ledger', () => {
  it('package deterministicHash matches the app primitive algorithm bit-for-bit', async () => {
    const inputs = [
      'user_1234',
      'v2:abcDEF-_123:checkout_cta',
      '',
      'a',
      'subject-with-unicode-ॐ',
      'x'.repeat(500),
    ];
    for (const input of inputs) {
      expect(await packageDeterministicHash(input)).toBe(referenceDeterministicHash(input));
    }
  });

  it('assignment is deterministic and the ledger round-trips it', async () => {
    const { service, ledger } = harness();
    const assigned = await service.ensureAssigned('checkout_cta', 'sub_1');
    // Same subject, later call: same variant, single immutable row.
    expect(await service.ensureAssigned('checkout_cta', 'sub_1')).toEqual(assigned);
    const rows = await ledger.listAssignments('checkout_cta');
    expect(rows).toHaveLength(1);
    expect(rows[0]).toEqual(assigned);

    // A separate service (fresh process semantics) over the SAME ledger
    // hands out the SAME variant for a new subject: determinism, not state.
    const freshRegistry = new ExperimentRegistry();
    freshRegistry.register(definition());
    const freshService = new ExperimentService({
      registry: freshRegistry,
      ledger,
      nowMs: () => NOW,
    });
    const other = await freshService.ensureAssigned('checkout_cta', 'sub_2');
    expect((await ledger.getAssignment('checkout_cta', 'sub_2'))!.variant).toBe(other.variant);
  });

  it('exposure-without-display produces NO exposure row; display is an explicit call', async () => {
    const { service, ledger } = harness();
    await service.ensureAssigned('checkout_cta', 'sub_1');
    await service.ensureAssigned('checkout_cta', 'sub_2');
    // Assigned but never displayed ⇒ zero exposures (acceptance item 2).
    expect(await ledger.listExposures('checkout_cta')).toHaveLength(0);

    expect(await service.logExposure('checkout_cta', 'sub_1')).toBe('recorded');
    expect(await service.logExposure('checkout_cta', 'sub_1')).toBe('already_exposed');
    // Exposure without assignment fails closed — unrandomised subjects
    // never enter SRM counts.
    expect(await service.logExposure('checkout_cta', 'sub_never')).toBe('no_assignment');
    const exposures = await ledger.listExposures('checkout_cta');
    expect(exposures).toHaveLength(1);
    expect(exposures[0]).toMatchObject({ subjectIdHash: 'sub_1', variant: assigned2(await service) });
  });

  it('SRM: a skewed fixture is flagged invalid automatically; a fair split passes', async () => {
    const { service } = harness();
    const subjects = Array.from({ length: 1000 }, (_, index) => `sub_${index}`);
    for (const subject of subjects) await service.ensureAssigned('checkout_cta', subject);
    // Rig the DISPLAY step: 70% of treatment subjects see the experience,
    // 100% of control subjects do — the classic silent SRM.
    const assignments = await Promise.all(subjects.map((s) => service.ensureAssigned('checkout_cta', s)));
    for (const assignment of assignments) {
      const exposed =
        assignment.variant === 'control' || Number(assignment.subjectIdHash.split('_')[1]) % 10 < 3;
      if (exposed) await service.logExposure('checkout_cta', assignment.subjectIdHash);
    }
    const srm = await service.checkSrm('checkout_cta');
    expect(srm.valid).toBe(false);
    expect(srm.reason).toContain('flagged invalid');
    // The readout built on it is marked invalid too — numbers recorded,
    // verdict says do not trust.
    const readout = await service.computeReadout('checkout_cta', []);
    expect(readout.valid).toBe(false);
  });

  it('readout is reproducible from the ledger + first-party outcomes alone', async () => {
    const { service, at } = harness();
    for (let index = 0; index < 80; index += 1) {
      await service.ensureAssigned('checkout_cta', `sub_${index}`);
      await service.logExposure('checkout_cta', `sub_${index}`);
    }
    const outcomes = [
      ...Array.from({ length: 12 }, (_, i) => ({ subjectIdHash: `sub_${i}`, event: 'booking_confirmed', atMs: NOW + DAY })),
      { subjectIdHash: 'sub_0', event: 'payment_failed', atMs: NOW + DAY },
      // Out-of-window conversion must never count:
      { subjectIdHash: 'sub_40', event: 'booking_confirmed', atMs: NOW + 90 * DAY },
    ];
    at(NOW + 2 * DAY);
    const first = await service.computeReadout('checkout_cta', outcomes);
    const second = await service.computeReadout('checkout_cta', outcomes);
    expect(second).toEqual(first); // byte-identical
    expect(first.basis).toBe(READOUT_BASIS);
    const totalConversions = first.variants.reduce(
      (sum, variant) => sum + variant.outcomes.booking_confirmed!.count,
      0,
    );
    expect(totalConversions).toBe(12); // sub_40's out-of-window event excluded
    expect(Array.isArray(first.guardrailBreaches)).toBe(true);
  });
});

async function assigned2(service: ExperimentService): Promise<string> {
  return (await service.ensureAssigned('checkout_cta', 'sub_1')).variant;
}
