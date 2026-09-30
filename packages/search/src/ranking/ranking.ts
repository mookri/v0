import { SearchDocument } from '../domain/SearchDocument';

export interface RankingFeatures {
  textScore: number;
  popularityScore: number;
  availabilityScore: number;
  locationScore: number;
  qualityScore: number;
}

export interface RankingPolicy {
  id: string;
  version: string;
  weights: { text: number; popularity: number; availability: number; location: number; quality: number };
}

export const DEFAULT_RANKING_POLICY: RankingPolicy = {
  id: 'puja-default',
  version: 'v1',
  weights: { text: 0.5, popularity: 0.2, availability: 0.15, location: 0.1, quality: 0.05 },
};

export function scoreDocument(doc: SearchDocument, features: RankingFeatures, policy: RankingPolicy): number {
  const w = policy.weights;
  return (
    w.text * features.textScore +
    w.popularity * features.popularityScore +
    w.availability * features.availabilityScore +
    w.location * features.locationScore +
    w.quality * features.qualityScore
  );
}

export function rankDeterministic<T extends { id: string; score: number }>(items: T[]): T[] {
  return [...items].sort((a, b) => b.score - a.score || a.id.localeCompare(b.id));
}
