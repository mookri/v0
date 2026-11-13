export interface RatingAggregate {
  count: number;
  sum: number;
  distribution: { one: number; two: number; three: number; four: number; five: number };
}

export type ConfidenceTier =
  | 'NO_REVIEWS'
  | 'NEW_PROVIDER'
  | 'EMERGING_CONFIDENCE'
  | 'ESTABLISHED_SAMPLE';

export const CATEGORY_PRIORS: Record<string, number> = {
  default: 4.2,
  'PUJA-LAKSHMI': 4.3,
  GRIHA_PRAVESH: 4.35,
  SHRADDHA: 4.4,
  SATYANARAYAN: 4.3,
};

export function emptyAggregate(): RatingAggregate {
  return { count: 0, sum: 0, distribution: { one: 0, two: 0, three: 0, four: 0, five: 0 } };
}

export function addRating(agg: RatingAggregate, overall: number): RatingAggregate {
  const key = (['one', 'two', 'three', 'four', 'five'] as const)[overall - 1];
  if (!key) return agg;
  return { count: agg.count + 1, sum: agg.sum + overall, distribution: { ...agg.distribution, [key]: agg.distribution[key] + 1 } };
}

export function averageRating(agg: RatingAggregate): number | undefined {
  if (agg.count === 0) return undefined;
  return Math.round((agg.sum / agg.count) * 10) / 10;
}

export function bayesianAverage(
  ratings: readonly number[],
  category = 'default',
  weightC = 10,
): number | null {
  const n = ratings.length;
  if (n === 0) return null;
  const mu = CATEGORY_PRIORS[category] ?? CATEGORY_PRIORS.default;
  const sum = ratings.reduce((acc, r) => acc + r, 0);
  return Math.round(((weightC * mu + sum) / (weightC + n)) * 100) / 100;
}

export function wilsonLowerBound(successes: number, total: number, z = 1.96): number | null {
  if (total <= 0) return null;
  const p = Math.max(0, Math.min(1, successes / total));
  const z2 = z * z;
  const denom = 1 + z2 / total;
  const center = p + z2 / (2 * total);
  const margin = z * Math.sqrt((p * (1 - p) + z2 / (4 * total)) / total);
  return Math.round(Math.max(0, (center - margin) / denom) * 10000) / 10000;
}

export function decayedAverage(
  samples: ReadonlyArray<{ rating: number; ageDays: number }>,
  halfLifeDays = 90,
): number | null {
  if (samples.length === 0) return null;
  const lambda = Math.log(2) / Math.max(1, halfLifeDays);
  let weightedSum = 0;
  let totalWeight = 0;
  for (const s of samples) {
    const w = Math.exp(-lambda * Math.max(0, s.ageDays));
    weightedSum += w * s.rating;
    totalWeight += w;
  }
  if (totalWeight <= 0) return null;
  return Math.round((weightedSum / totalWeight) * 100) / 100;
}

export function confidenceTier(sampleCount: number): {
  tier: ConfidenceTier;
  label: string;
  showNumericHeadline: boolean;
} {
  if (sampleCount <= 0) {
    return { tier: 'NO_REVIEWS', label: 'No reviews yet', showNumericHeadline: false };
  }
  if (sampleCount <= 4) {
    return { tier: 'NEW_PROVIDER', label: 'New provider', showNumericHeadline: false };
  }
  if (sampleCount <= 19) {
    return { tier: 'EMERGING_CONFIDENCE', label: 'Emerging confidence', showNumericHeadline: true };
  }
  return { tier: 'ESTABLISHED_SAMPLE', label: 'Established sample', showNumericHeadline: true };
}

export interface QualitySignal {
  id: string;
  subject: { type: 'pandit' | 'temple' | 'vendor' | 'service_instance'; id: string };
  metric: string;
  value: number;
  periodStart: string;
  periodEnd: string;
  calculationVersion: string;
  generatedAt: string;
}

export interface PublicTrustSummary {
  targetId: string;
  rating?: number | null;
  reviewCount: number;
  confidenceTier: ConfidenceTier;
  confidenceLabel: string;
  verifiedServiceCount: number;
  publishedBadges: string[];
  policyVersion: string;
  generatedAt: string;
}

export interface TrustBadge {
  id: string;
  type: string;
  subjectId: string;
  issuedAt: string;
  expiresAt?: string;
  policyVersion: string;
  status: 'ACTIVE' | 'EXPIRED' | 'REVOKED';
}

export function isBadgeActive(badge: TrustBadge, nowIso: string): boolean {
  if (badge.status !== 'ACTIVE') return false;
  if (badge.expiresAt && badge.expiresAt <= nowIso) return false;
  return true;
}
