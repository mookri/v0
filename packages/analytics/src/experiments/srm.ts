// SRM (sample-ratio mismatch) check (plan B8 bullet 1 + acceptance item 2):
// automated chi-square goodness-of-fit over EXPOSURES vs the pre-registered
// allocation. A skewed fixture must be flagged invalid automatically — SRM
// means the experiment's randomisation broke somewhere upstream (redirects,
// targeting, bot filtering), so any readout from it is uninterpretable.
//
// Stats are implemented here (no dependency): chi-square p-value via the
// regularized upper incomplete gamma (Lanczos + series/continued-fraction,
// Numerical Recipes formulation — fractional error ~1e-7, far beyond what
// an SRM gate needs).

/** Flag threshold: standard experiment-guard p-value. */
export const SRM_P_THRESHOLD = 0.001;
/** Below this exposure count SRM has no power — recorded, never flagged. */
export const SRM_MIN_EXPOSED = 100;

function gammln(x: number): number {
  const cof = [
    76.18009172947146, -86.50532032941677, 24.01409824083091, -1.231739572450155,
    0.1208650973866179e-2, -0.5395239384953e-5,
  ];
  let tmp = x + 5.5;
  tmp -= (x + 0.5) * Math.log(tmp);
  let ser = 1.000000000190015;
  for (let j = 0; j < 6; j += 1) ser += cof[j]! / (x + j + 1);
  return -tmp + Math.log((2.5066282746310005 * ser) / x);
}

function gammaSeries(a: number, x: number): number {
  let sum = 1 / a;
  let term = sum;
  for (let n = 0; n < 200; n += 1) {
    term *= x / (a + n + 1);
    sum += term;
    if (Math.abs(term) < Math.abs(sum) * 1e-12) break;
  }
  return sum * Math.exp(-x + a * Math.log(x) - gammln(a));
}

function gammaContinuedFraction(a: number, x: number): number {
  const tiny = 1e-30;
  let b = x + 1 - a;
  let c = 1 / tiny;
  let d = 1 / b;
  let h = d;
  for (let i = 1; i <= 200; i += 1) {
    const an = -i * (i - a);
    b += 2;
    d = an * d + b;
    if (Math.abs(d) < tiny) d = tiny;
    c = b + an / c;
    if (Math.abs(c) < tiny) c = tiny;
    d = 1 / d;
    const delta = d * c;
    h *= delta;
    if (Math.abs(delta - 1) < 1e-12) break;
  }
  return Math.exp(-x + a * Math.log(x) - gammln(a)) * h;
}

/** Regularized upper incomplete gamma Q(a, x). */
export function gammaQ(a: number, x: number): number {
  if (x < 0 || a <= 0) throw new Error(`gammaQ: invalid args a=${a} x=${x}`);
  if (x < a + 1) return 1 - gammaSeries(a, x);
  return gammaContinuedFraction(a, x);
}

/** Upper-tail p-value of the chi-square statistic with `df` degrees of
 *  freedom: P(X^2 >= chi2). */
export function chiSquarePValue(chi2: number, df: number): number {
  if (df <= 0) throw new Error(`chiSquarePValue: df must be positive, got ${df}`);
  return gammaQ(df / 2, chi2 / 2);
}

export interface SrmResult {
  totalExposed: number;
  observed: Record<string, number>;
  expected: Record<string, number>;
  chiSquare: number;
  pValue: number;
  /** false ⇒ the experiment is INVALID for readout (flagged automatically). */
  valid: boolean;
  reason: string;
}

export interface ExposureLike {
  variant: string;
}

/** Compare observed exposure counts against the planned allocation. */
export function checkSrm(
  exposures: readonly ExposureLike[],
  weights: Readonly<Record<string, number>>,
  options: { threshold?: number; minExposed?: number } = {},
): SrmResult {
  const threshold = options.threshold ?? SRM_P_THRESHOLD;
  const minExposed = options.minExposed ?? SRM_MIN_EXPOSED;

  const observed: Record<string, number> = {};
  for (const exposure of exposures) observed[exposure.variant] = (observed[exposure.variant] ?? 0) + 1;
  const variants = Object.keys(weights).sort();
  const totalWeight = variants.reduce((sum, variant) => sum + (weights[variant] ?? 0), 0);
  const totalExposed = exposures.length;

  const expected: Record<string, number> = {};
  for (const variant of variants) {
    expected[variant] = totalWeight > 0 ? (totalExposed * (weights[variant] ?? 0)) / totalWeight : 0;
  }
  // Variants observed but absent from the weights are themselves an SRM
  // signal — fold them into the check as zero-expected (infinite chi²).
  const unexpected = Object.keys(observed).filter((variant) => !(variant in weights));

  const zeroExpectedWithCount = variants.some(
    (variant) => (weights[variant] ?? 0) === 0 && (observed[variant] ?? 0) > 0,
  );

  let chiSquare = 0;
  for (const variant of variants) {
    const obs = observed[variant] ?? 0;
    const exp = expected[variant] ?? 0;
    if (exp > 0) chiSquare += (obs - exp) ** 2 / exp;
  }

  const df = Math.max(1, variants.filter((variant) => (expected[variant] ?? 0) > 0).length - 1);

  if (totalExposed < minExposed) {
    return {
      totalExposed,
      observed,
      expected,
      chiSquare,
      pValue: 1,
      valid: true,
      reason: `only ${totalExposed} exposures (< ${minExposed}) — SRM has no power here, not flagged`,
    };
  }
  if (unexpected.length > 0 || zeroExpectedWithCount) {
    const detail = [
      unexpected.length > 0 ? `exposures in unregistered variants: ${unexpected.join(', ')}` : '',
      zeroExpectedWithCount ? 'exposures in zero-weight variants' : '',
    ]
      .filter(Boolean)
      .join('; ');
    return {
      totalExposed,
      observed,
      expected,
      chiSquare: Number.POSITIVE_INFINITY,
      pValue: 0,
      valid: false,
      reason: `SRM: ${detail} — experiment flagged invalid`,
    };
  }

  const pValue = chiSquarePValue(chiSquare, df);
  const valid = pValue >= threshold;
  return {
    totalExposed,
    observed,
    expected,
    chiSquare,
    pValue,
    valid,
    reason: valid
      ? `allocation matches plan (chi2=${chiSquare.toFixed(2)}, p=${pValue.toFixed(4)} >= ${threshold})`
      : `SRM: exposure split deviates from planned allocation (chi2=${chiSquare.toFixed(2)}, p=${pValue.toFixed(6)} < ${threshold}) — experiment flagged invalid`,
  };
}
