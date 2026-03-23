// /lib/content/loader.ts
// Reads and validates individual JSON content files.

import { readFileSync, existsSync } from 'fs';
import { join } from 'path';
import type { SEOPage, ContentCluster } from './types';
import { getRegistryEntry, getAllSlugs, getSlugsByCluster, getClusterMeta } from './registry';
import { parseJsonString } from './json';

const PAGES_DIR = join(process.cwd(), 'content', 'pages');

/**
 * Load a single page by slug.
 * Returns null if the slug is not registered or the file doesn't exist.
 */
export function getPage(slug: string): SEOPage | null {
  const entry = getRegistryEntry(slug);
  if (!entry) return null;

  const filePath = join(PAGES_DIR, entry.file);
  if (!existsSync(filePath)) return null;

  try {
    const raw = readFileSync(filePath, 'utf-8');
    const page = parseJsonString<SEOPage>(raw);

    // Basic validation
    if (!page.id || !page.slug || !page.title || !page.content) {
      console.warn(`[content-loader] Invalid page data in ${entry.file}`);
      return null;
    }

    return page;
  } catch (err) {
    console.error(`[content-loader] Failed to load ${entry.file}:`, err);
    return null;
  }
}

/**
 * Load all pages.
 */
export function getAllPages(): SEOPage[] {
  const slugs = getAllSlugs();
  const pages: SEOPage[] = [];

  for (const slug of slugs) {
    const page = getPage(slug);
    if (page) pages.push(page);
  }

  return pages;
}

/**
 * Load all pages in a specific cluster.
 */
export function getPagesByCluster(cluster: ContentCluster): SEOPage[] {
  const slugs = getSlugsByCluster(cluster);
  const pages: SEOPage[] = [];

  for (const slug of slugs) {
    const page = getPage(slug);
    if (page) pages.push(page);
  }

  return pages;
}

/**
 * Get all slugs formatted for Next.js generateStaticParams.
 * Returns array of { slug: string[] } for catch-all routes.
 */
export function getStaticParams(): Array<{ slug: string[] }> {
  const slugs = getAllSlugs();
  return slugs.map((s) => {
    const entry = getRegistryEntry(s);
    if (!entry) return { slug: [s] };

    // Route as /cluster/slug — e.g. /brand/what-is-navdhya
    const clusterMeta = getClusterMeta(entry.cluster);
    const basePath = clusterMeta?.basePath ?? entry.cluster;
    return { slug: [basePath, s] };
  });
}

/**
 * Resolve a catch-all slug array back to a page slug.
 * e.g. ["brand", "what-is-navdhya"] → "what-is-navdhya"
 */
export function resolveSlugFromParams(slugParts: string[]): string | null {
  if (!slugParts || slugParts.length === 0) return null;
  if (slugParts.length > 2) return null;

  const pageSlug = slugParts[slugParts.length - 1];
  const entry = getRegistryEntry(pageSlug);
  if (!entry) return null;

  if (slugParts.length === 2) {
    const clusterMeta = getClusterMeta(entry.cluster);
    const expectedBasePath = clusterMeta?.basePath ?? entry.cluster;
    if (slugParts[0] !== expectedBasePath) {
      return null;
    }
  }

  return pageSlug;
}
