import { SearchEntityType, SearchVisibility } from './CanonicalQuery';

export interface SearchDocument {
  id: string;
  entityType: SearchEntityType;
  title: string;
  subtitle?: string;
  slug?: string;
  locale: string;
  categories: string[];
  tags: string[];
  searchableText: string[];
  facets: Record<string, string[]>;
  price?: { from?: number; currency: string };
  availability?: { searchable: boolean };
  popularity?: { score: number };
  status: 'ACTIVE' | 'INACTIVE';
  visibility: SearchVisibility;
  sourceVersion: number;
  schemaVersion: number;
  indexedAt: string;
}

export function isEligible(doc: SearchDocument, audience: SearchVisibility): boolean {
  if (doc.status !== 'ACTIVE') return false;
  const rank: Record<SearchVisibility, number> = { public: 0, authenticated: 1, partner: 2, admin: 3, internal: 4 };
  return rank[doc.visibility] <= rank[audience];
}
