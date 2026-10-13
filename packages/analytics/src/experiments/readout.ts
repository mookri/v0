// Experiment readout (plan B8 bullet 1 + evaluation criterion 1): a
// REPRODUCIBLE computation — a pure function of the ledger's exposures and
// first-party outcome events, nothing else. Same inputs ⇒ byte-identical
// readout; no warehouse round-trip, no manual adjustment.
//
// Honesty notes:
//   - Outcomes are counted per EXPOSED subject within the pre-registered
//     observation window ([exposedAt, exposedAt + window]); out-of-window
//     events never count (post-hoc windowing is how experiments lie).
//   - The primary comparison is variants[0] (pre-registered control) vs
//     variants[1] on primaryOutcomes[0]; two-proportion z-test.
//   - A failed SRM check marks the whole readout invalid — numbers are
//     still computed (for the record) but the verdict says do not trust.
//   - Advanced methods (CUPED/sequential/switchback) are NOT here — the
//     plan gates them on phase-1 validity evidence.

import type { ExperimentDefinition } from './contract';
import type { ExperimentExposure } from './ledger';
import { checkSrm, type SrmResult } from './srm';

export interface OutcomeEvent {
  subjectIdHash: string;
  event: string;
  atMs: number;
}

/** erf complementary, Chebyshev approximation (|fractional error| < 1.2e-7
 *  everywhere — Numerical Recipes erfcc; ample for a z-test p-value). */
function erfcc(x: number): number {
  const z = Math.abs(x);
  const t = 1 / (1 + 0.5 * z);
  const ans =
    t *
    Math.exp(
      -z * z -
        1.26551223 +
        t *
          (1.00002368 +
            t *
              (0.37409196 +
                t *
                  (0.09678418 +
                    t *
                      (-0.18628806 +
                        t *
                          (0.27886807 +
                            t * (-1.13520398 + t * (1.48851587 + t * (-0.82215223 + t * 0.17087277)))))))),
    );
  return x >= 0 ? ans : 2 - ans;
}

/** Two-tailed p-value for the two-proportion z-test. */
export function twoProportionPValue(
  successesA: number,
  nA: number,
  successesB: number,
  nB: number,
): number | null {
  if (nA === 0 || nB === 0) return null;
  const pA = successesA / nA;
  const pB = successesB / nB;
  const pooled = (successesA + successesB) / (nA + nB);
  const se = Math.sqrt(pooled * (1 - pooled) * (1 / nA + 1 / nB));
  if (se === 0) return pA === pB ? 1 : 0;
  const z = (pA - pB) / se;
  return erfcc(Math.abs(z) / Math.SQRT2);
}

export interface OutcomeReadout {
  count: number;
  rate: number;
}

export interface VariantReadout {
  variant: string;
  exposed: number;
  outcomes: Record<string, OutcomeReadout>;
  guardrails: Record<string, OutcomeReadout & { breached: boolean }>;
}

export interface PrimaryComparison {
  outcome: string;
  controlVariant: string;
  treatmentVariant: string;
  controlRate: number;
  treatmentRate: number;
  absoluteLift: number;
  relativeLift: number | null;
  pValue: number | null;
}

export interface ExperimentReadout {
  experimentId: string;
  allocationVersion: number;
  observationWindowDays: number;
  srm: SrmResult;
  variants: VariantReadout[];
  primaryComparison: PrimaryComparison | null;
  guardrailBreaches: string[];
  /** false when SRM flagged the experiment — do not act on the numbers. */
  valid: boolean;
  /** Reproducibility statement (test-pinned). */
  basis: string;
}

export const READOUT_BASIS =
  'reproducible from the experiment ledger + first-party exposure/outcome events alone; no GA4 or third-party data in the computation';

export interface ReadoutInputs {
  definition: ExperimentDefinition;
  exposures: readonly ExperimentExposure[];
  outcomes: readonly OutcomeEvent[];
}

function withinWindow(
  exposure: ExperimentExposure,
  event: OutcomeEvent,
  windowMs: number,
): boolean {
  return event.atMs >= exposure.exposedAtMs && event.atMs <= exposure.exposedAtMs + windowMs;
}

export function computeReadout(inputs: ReadoutInputs): ExperimentReadout {
  const { definition, exposures, outcomes } = inputs;
  const windowMs = definition.observationWindowDays * 24 * 60 * 60 * 1000;
  const srm = checkSrm(exposures, definition.weights);

  // Exposure per subject (first exposure wins — the ledger already
  // deduplicates, this is defence in depth for foreign inputs).
  const exposureBySubject = new Map<string, ExperimentExposure>();
  for (const exposure of exposures) {
    if (!exposureBySubject.has(exposure.subjectIdHash)) {
      exposureBySubject.set(exposure.subjectIdHash, exposure);
    }
  }

  const variants: VariantReadout[] = definition.variants.map((variant) => ({
    variant,
    exposed: 0,
    outcomes: Object.fromEntries(
      definition.primaryOutcomes.map((outcome) => [outcome, { count: 0, rate: 0 }]),
    ),
    guardrails: Object.fromEntries(
      definition.guardrails.map((guardrail) => [
        guardrail.event,
        { count: 0, rate: 0, breached: false },
      ]),
    ),
  }));
  const variantByName = new Map(variants.map((row) => [row.variant, row]));

  for (const exposure of exposures) {
    const row = variantByName.get(exposure.variant);
    if (!row) continue; // unregistered variant — SRM check already flags it
    row.exposed += 1;
    const windowEvents = outcomes.filter(
      (event) =>
        event.subjectIdHash === exposure.subjectIdHash && withinWindow(exposure, event, windowMs),
    );
    for (const outcome of definition.primaryOutcomes) {
      if (windowEvents.some((event) => event.event === outcome)) row.outcomes[outcome]!.count += 1;
    }
    for (const guardrail of definition.guardrails) {
      if (windowEvents.some((event) => event.event === guardrail.event)) {
        row.guardrails[guardrail.event]!.count += 1;
      }
    }
  }

  const guardrailBreaches: string[] = [];
  for (const row of variants) {
    for (const guardrail of definition.guardrails) {
      const readout = row.guardrails[guardrail.event]!;
      readout.rate = row.exposed > 0 ? readout.count / row.exposed : 0;
      readout.breached = readout.rate > guardrail.maxRatePerExposed;
      if (readout.breached) guardrailBreaches.push(`${row.variant}:${guardrail.event}`);
    }
    for (const outcome of definition.primaryOutcomes) {
      const readout = row.outcomes[outcome]!;
      readout.rate = row.exposed > 0 ? readout.count / row.exposed : 0;
    }
  }

  const control = variants[0];
  const treatment = variants[1];
  const primaryOutcome = definition.primaryOutcomes[0] ?? null;
  let primaryComparison: PrimaryComparison | null = null;
  if (control && treatment && primaryOutcome) {
    const controlRate = control.outcomes[primaryOutcome]!.rate;
    const treatmentRate = treatment.outcomes[primaryOutcome]!.rate;
    primaryComparison = {
      outcome: primaryOutcome,
      controlVariant: control.variant,
      treatmentVariant: treatment.variant,
      controlRate,
      treatmentRate,
      absoluteLift: treatmentRate - controlRate,
      relativeLift: controlRate > 0 ? (treatmentRate - controlRate) / controlRate : null,
      pValue: twoProportionPValue(
        treatment.outcomes[primaryOutcome]!.count,
        treatment.exposed,
        control.outcomes[primaryOutcome]!.count,
        control.exposed,
      ),
    };
  }

  return {
    experimentId: definition.id,
    allocationVersion: definition.allocationVersion,
    observationWindowDays: definition.observationWindowDays,
    srm,
    variants,
    primaryComparison,
    guardrailBreaches,
    valid: srm.valid,
    basis: READOUT_BASIS,
  };
}
