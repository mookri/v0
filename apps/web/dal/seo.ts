import "server-only";
import { unstable_cacheLife as cacheLife, unstable_cacheTag as cacheTag } from "next/cache";
import type { SEOPage, ClusterMeta } from "@/lib/content/types";
import { getPujaPageViewModel } from "@/lib/content/puja";
import { resolveSlugFromParamsAsync, getPageAsync } from "@/lib/content/loader";
import { getRegistryEntryAsync, getClusterMetaAsync } from "@/lib/content/registry";
import { getPageLinks } from "@/lib/linking/graph";
import { generateSchemas } from "@/lib/schema/generator";

// ---------------------------------------------------------------------------
// SEO route data access layer (Cache Components build).
//
// This app runs with `cacheComponents: true`, where `export const revalidate`
// on a segment is not allowed. The hourly-refresh behaviour the SEO track
// decided on is expressed here instead: every route's full data payload is a
// "use cache" entry with a one-hour lifetime, taggable so POST
// /api/revalidate can bust a single slug without a rebuild.
//
// The payload includes everything the page needs (page, cluster metadata,
// internal links, JSON-LD schemas) so no uncached I/O happens during render.
// ---------------------------------------------------------------------------

type Schemas = ReturnType<typeof generateSchemas>;

export type SeoRouteData = {
  slug: string;
  page: SEOPage;
  clusterMeta: ClusterMeta;
  internalLinks: Awaited<ReturnType<typeof getPageLinks>>;
  schemas: Schemas;
};

export type PujaRouteData = {
  slug: string;
  page: SEOPage;
  clusterMeta: ClusterMeta;
  puja: ReturnType<typeof getPujaPageViewModel>;
  schemas: Schemas;
};

/**
 * Resolve + load one SEO catch-all route (`/[cluster]/[slug]`).
 * Returns null when the slug/base-path combination does not resolve, the page
 * is missing, or the page is noindex (callers 404).
 */
export async function loadSeoRouteData(slugParts: string[]): Promise<SeoRouteData | null> {
  "use cache";
  cacheLife("hours");

  const slug = await resolveSlugFromParamsAsync(slugParts);
  if (!slug) return null;
  cacheTag(`seo:route:${slug}`);

  const [page, entry] = await Promise.all([
    getPageAsync(slug),
    getRegistryEntryAsync(slug),
  ]);
  if (!page || !entry) return null;

  const clusterMeta = await getClusterMetaAsync(entry.cluster);
  if (!clusterMeta || page.noindex) return null;

  const internalLinks = await getPageLinks(page);

  return {
    slug,
    page,
    clusterMeta,
    internalLinks,
    schemas: generateSchemas(page, clusterMeta.basePath, clusterMeta.label),
  };
}

/**
 * Load one `/pujas/[slug]` route. Returns null when the slug is not a pujas
 * cluster page or is noindex (callers 404).
 */
export async function loadPujaRouteData(slug: string): Promise<PujaRouteData | null> {
  "use cache";
  cacheLife("hours");
  cacheTag(`seo:puja:${slug}`);

  const [page, entry] = await Promise.all([
    getPageAsync(slug),
    getRegistryEntryAsync(slug),
  ]);
  if (!page || !entry || page.cluster !== "pujas" || page.noindex) return null;

  const clusterMeta = await getClusterMetaAsync("pujas");
  if (!clusterMeta) return null;

  return {
    slug,
    page,
    clusterMeta,
    puja: getPujaPageViewModel(page),
    schemas: generateSchemas(page, clusterMeta.basePath, clusterMeta.label),
  };
}
