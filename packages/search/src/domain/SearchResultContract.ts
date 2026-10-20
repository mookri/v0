import { SearchEntityType } from './CanonicalQuery';

export type ResultSource = 'organic' | 'recommended' | 'sponsored' | 'editorial';

export interface SearchResultItem {
  id: string;
  entityType: SearchEntityType;
  title: string;
  slug?: string;
  source: ResultSource;
  score: number;
}

export interface FacetBucket {
  value: string;
  count: number;
}

export interface SearchResult {
  items: SearchResultItem[];
  total?: number;
  nextCursor?: string;
  facets?: Record<string, FacetBucket[]>;
  meta: { indexVersion: string; rankingPolicyVersion: string; queryId: string };
}

export function emptyResult(queryId: string, didYouMean?: string): SearchResult & { didYouMean?: string } {
  return { items: [], facets: {}, meta: { indexVersion: 'local-v1', rankingPolicyVersion: 'puja-default-v1', queryId }, didYouMean };
}
