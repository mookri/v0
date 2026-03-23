// /lib/linking/graph.ts
// Internal link graph — resolves related pages for cross-linking.
// v2 — added try/catch on every external call, in-memory LRU cache,
//      proper deduplication, and graceful degradation on bad slugs.

import type { SEOPage, ContentCluster } from '@/lib/content/types';
import { getPage } from '@/lib/content/loader';
import { getSlugsByCluster, getRegistryEntry, getClusterMeta } from '@/lib/content/registry';
import { CORE_ENTITY_LINKS } from '@/lib/seo/entity';

export interface InternalLink {
  label: string;
  href: string;
  cluster: ContentCluster;
}

// ---------------------------------------------------------------------------
// Simple in-process LRU cache (avoids re-resolving the same page repeatedly
// across concurrent SSG/ISR renders in the same Node process).
// ---------------------------------------------------------------------------

interface CacheEntry<T> {
  value: T;
  expiresAt: number;
}

const CACHE_TTL_MS = 60_000; // 1 minute
const relatedCache = new Map<string, CacheEntry<InternalLink[]>>();

function cacheGet<T>(key: string, store: Map<string, CacheEntry<T>>): T | undefined {
  const entry = store.get(key);
  if (!entry) return undefined;
  if (Date.now() > entry.expiresAt) {
    store.delete(key);
    return undefined;
  }
  return entry.value;
}

function cacheSet<T>(key: string, value: T, store: Map<string, CacheEntry<T>>): void {
  // Evict oldest entry if cache is large
  if (store.size > 500) {
    const firstKey = store.keys().next().value;
    if (firstKey !== undefined) store.delete(firstKey);
  }
  store.set(key, { value, expiresAt: Date.now() + CACHE_TTL_MS });
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Safely load a page — returns undefined instead of throwing.
 */
function safeGetPage(slug: string): SEOPage | undefined {
  try {
    return getPage(slug) ?? undefined;
  } catch {
    return undefined;
  }
}

/**
 * Safely load a registry entry — returns undefined instead of throwing.
 */
function safeGetEntry(slug: string) {
  try {
    return getRegistryEntry(slug) ?? undefined;
  } catch {
    return undefined;
  }
}

/**
 * Safely load slugs for a cluster — returns empty array instead of throwing.
 */
function safeGetClusterSlugs(cluster: ContentCluster): string[] {
  try {
    return getSlugsByCluster(cluster) ?? [];
  } catch {
    return [];
  }
}

// ---------------------------------------------------------------------------
// Public API
// ---------------------------------------------------------------------------

/**
 * Get related pages for a given page.
 * Priority: explicit relatedSlugs → same-cluster pages → (core brand pages handled separately).
 *
 * FIX: All external calls are wrapped in try/catch.
 *      Deduplication is now on href (not just slug) to prevent cross-cluster duplicates.
 *      Results are cached per-slug for 60 seconds to avoid repeat work during ISR.
 */
export function getRelatedPages(
  page: SEOPage,
  limit: number = 5,
): InternalLink[] {
  const cacheKey = `${page.slug}:${limit}`;
  const cached = cacheGet(cacheKey, relatedCache);
  if (cached) return cached;

  const links: InternalLink[] = [];
  const usedSlugs = new Set<string>([page.slug]);
  const usedHrefs = new Set<string>();

  function addLink(slug: string, label: string, href: string, cluster: ContentCluster): boolean {
    if (usedSlugs.has(slug) || usedHrefs.has(href) || links.length >= limit) return false;
    links.push({ label, href, cluster });
    usedSlugs.add(slug);
    usedHrefs.add(href);
    return true;
  }

  // 1. Explicit related slugs from the page's JSON
  for (const relSlug of page.relatedSlugs ?? []) {
    if (links.length >= limit) break;

    const relPage = safeGetPage(relSlug);
    if (!relPage) continue;

    const entry = safeGetEntry(relSlug);
    if (!entry) continue;

    const basePath = getClusterMeta(entry.cluster)?.basePath || entry.cluster;
    addLink(relSlug, relPage.title, `/${basePath}/${relSlug}`, entry.cluster);
  }

  // 2. Same-cluster pages (fill remaining slots)
  if (links.length < limit) {
    const clusterSlugs = safeGetClusterSlugs(page.cluster);
    for (const cSlug of clusterSlugs) {
      if (links.length >= limit) break;

      const cPage = safeGetPage(cSlug);
      if (!cPage) continue;

      const basePath = getClusterMeta(page.cluster)?.basePath || page.cluster;
      addLink(cSlug, cPage.title, `/${basePath}/${cSlug}`, page.cluster);
    }
  }

  cacheSet(cacheKey, links, relatedCache);
  return links;
}

/**
 * Get the core entity links every SEO page should link to.
 * Filters out the current page's own path to avoid self-linking.
 */
export function getCoreLinks(
  currentSlug: string,
): typeof CORE_ENTITY_LINKS[number][] {
  return CORE_ENTITY_LINKS.filter(
    (link) => !link.href.endsWith(`/${currentSlug}`),
  );
}

/**
 * Build the full link set for a page: related + core entity links.
 * Gracefully returns empty arrays on any failure.
 */
export function getPageLinks(page: SEOPage): {
  related: InternalLink[];
  core: typeof CORE_ENTITY_LINKS[number][];
} {
  try {
    return {
      related: getRelatedPages(page, 5),
      core: getCoreLinks(page.slug),
    };
  } catch {
    return { related: [], core: [] };
  }
}

/**
 * Invalidate the related-pages cache for a specific slug.
 * Call this from the ISR revalidation endpoint when content changes.
 */
export function invalidatePageCache(slug: string): void {
  for (const key of relatedCache.keys()) {
    if (key.startsWith(`${slug}:`)) {
      relatedCache.delete(key);
    }
  }
}

/**
 * Invalidate the entire related-pages cache.
 * Used during full-site rebuilds.
 */
export function clearLinkCache(): void {
  relatedCache.clear();
}