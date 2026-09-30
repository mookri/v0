/**
 * Server-only module — never import from client components.
 *
 * Provides a memoised, TTL-backed cache for the expensive sitemap build.
 * Falls back to a stale entry when a rebuild throws, so a transient error
 * in the content layer never takes the sitemap offline.
 */
import type { MetadataRoute } from "next";
import { buildSitemapChunks, buildSitemapEntries } from "@/lib/seo/sitemap";
import {
  SITEMAP_CACHE_TTL_MS,
  SITEMAP_STALE_TTL_MS,
} from "@/lib/seo/sitemap.constants";

interface CacheEntry {
  chunks: MetadataRoute.Sitemap[];
  builtAt: number;
}

// Module-level singleton — survives across requests in the same Node process.
// In serverless cold starts it rebuilds once per instance, which is fine.
let cache: CacheEntry | null = null;

function isStale(entry: CacheEntry): boolean {
  return Date.now() - entry.builtAt > SITEMAP_CACHE_TTL_MS;
}

function isExpired(entry: CacheEntry): boolean {
  return Date.now() - entry.builtAt > SITEMAP_STALE_TTL_MS;
}

/**
 * Returns sitemap chunks, rebuilding when the cache is stale.
 * Throws only when there is no usable cache and the build itself fails.
 */
export async function getSitemapChunks(): Promise<{
  chunks: MetadataRoute.Sitemap[];
  fromCache: boolean;
  builtAt: number;
}> {
  // Fast path — fresh cache.
  if (cache && !isStale(cache)) {
    return { chunks: cache.chunks, fromCache: true, builtAt: cache.builtAt };
  }

  try {
    const chunks = buildSitemapChunks(buildSitemapEntries());
    cache = { chunks, builtAt: Date.now() };
    return { chunks, fromCache: false, builtAt: cache.builtAt };
  } catch (err) {
    // Rebuild failed — serve stale if we have something young enough.
    if (cache && !isExpired(cache)) {
      console.error(
        "[sitemap-cache] rebuild failed, serving stale cache",
        { staleSinceMs: Date.now() - cache.builtAt },
        err,
      );
      return { chunks: cache.chunks, fromCache: true, builtAt: cache.builtAt };
    }

    // Nothing usable — propagate so the route can return 500.
    throw err;
  }
}
