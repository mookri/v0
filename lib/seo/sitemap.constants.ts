export const SITEMAP_CHUNK_SIZE = 40;

/** How long the in-memory build cache is considered fresh. */
export const SITEMAP_CACHE_TTL_MS = 60 * 60 * 1000; // 1 hour

/**
 * How long a stale cache entry is allowed to be served when a rebuild fails.
 * Acts as the circuit-breaker window.
 */
export const SITEMAP_STALE_TTL_MS = 24 * 60 * 60 * 1000; // 24 hours

/** Sent to HTTP clients and CDNs. */
export const SITEMAP_HTTP_MAX_AGE_SECONDS = 3_600;
export const SITEMAP_HTTP_SWR_SECONDS = 86_400;

export const SITEMAP_CONTENT_TYPE = "application/xml; charset=utf-8";
