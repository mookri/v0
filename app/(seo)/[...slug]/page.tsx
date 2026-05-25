// /app/(seo)/[...slug]/page.tsx
// Catch-all dynamic route for the SEO platform — v2
// Changes: stronger notFound guards, proper async params, error surfacing.

import { notFound } from 'next/navigation';
import type { Metadata } from 'next';
import { getStaticParams, resolveSlugFromParams, getPage } from '@/lib/content/loader';
import { getRegistryEntry, getClusterMeta } from '@/lib/content/registry';
import { generatePageMetadata } from '@/lib/seo/metadata';
import { ArticlePage } from '@/components/seo/ArticlePage';
import { getPageLinks } from '@/lib/linking/graph';
import { generateSchemas } from '@/lib/schema/generator';

// ISR cache time — 1 hour. Individual pages can override via automation.revalidateAfter.
export const revalidate = 3600;
export const dynamicParams = false;

// Hint to Next.js that this route uses dynamic params for better streaming
export const dynamic = 'auto';

interface PageProps {
  params: Promise<{ slug: string[] }>;
}

/**
 * Pre-render all registered pages at build time.
 */
export async function generateStaticParams() {
  try {
    return getStaticParams();
  } catch (err) {
    console.error('[SEO] generateStaticParams failed:', err);
    return [];
  }
}

/**
 * Generate Next.js Metadata. Returns empty object on any failure to avoid
 * breaking the page render.
 */
export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  try {
    const { slug: slugParts } = await params;
    const slug = resolveSlugFromParams(slugParts);
    if (!slug) return {};

    const page = getPage(slug);
    const entry = getRegistryEntry(slug);
    if (!page || !entry) return {};

    const clusterMeta = getClusterMeta(entry.cluster);
    if (!clusterMeta) return {};

    const base = generatePageMetadata(page, clusterMeta.basePath);

    // Apply noindex override
    if (page.noindex) {
      return { ...base, robots: { index: false, follow: false } };
    }

    return base;
  } catch (err) {
    console.error('[SEO] generateMetadata error:', err);
    return {};
  }
}

/**
 * Render the SEO content page.
 * All data loading is synchronous (content lives on disk / in-process cache),
 * so this renders in a single pass with no waterfall.
 */
export default async function SEOContentPage({ params }: PageProps) {
  const { slug: slugParts } = await params;
  const slug = resolveSlugFromParams(slugParts);

  if (!slug) notFound();

  const page = getPage(slug);
  const entry = getRegistryEntry(slug);

  if (!page || !entry) notFound();

  const clusterMeta = getClusterMeta(entry.cluster);
  if (!clusterMeta) notFound();

  // Respect noindex pages — return 404 from the router so they're never crawled
  if (page.noindex) notFound();

  const internalLinks = getPageLinks(page);
  const schemas = generateSchemas(page, clusterMeta.basePath, clusterMeta.label);

  return (
    <>
      {schemas.map((schema, index) => (
        <script
          key={`${page.slug}-schema-${index}`}
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
        />
      ))}
      <ArticlePage
        page={page}
        clusterMeta={clusterMeta}
        internalLinks={internalLinks}
      />
    </>
  );
}
