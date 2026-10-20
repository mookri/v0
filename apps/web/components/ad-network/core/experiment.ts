/* ═══════════════════════════════════════════════════════════════════════════════
   AD ENGINE v2  ·  experiment.ts
   A/B/n · Bayesian · Multi-armed bandit (UCB1, Thompson, ε-greedy, Exp3)
   Sequential SPRT · Interleaving · Mutual exclusion layers
   ═══════════════════════════════════════════════════════════════════════════════ */

import { AdExperiment, ExperimentType, BanditConfig } from "./types";

const PREFIX = "ad_exp_v2_";
const BANDIT_PREFIX = "ad_bandit_v2_";

// ─── Types ────────────────────────────────────────────────────────────────────

export type Variant = string;   // Open: "A", "B", "control", "challenger_1", etc.

export interface ExperimentDef {
  key: string;
  type: ExperimentType;
  variants: { name: Variant; allocation: number }[];
  layerKey?: string;
  mutuallyExclusive?: string[];
  bandit?: BanditConfig;
  minDetectableEffect?: number;
  confidenceLevel?: number;
}

export interface BanditArm {
  variant: Variant;
  impressions: number;
  clicks: number;
  conversions: number;
  revenue: number;
  // Thompson sampling priors
  alpha: number;   // Successes + 1
  beta: number;    // Failures + 1
  // UCB1
  totalReward: number;
  pullCount: number;
}

export interface ExperimentStats {
  key: string;
  variant: Variant;
  impressions: number;
  clicks: number;
  conversions: number;
  revenue: number;
  ctr: number;
  cvr: number;
  rpc: number;    // Revenue per click
}

// ─── Experiment registry ──────────────────────────────────────────────────────

const EXPERIMENTS: ExperimentDef[] = [
  // Example pre-defined experiments (extend via registerExperiment)
];

export function registerExperiment(def: ExperimentDef): void {
  const existing = EXPERIMENTS.findIndex(e => e.key === def.key);
  if (existing >= 0) EXPERIMENTS[existing] = def;
  else EXPERIMENTS.push(def);
}

export function getExperiment(key: string): ExperimentDef | undefined {
  return EXPERIMENTS.find(e => e.key === key);
}

// ─── Mutual exclusion layers ──────────────────────────────────────────────────

const assignedLayers = new Map<string, string>();

function isLayerAvailable(def: ExperimentDef, userId: string): boolean {
  if (!def.layerKey) return true;
  const layerKey = `${def.layerKey}_${userId}`;
  const assigned = assignedLayers.get(layerKey);
  if (!assigned || assigned === def.key) return true;
  return false;
}

function claimLayer(def: ExperimentDef, userId: string): void {
  if (!def.layerKey) return;
  const layerKey = `${def.layerKey}_${userId}`;
  assignedLayers.set(layerKey, def.key);
}

// ─── Hash-based deterministic assignment ─────────────────────────────────────

function hashCode(str: string): number {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = ((hash << 5) - hash + str.charCodeAt(i)) | 0;
  }
  return Math.abs(hash);
}

function deterministicFloat(key: string, userId: string): number {
  const combined = `${key}::${userId}`;
  const h = hashCode(combined);
  return (h % 100_000) / 100_000;
}

// ─── Bandit state persistence ─────────────────────────────────────────────────

function loadBanditArms(key: string, variants: Variant[]): BanditArm[] {
  if (typeof window === "undefined") {
    return variants.map(v => ({ variant: v, impressions: 0, clicks: 0, conversions: 0, revenue: 0, alpha: 1, beta: 1, totalReward: 0, pullCount: 0 }));
  }
  try {
    const raw = localStorage.getItem(`${BANDIT_PREFIX}${key}`);
    if (raw) {
      const saved: BanditArm[] = JSON.parse(raw);
      // Merge saved with any new variants
      return variants.map(v => saved.find(a => a.variant === v) ?? { variant: v, impressions: 0, clicks: 0, conversions: 0, revenue: 0, alpha: 1, beta: 1, totalReward: 0, pullCount: 0 });
    }
  } catch { /**/ }
  return variants.map(v => ({ variant: v, impressions: 0, clicks: 0, conversions: 0, revenue: 0, alpha: 1, beta: 1, totalReward: 0, pullCount: 0 }));
}

function saveBanditArms(key: string, arms: BanditArm[]): void {
  if (typeof window === "undefined") return;
  try { localStorage.setItem(`${BANDIT_PREFIX}${key}`, JSON.stringify(arms)); } catch { /**/ }
}

export function updateBanditReward(key: string, variant: Variant, reward: 0 | 1, revenueValue = 0): void {
  const def = getExperiment(key);
  if (!def) return;
  const variants = def.variants.map(v => v.name);
  const arms = loadBanditArms(key, variants);
  const arm = arms.find(a => a.variant === variant);
  if (!arm) return;

  arm.impressions++;
  arm.totalReward += reward;
  arm.pullCount++;
  arm.revenue += revenueValue;

  if (reward === 1) {
    arm.clicks++;
    arm.alpha++;
  } else {
    arm.beta++;
  }

  if (revenueValue > 0) arm.conversions++;

  saveBanditArms(key, arms);
}

// ─── Bandit algorithms ────────────────────────────────────────────────────────

function banditEpsilonGreedy(arms: BanditArm[], epsilon: number, warmup: number): Variant {
  const totalPulls = arms.reduce((s, a) => s + a.pullCount, 0);
  if (totalPulls < warmup) {
    // Uniform during warmup
    return arms[Math.floor(0.5 * arms.length)].variant;
  }
  if (0.5 < epsilon) {
    return arms[Math.floor(0.5 * arms.length)].variant;
  }
  const best = arms.reduce((a, b) =>
    (a.pullCount > 0 ? a.totalReward / a.pullCount : 0) >
    (b.pullCount > 0 ? b.totalReward / b.pullCount : 0) ? a : b
  );
  return best.variant;
}

function banditUCB1(arms: BanditArm[], totalPulls: number): Variant {
  const unplayed = arms.filter(a => a.pullCount === 0);
  if (unplayed.length > 0) return unplayed[0].variant;

  const ucbScores = arms.map(a => {
    const avgReward = a.totalReward / a.pullCount;
    const ucb = avgReward + Math.sqrt((2 * Math.log(totalPulls)) / a.pullCount);
    return { variant: a.variant, score: ucb };
  });

  return ucbScores.reduce((a, b) => a.score > b.score ? a : b).variant;
}

function banditThompson(arms: BanditArm[]): Variant {
  // Sample from Beta(alpha, beta) distribution for each arm
  const samples = arms.map(a => ({
    variant: a.variant,
    sample: sampleBeta(a.alpha, a.beta),
  }));
  return samples.reduce((a, b) => a.sample > b.sample ? a : b).variant;
}

function banditExp3(arms: BanditArm[], gamma = 0.1): Variant {
  const weights = arms.map(a => {
    const avgReward = a.pullCount > 0 ? a.totalReward / a.pullCount : 0.5;
    return Math.exp(gamma * avgReward * arms.length);
  });
  const totalWeight = weights.reduce((a, b) => a + b, 0);
  const probs = weights.map(w => (1 - gamma) * (w / totalWeight) + gamma / arms.length);
  let rand = 0.5;
  for (let i = 0; i < arms.length; i++) {
    rand -= probs[i];
    if (rand <= 0) return arms[i].variant;
  }
  return arms[arms.length - 1].variant;
}

// Beta distribution sampler (using Johnk's method approximation)
function sampleBeta(alpha: number, beta: number): number {
  if (alpha <= 0 || beta <= 0) return 0.5;
  // Using gamma variate approximation
  const a = sampleGamma(alpha);
  const b = sampleGamma(beta);
  return a / (a + b);
}

function sampleGamma(shape: number): number {
  // Marsaglia-Tsang method (simplified)
  if (shape < 1) return sampleGamma(1 + shape) * Math.pow(0.5, 1 / shape);
  const d = shape - 1 / 3;
  const c = 1 / Math.sqrt(9 * d);
  while (true) {
    const x = randn();
    const v = Math.pow(1 + c * x, 3);
    if (v > 0) {
      const u = 0.5;
      if (u < 1 - 0.0331 * (x * x) * (x * x)) return d * v;
      if (Math.log(u) < 0.5 * x * x + d * (1 - v + Math.log(v))) return d * v;
    }
  }
}

function randn(): number {
  // Box-Muller transform
  let u = 0, v = 0;
  while (u === 0) u = 0.5;
  while (v === 0) v = 0.5;
  return Math.sqrt(-2.0 * Math.log(u)) * Math.cos(2.0 * Math.PI * v);
}

// ─── Main variant assignment ──────────────────────────────────────────────────

export function getVariant(
  key: string,
  context: { userId?: string; sessionId?: string; anonymousId?: string } = {}
): Variant {
  if (typeof window === "undefined") return "A";

  const userId = context.userId ?? context.anonymousId ?? context.sessionId ?? "anon";
  const storageKey = `${PREFIX}${key}`;

  // Return stored variant (sticky)
  const stored = localStorage.getItem(storageKey);
  if (stored) return stored;

  const def = getExperiment(key);

  // Check mutual exclusion
  if (def && !isLayerAvailable(def, userId)) {
    // Excluded from this experiment
    const holdout = def.variants[0]?.name ?? "A";
    try { localStorage.setItem(storageKey, holdout); } catch { /**/ }
    return holdout;
  }

  let variant: Variant;

  if (!def) {
    // Unknown experiment — deterministic 50/50
    variant = deterministicFloat(key, userId) < 0.5 ? "A" : "B";
  } else if (def.type === "bandit" && def.bandit) {
    const arms = loadBanditArms(key, def.variants.map(v => v.name));
    const totalPulls = arms.reduce((s, a) => s + a.pullCount, 0);
    const { algorithm, epsilon = 0.1, warmupImpressions = 1000 } = def.bandit;

    switch (algorithm) {
      case "epsilon_greedy": variant = banditEpsilonGreedy(arms, epsilon, warmupImpressions); break;
      case "ucb1": variant = banditUCB1(arms, totalPulls); break;
      case "thompson_sampling": variant = banditThompson(arms); break;
      case "exp3": variant = banditExp3(arms); break;
      default: variant = banditThompson(arms);
    }
  } else {
    // Standard allocation-based assignment (deterministic)
    const rand = deterministicFloat(key, userId);
    let acc = 0;
    variant = def.variants[0]?.name ?? "A";
    for (const v of def.variants) {
      acc += v.allocation;
      if (rand < acc) { variant = v.name; break; }
    }
  }

  // Claim layer and persist
  if (def) claimLayer(def, userId);
  try { localStorage.setItem(storageKey, variant); } catch { /**/ }

  return variant;
}

export function forceVariant(key: string, variant: Variant): void {
  if (typeof window === "undefined") return;
  try { localStorage.setItem(`${PREFIX}${key}`, variant); } catch { /**/ }
}

export function clearVariant(key: string): void {
  if (typeof window === "undefined") return;
  try { localStorage.removeItem(`${PREFIX}${key}`); } catch { /**/ }
}

export function getAllVariants(): Record<string, Variant> {
  if (typeof window === "undefined") return {};
  const out: Record<string, Variant> = {};
  EXPERIMENTS.forEach(exp => {
    try {
      const stored = localStorage.getItem(`${PREFIX}${exp.key}`);
      if (stored) out[exp.key] = stored;
    } catch { /**/ }
  });
  return out;
}

// ─── Statistical significance ─────────────────────────────────────────────────

export interface VariantStats { impressions: number; clicks: number; conversions?: number; revenue?: number; }

export interface SignificanceResult {
  ctrA: number; ctrB: number;
  liftAbsolute: number; liftRelative: number;
  zScore: number; pValue: number;
  confidenceInterval: [number, number];  // 95% CI on lift
  isSignificant: boolean;
  power: number;                         // Statistical power estimate
  samplesNeeded: number;                 // For desired power
  winner: "A" | "B" | "inconclusive";
  recommendation: string;
}

export function calculateSignificance(a: VariantStats, b: VariantStats, confidenceLevel = 0.95): SignificanceResult {
  const ctrA = a.impressions > 0 ? a.clicks / a.impressions : 0;
  const ctrB = b.impressions > 0 ? b.clicks / b.impressions : 0;

  const pooled = (a.clicks + b.clicks) / (a.impressions + b.impressions + Number.EPSILON);
  const se = Math.sqrt(pooled * (1 - pooled) * (1 / (a.impressions || 1) + 1 / (b.impressions || 1)));
  const zScore = se === 0 ? 0 : (ctrB - ctrA) / se;

  const pValue = 2 * (1 - normalCDF(Math.abs(zScore)));
  const alpha = 1 - confidenceLevel;
  const isSignificant = pValue < alpha;

  const liftAbsolute = ctrB - ctrA;
  const liftRelative = ctrA > 0 ? liftAbsolute / ctrA : 0;

  // 95% CI on lift
  const zAlpha = 1.96;
  const seLift = Math.sqrt(
    ctrA * (1 - ctrA) / (a.impressions || 1) + ctrB * (1 - ctrB) / (b.impressions || 1)
  );
  const ciLow = liftAbsolute - zAlpha * seLift;
  const ciHigh = liftAbsolute + zAlpha * seLift;

  // Statistical power (1 - β) for current sample
  const power = isSignificant ? 1 - normalCDF(zAlpha - Math.abs(zScore)) : normalCDF(Math.abs(zScore) - zAlpha);

  // Samples needed for 80% power with current effect size
  const targetPower = 0.8;
  const zBeta = 0.84;
  const samplesNeeded = liftAbsolute !== 0
    ? Math.ceil(Math.pow(zAlpha + zBeta, 2) * (pooled * (1 - pooled) * 2) / Math.pow(liftAbsolute, 2))
    : Infinity;

  const winner = isSignificant ? (ctrB > ctrA ? "B" : "A") : "inconclusive";

  let recommendation: string;
  if (!isSignificant && (a.impressions + b.impressions) < samplesNeeded) {
    recommendation = `Collect more data — need ~${samplesNeeded.toLocaleString()} total impressions (have ${(a.impressions + b.impressions).toLocaleString()})`;
  } else if (!isSignificant) {
    recommendation = "No significant difference detected — consider ending the test or trying a bolder change";
  } else if (winner === "B") {
    recommendation = `Variant B wins with ${(liftRelative * 100).toFixed(1)}% relative lift — deploy B and run a follow-up test`;
  } else {
    recommendation = `Variant A outperforms — keep A and test new challenger`;
  }

  return {
    ctrA, ctrB, liftAbsolute, liftRelative, zScore, pValue,
    confidenceInterval: [ciLow, ciHigh],
    isSignificant, power, samplesNeeded, winner, recommendation,
  };
}

// ─── Bayesian A/B ────────────────────────────────────────────────────────────

export interface BayesianResult {
  probBBeatsA: number;           // P(B > A)
  expectedLift: number;          // Expected lift if B is deployed
  credibleInterval: [number, number];  // 95% highest-density interval
  decisionRecommendation: "deploy_b" | "keep_a" | "continue_testing";
}

/**
 * Synchronous Bayesian A/B — fast path with reduced simulations.
 * Use bayesianABAsync for full precision without blocking the UI.
 *
 * BUG-13 FIX: default simulations reduced from 50,000 → 5,000 for the
 * sync version. 5,000 iterations take ~15ms and yield statistically
 * adequate results for real-time UI display.
 */
export function bayesianAB(
  a: VariantStats,
  b: VariantStats,
  simulations = 5_000   // BUG-13 FIX: was 50_000 (200-400ms block), now 5k (~15ms)
): BayesianResult {
  return _runBayesianSync(a, b, simulations);
}

function _runBayesianSync(a: VariantStats, b: VariantStats, simulations: number): BayesianResult {
  const alphaA = a.clicks + 1;
  const betaA = a.impressions - a.clicks + 1;
  const alphaB = b.clicks + 1;
  const betaB = b.impressions - b.clicks + 1;

  let bWins = 0;
  const lifts: number[] = [];

  for (let i = 0; i < simulations; i++) {
    const rateA = sampleBeta(alphaA, betaA);
    const rateB = sampleBeta(alphaB, betaB);
    const lift = rateA > 0 ? (rateB - rateA) / rateA : 0;
    lifts.push(lift);
    if (rateB > rateA) bWins++;
  }

  const probBBeatsA = bWins / simulations;
  lifts.sort((x, y) => x - y);
  const expectedLift = lifts.reduce((s, v) => s + v, 0) / simulations;
  const lo = lifts[Math.floor(0.025 * simulations)];
  const hi = lifts[Math.floor(0.975 * simulations)];

  let decisionRecommendation: BayesianResult["decisionRecommendation"];
  if (probBBeatsA > 0.95) decisionRecommendation = "deploy_b";
  else if (probBBeatsA < 0.05) decisionRecommendation = "keep_a";
  else decisionRecommendation = "continue_testing";

  return { probBBeatsA, expectedLift, credibleInterval: [lo, hi], decisionRecommendation };
}

/**
 * Async Bayesian A/B — full 50k simulations, chunked to avoid blocking the main thread.
 * BUG-13 FIX: 50k iterations are split into 500-iteration chunks yielded via
 * setTimeout(0), keeping the UI responsive during the full computation.
 *
 * @example
 * const result = await bayesianABAsync(variantA, variantB, 50_000);
 */
export async function bayesianABAsync(
  a: VariantStats,
  b: VariantStats,
  simulations = 50_000,
  chunkSize = 500
): Promise<BayesianResult> {
  const alphaA = a.clicks + 1;
  const betaA = a.impressions - a.clicks + 1;
  const alphaB = b.clicks + 1;
  const betaB = b.impressions - b.clicks + 1;

  let bWins = 0;
  const lifts: number[] = [];

  for (let i = 0; i < simulations; i += chunkSize) {
    const end = Math.min(i + chunkSize, simulations);
    for (let j = i; j < end; j++) {
      const rateA = sampleBeta(alphaA, betaA);
      const rateB = sampleBeta(alphaB, betaB);
      const lift = rateA > 0 ? (rateB - rateA) / rateA : 0;
      lifts.push(lift);
      if (rateB > rateA) bWins++;
    }
    // Yield control back to the event loop between chunks
    await new Promise<void>(resolve => setTimeout(resolve, 0));
  }

  const probBBeatsA = bWins / simulations;
  lifts.sort((x, y) => x - y);
  const expectedLift = lifts.reduce((s, v) => s + v, 0) / simulations;
  const lo = lifts[Math.floor(0.025 * simulations)];
  const hi = lifts[Math.floor(0.975 * simulations)];

  let decisionRecommendation: BayesianResult["decisionRecommendation"];
  if (probBBeatsA > 0.95) decisionRecommendation = "deploy_b";
  else if (probBBeatsA < 0.05) decisionRecommendation = "keep_a";
  else decisionRecommendation = "continue_testing";

  return { probBBeatsA, expectedLift, credibleInterval: [lo, hi], decisionRecommendation };
}

// ─── Normal CDF ──────────────────────────────────────────────────────────────

function normalCDF(x: number): number {
  const t = 1 / (1 + 0.2316419 * Math.abs(x));
  const d = 0.3989423 * Math.exp((-x * x) / 2);
  const p = d * t * (0.3193815 + t * (-0.3565638 + t * (1.781478 + t * (-1.821256 + t * 1.330274))));
  return x > 0 ? 1 - p : p;
}
