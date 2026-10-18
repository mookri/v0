export const MAX_QUERY_LENGTH = 200;
export const MAX_SUGGEST_QUERY_LENGTH = 80;

export const SEARCH_TYPES = ['temple', 'pandit', 'puja_service', 'seo_page'] as const;
export type SearchType = (typeof SEARCH_TYPES)[number];

export function isSearchType(value: string | null | undefined): value is SearchType {
  return !!value && (SEARCH_TYPES as readonly string[]).includes(value);
}

export function firstParam(value: string | string[] | undefined): string {
  const raw = Array.isArray(value) ? (value[0] ?? '') : (value ?? '');
  return raw.trim();
}

export function clampSearchQuery(q: string): string {
  return q.trim().slice(0, MAX_QUERY_LENGTH);
}

export interface SearchHrefOptions {
  q?: string | null;
  type?: string | null;
  city?: string | null;
}

export function buildSearchHref({ q, type, city }: SearchHrefOptions): string {
  const params = new URLSearchParams();
  const trimmedQ = (q ?? '').trim();
  if (trimmedQ) params.set('q', trimmedQ);
  const trimmedType = (type ?? '').trim();
  if (trimmedType) params.set('type', trimmedType);
  const trimmedCity = (city ?? '').trim();
  if (trimmedCity) params.set('city', trimmedCity);
  const qs = params.toString();
  return qs ? `/search?${qs}` : '/search';
}

export function typeLabel(type: string): string {
  switch (type) {
    case 'seo_page':
      return 'Guides & Blogs';
    case 'temple':
      return 'Temples';
    case 'pandit':
      return 'Pandits';
    case 'puja_service':
      return 'Puja Services';
    default:
      return type.charAt(0).toUpperCase() + type.slice(1).replace(/[_-]/g, ' ');
  }
}

export interface SuggestionItem {
  title: string;
  url: string;
  slug?: string | null;
  city?: string | null;
  subtitle?: string | null;
}

export interface SuggestionGroup {
  type: string;
  label: string;
  items: SuggestionItem[];
}

export function flattenSuggestions(groups: SuggestionGroup[]): SuggestionItem[] {
  return groups.flatMap((group) => group.items);
}