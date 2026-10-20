// Experiment service (plan B8 bullet 1): deterministic assignment over the
// registry + ledger, exposure logging gated on actual display, and
// reproducible readouts.
//
// Postures (all evaluated):
//   - The LEDGER is the assignment authority: ensureAssigned reads the
//     ledger first and only computes when no row exists — a subject keeps
//     its variant across restarts, weight edits (allocation_version) and
//     deployments. Remote Config may deliver variant CONFIGURATION, never
//     the assignment decision.
//   - Assignments are immutable facts (first-write-wins).
//   - Exposure ≠ assignment: logExposure is the explicit "the experience
//     was actually displayed" call, and it REFUSES when no assignment
//     exists (acceptance: exposure-without-display produces no row).
//   - Unknown or non-running experiments fail closed.

import type { ExperimentDefinition } from './contract';
import type { ExperimentRegistry } from './contract';
import { pickVariant } from './deterministic';
import type { ExperimentAssignment, ExperimentLedgerStore } from './ledger';
import { computeReadout, type ExperimentReadout, type OutcomeEvent } from './readout';
import { checkSrm, type SrmResult } from './srm';

export type ExposureOutcome = 'recorded' | 'already_exposed' | 'no_assignment';

export interface ExperimentServiceDeps {
  registry: ExperimentRegistry;
  ledger: ExperimentLedgerStore;
  nowMs: () => number;
}

export class ExperimentService {
  constructor(private readonly deps: ExperimentServiceDeps) {}

  private requireDefinition(experimentId: string): ExperimentDefinition {
    const definition = this.deps.registry.get(experimentId);
    if (!definition) {
      throw new Error(`experiment '${experimentId}' is not registered — refusing to assign`);
    }
    return definition;
  }

  /** Get-or-create the subject's assignment. Deterministic: same subject +
   *  same experiment + same salt + same allocation_version ⇒ same variant,
   *  on every surface, forever. */
  async ensureAssigned(experimentId: string, subjectIdHash: string): Promise<ExperimentAssignment> {
    const definition = this.requireDefinition(experimentId);
    if (definition.status !== 'running') {
      throw new Error(
        `experiment '${experimentId}' is ${definition.status} — assignment only allowed while running`,
      );
    }

    const existing = await this.deps.ledger.getAssignment(experimentId, subjectIdHash);
    if (existing) return existing;

    const variant = await pickVariant(
      { subjectIdHash, experimentId, salt: definition.salt, allocationVersion: definition.allocationVersion },
      definition.variants,
      definition.weights,
    );
    const assignment: ExperimentAssignment = {
      experimentId,
      subjectIdHash,
      variant,
      allocationVersion: definition.allocationVersion,
      assignedAtMs: this.deps.nowMs(),
    };
    await this.deps.ledger.putAssignment(assignment);
    // First-write-wins: if a racing request stored first, that row is the
    // authority — return it rather than our computation.
    return (await this.deps.ledger.getAssignment(experimentId, subjectIdHash)) ?? assignment;
  }

  /** The explicit "experience displayed" signal. Assignment alone NEVER
   *  produces an exposure row (plan acceptance item 1). */
  async logExposure(experimentId: string, subjectIdHash: string): Promise<ExposureOutcome> {
    this.requireDefinition(experimentId);
    const assignment = await this.deps.ledger.getAssignment(experimentId, subjectIdHash);
    if (!assignment) {
      // Fail closed: an exposure without an assignment is a wiring bug —
      // recording it would poison SRM with unrandomised subjects.
      return 'no_assignment';
    }
    const result = await this.deps.ledger.recordExposure({
      experimentId,
      subjectIdHash,
      variant: assignment.variant,
      exposedAtMs: this.deps.nowMs(),
    });
    return result === 'stored' ? 'recorded' : 'already_exposed';
  }

  async checkSrm(experimentId: string): Promise<SrmResult> {
    const definition = this.requireDefinition(experimentId);
    const exposures = await this.deps.ledger.listExposures(experimentId);
    return checkSrm(exposures, definition.weights);
  }

  /** Reproducible readout from ledger + first-party outcome events. */
  async computeReadout(experimentId: string, outcomes: readonly OutcomeEvent[]): Promise<ExperimentReadout> {
    const definition = this.requireDefinition(experimentId);
    const exposures = await this.deps.ledger.listExposures(experimentId);
    return computeReadout({ definition, exposures, outcomes });
  }
}
