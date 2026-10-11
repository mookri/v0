// /lib/search/params.ts
// Pure search-parameter helpers — SEO track S40.
//
// Deliberately free of fs, fetch and server-only so the same module backs
// the client search box (header autocomplete), the /search server page and
// Jest. Anything that talks to the corpus or the backend lives in
// lib/search/federated.ts (which imports fs and must never reach a client
// bundle); this file only parses, clamps and builds URLs.

/** Full search queries are clamped to this length (mirrors the BFF route). */
export const MAX_QUERY_LENGTH = 200;

/** Suggest type-ahead is clamped tighter — it fires per keystroke. */
export const MAX_SUGGEST_QUERY_LENGTH = 80;

/**
 * Entity types the search surface accepts as a `type` filter. Anything
 * else in the URL is ignored (treated as "all types") rather than passed
 * through to the backend — a free-form type string would be an injection
 * vector into the Laravel provider registry.
 */
export const SEARCH_TYPES = ["temple", "pandit", "puja_service", "seo_page"] as const;

export type SearchType = (typeof SEARCH_TYPES)[number];

export function isSearchType(value: string | null | undefined): value is SearchType {
  return !!value && (SEARCH_TYPES as readonly string[]).includes(value);
}

/** First value of a possibly-repeated URL parameter, trimmed. */
export function firstParam(value: string | string[] | undefined): string {
  const raw = Array.isArray(value) ? (value[0] ?? "") : (value ?? "");
  return raw.trim();
}

/** Trim + hard-clamp a search query. Never returns more than the max. */
export function clampSearchQuery(q: string): string {
  return q.trim().slice(0, MAX_QUERY_LENGTH);
}

export interface SearchHrefOptions {
  q?: string | null;
  type?: string | null;
  city?: string | null;
}

/** Build a /search URL, omitting empty parts. */
export function buildSearchHref({ q, type, city }: SearchHrefOptions): string {
  const params = new URLSearchParams();
  const trimmedQ = (q ?? "").trim();
  if (trimmedQ) params.set("q", trimmedQ);
  const trimmedType = (type ?? "").trim();
  if (trimmedType) params.set("type", trimmedType);
  const trimmedCity = (city ?? "").trim();
  if (trimmedCity) params.set("city", trimmedCity);

  const qs = params.toString();
  return qs ? `/search?${qs}` : "/search";
}

/** Human label for an entity type (shared by chips, badges and groups). */
export function typeLabel(type: string): string {
  switch (type) {
    case "seo_page":
      return "Guides & Blogs";
    case "temple":
      return "Temples";
    case "pandit":
      return "Pandits";
    case "puja_service":
      return "Puja Services";
    default:
      return type.charAt(0).toUpperCase() + type.slice(1).replace(/[_-]/g, " ");
  }
}

// ---------------------------------------------------------------------------
// Suggestion shapes (client-safe copies of the /api/search/suggest contract)
// ---------------------------------------------------------------------------

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

/** Flatten grouped suggestions into the keyboard-navigable list order. */
export function flattenSuggestions(groups: SuggestionGroup[]): SuggestionItem[] {
  return groups.flatMap((group) => group.items);
}
