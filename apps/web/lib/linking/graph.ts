// /lib/linking/graph.ts
// Internal link graph — resolves related pages for cross-linking.
// v3 — async loaders, dual backend support, ISR-compatible caching

import type { SEOPage, ContentCluster } from "@/lib/content/types";
import { getPageAsync } from "@/lib/content/loader";
import {
  getRegistryEntryAsync,
  getClusterMetaAsync,
  getSlugsByClusterAsync,
} from "@/lib/content/registry";
import { CORE_ENTITY_LINKS } from "@/lib/seo/entity";

export interface InternalLink {
  label: string;
  href: string;
  cluster: ContentCluster;
}

// ---------------------------------------------------------------------------
// Simple in-process LRU cache (avoids re-resolving the same page repeatedly
// across concurrent ISR renders in the same Node process).
// ---------------------------------------------------------------------------

interface CacheEntry<T> {
  value: T;
}

const relatedCache = new Map<string, CacheEntry<InternalLink[]>>();

function cacheGet<T>(key: string, store: Map<string, CacheEntry<T>>): T | undefined {
  // Entries are invalidated by invalidatePageCache() when the ISR endpoint
  // revalidates a slug, so a TTL check is not needed here.
  return store.get(key)?.value;
}

function cacheSet<T>(key: string, value: T, store: Map<string, CacheEntry<T>>): void {
  if (store.size > 500) {
    const firstKey = store.keys().next().value;
    if (firstKey !== undefined) store.delete(firstKey);
  }
  store.set(key, { value });
}

// ---------------------------------------------------------------------------
// Async Helpers
// ---------------------------------------------------------------------------

/**
 * Safely load a page — returns undefined instead of throwing.
 */
async function safeGetPage(slug: string): Promise<SEOPage | undefined> {
  try {
    return (await getPageAsync(slug)) ?? undefined;
  } catch {
    return undefined;
  }
}

/**
 * Safely load a registry entry — returns undefined instead of throwing.
 */
async function safeGetEntry(slug: string) {
  try {
    return (await getRegistryEntryAsync(slug)) ?? undefined;
  } catch {
    return undefined;
  }
}

/**
 * Safely load slugs for a cluster — returns empty array instead of throwing.
 */
async function safeGetClusterSlugs(cluster: ContentCluster): Promise<string[]> {
  try {
    return (await getSlugsByClusterAsync(cluster)) ?? [];
  } catch {
    return [];
  }
}

async function safeGetClusterMeta(cluster: ContentCluster) {
  try {
    return (await getClusterMetaAsync(cluster)) ?? undefined;
  } catch {
    return undefined;
  }
}

// ---------------------------------------------------------------------------
// Public API
// ---------------------------------------------------------------------------

/**
 * Get related pages for a given page.
 * Priority: explicit relatedSlugs → same-cluster pages → (core brand pages handled separately).
 *
 * All external calls are async with try/catch.
 * Deduplication is on href (not just slug) to prevent cross-cluster duplicates.
 * Results are cached per-slug for 60 seconds to avoid repeat work during ISR.
 */
export async function getRelatedPages(
  page: SEOPage,
  limit: number = 5,
): Promise<InternalLink[]> {
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

    const relPage = await safeGetPage(relSlug);
    if (!relPage) continue;

    const entry = await safeGetEntry(relSlug);
    if (!entry) continue;

    const basePath = (await safeGetClusterMeta(entry.cluster))?.basePath || entry.cluster;
    addLink(relSlug, relPage.title, `/${basePath}/${relSlug}`, entry.cluster);
  }

  // 2. Same-cluster pages (fill remaining slots with nearby pages so the
  // internal link graph is distributed more evenly across the cluster)
  if (links.length < limit) {
    const clusterSlugs = await safeGetClusterSlugs(page.cluster);
    const basePath = (await safeGetClusterMeta(page.cluster))?.basePath || page.cluster;
    const currentIndex = clusterSlugs.indexOf(page.slug);

    if (currentIndex >= 0) {
      for (let offset = 1; offset < clusterSlugs.length && links.length < limit; offset += 1) {
        const candidates = [
          clusterSlugs[currentIndex - offset],
          clusterSlugs[currentIndex + offset],
        ].filter((slug): slug is string => Boolean(slug));

        for (const cSlug of candidates) {
          if (links.length >= limit) break;

          const cPage = await safeGetPage(cSlug);
          if (!cPage) continue;

          addLink(cSlug, cPage.title, `/${basePath}/${cSlug}`, page.cluster);
        }
      }
    }

    if (links.length < limit) {
      for (const cSlug of clusterSlugs) {
        if (links.length >= limit) break;

        const cPage = await safeGetPage(cSlug);
        if (!cPage) continue;

        addLink(cSlug, cPage.title, `/${basePath}/${cSlug}`, page.cluster);
      }
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
export async function getPageLinks(page: SEOPage): Promise<{
  related: InternalLink[];
  core: typeof CORE_ENTITY_LINKS[number][];
}> {
  try {
    return {
      related: await getRelatedPages(page, 5),
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