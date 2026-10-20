export type SearchEntityType = 'puja' | 'pandit' | 'service' | 'article' | 'location' | 'collection';
export type SearchVisibility = 'public' | 'authenticated' | 'partner' | 'admin' | 'internal';
export type SearchSort = 'relevance' | 'popular' | 'price_asc' | 'price_desc' | 'newest';

export interface SearchFilters {
  category?: string[];
  city?: string;
  language?: string[];
  priceRange?: { min?: number; max?: number };
  entityTypes?: SearchEntityType[];
}

export interface SearchContext {
  product: 'web' | 'customer';
  userId?: string;
  locale: string;
  city?: string;
}

export interface SearchQuery {
  text?: string;
  entityTypes?: SearchEntityType[];
  filters?: SearchFilters;
  sort?: SearchSort;
  cursor?: string;
  limit: number;
  locale: string;
  context?: SearchContext;
}

export function normalizeQueryText(raw: string): string {
  return raw
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^\p{L}\p{N}\s]/gu, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 200);
}
