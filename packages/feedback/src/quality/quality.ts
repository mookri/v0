export interface RatingAggregate {
  count: number;
  sum: number;
  distribution: { one: number; two: number; three: number; four: number; five: number };
}

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

export interface QualitySignal {
  id: string;
  subject: { type: 'partner' | 'service' | 'fulfillment'; id: string };
  metric: string;
  value: number;
  periodStart: string;
  periodEnd: string;
  calculationVersion: string;
  generatedAt: string;
}

export interface PublicTrustSummary {
  targetId: string;
  rating?: number;
  reviewCount: number;
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
