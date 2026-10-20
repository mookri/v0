// Experiment contract + registry (plan B8, phase-1 experimentation).
//
// Pre-registration is the credibility mechanism: primary outcomes, eligible
// population, observation window and guardrails are pinned BEFORE assignment
// starts, so a readout cannot be retro-fitted to whatever looked good.
// Guardrails must cover cancellation/refunds/support/reliability — not
// conversion alone (plan wording, enforced by validation).
//
// Honesty notes:
//   - eligiblePopulation is a DOCUMENTED string, not a predicate: phase 1
//     applies eligibility at the call site (the surface decides who enters);
//     the registry records the commitment, it does not enforce it.
//   - Advanced methods (CUPED, sequential testing, switchback) are later
//     capabilities gated on phase-1 validity — not implemented here.
//   - Remote Config may deliver experiment CONFIGURATION, but the ledger
//     (B8 ledger.ts) is the assignment authority.

export type ExperimentStatus = 'draft' | 'running' | 'stopped';

export interface Guardrail {
  /** Domain event name watched for regressions (e.g. payment_failed). */
  event: string;
  /** Pre-registered breach threshold: exposed subjects with the event /
   *  exposed subjects. Breach ⇒ readout flagged. */
  maxRatePerExposed: number;
  note?: string;
}

export interface ExperimentDefinition {
  id: string;
  /** Per-experiment salt — assignment input hygiene (plan B8 bullet 1). */
  salt: string;
  /** Bump on any weight/variant change; recorded on every assignment so
   *  readouts stay interpretable across re-allocations. */
  allocationVersion: number;
  variants: readonly string[];
  /** Integer weights per variant (sum > 0). variants[0] is the control. */
  weights: Readonly<Record<string, number>>;
  /** Pre-registered primary outcomes (domain event names). */
  primaryOutcomes: readonly string[];
  /** Documented eligibility criteria applied at the call site. */
  eligiblePopulation: string;
  observationWindowDays: number;
  guardrails: readonly Guardrail[];
  status: ExperimentStatus;
}

/** Registration problems — empty array means valid. */
export function validateDefinition(definition: ExperimentDefinition): string[] {
  const problems: string[] = [];
  if (!/^[a-z0-9][a-z0-9_-]{2,63}$/.test(definition.id)) {
    problems.push('id must be a 3-64 char slug ([a-z0-9_-])');
  }
  if (definition.variants.length < 2) problems.push('at least two variants are required');
  if (new Set(definition.variants).size !== definition.variants.length) {
    problems.push('variants must be unique');
  }
  let total = 0;
  for (const variant of definition.variants) {
    const weight = definition.weights[variant];
    if (weight === undefined || !Number.isInteger(weight) || weight < 0) {
      problems.push(`variant '${variant}' needs a non-negative integer weight`);
    } else {
      total += weight;
    }
  }
  if (total <= 0) problems.push('weights must sum to a positive integer');
  if (definition.primaryOutcomes.length === 0) {
    problems.push('pre-registered primary outcomes are required');
  }
  if (!Number.isFinite(definition.observationWindowDays) || definition.observationWindowDays <= 0) {
    problems.push('observation window must be a positive number of days');
  }
  if (definition.eligiblePopulation.trim().length === 0) {
    problems.push('eligible population must be documented');
  }
  for (const guardrail of definition.guardrails) {
    if (!Number.isFinite(guardrail.maxRatePerExposed) || guardrail.maxRatePerExposed <= 0) {
      problems.push(`guardrail '${guardrail.event}' needs a positive maxRatePerExposed`);
    }
  }
  // Plan B8: guardrails cover more than conversion — cancellation, refunds,
  // support contacts, reliability. Pin at least one guardrail event.
  if (definition.guardrails.length === 0) {
    problems.push('at least one guardrail (cancellation/refund/support/reliability) is required — conversion alone is not enough');
  }
  return problems;
}

export class ExperimentRegistry {
  private readonly definitions = new Map<string, ExperimentDefinition>();

  register(definition: ExperimentDefinition): void {
    const problems = validateDefinition(definition);
    if (problems.length > 0) {
      throw new Error(`experiment '${definition.id}' rejected: ${problems.join('; ')}`);
    }
    if (this.definitions.has(definition.id)) {
      throw new Error(`experiment '${definition.id}' already registered — use update() with a bumped allocationVersion`);
    }
    this.definitions.set(definition.id, Object.freeze({ ...definition }));
  }

  /** Config change path: allocationVersion must strictly increase so every
   *  stored assignment stays attributable to the config that produced it. */
  update(definition: ExperimentDefinition): void {
    const existing = this.definitions.get(definition.id);
    if (!existing) throw new Error(`experiment '${definition.id}' is not registered`);
    if (definition.allocationVersion <= existing.allocationVersion) {
      throw new Error(
        `update('${definition.id}') must bump allocationVersion (have ${existing.allocationVersion}, got ${definition.allocationVersion})`,
      );
    }
    const problems = validateDefinition(definition);
    if (problems.length > 0) {
      throw new Error(`experiment '${definition.id}' update rejected: ${problems.join('; ')}`);
    }
    this.definitions.set(definition.id, Object.freeze({ ...definition }));
  }

  get(id: string): ExperimentDefinition | null {
    return this.definitions.get(id) ?? null;
  }
}
