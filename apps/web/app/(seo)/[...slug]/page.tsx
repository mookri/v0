// /app/(seo)/[...slug]/page.tsx
// Catch-all dynamic route for the SEO platform — v3
//
// v3 changes (SEO track S36):
//   - No generateStaticParams: nothing is prerendered at build time, so a
//     build needs no DB/API and never bakes 1,265 pages into the output.
//   - Hourly refresh via the Cache Components model (this app runs with
//     `cacheComponents: true`, where `export const revalidate` is not
//     allowed): the full data payload is a "use cache" entry with a one-hour
//     lifetime in dal/seo.ts, taggable so POST /api/revalidate busts a slug
//     on demand without a rebuild.
//   - Data loading reads the Laravel SEO API when SEO_CONTENT_SOURCE=api,
//     else the disk corpus.

import { notFound } from "next/navigation";
import type { Metadata } from "next";
import { loadSeoRouteData } from "@/dal/seo";
import { generatePageMetadata } from "@/lib/seo/metadata";
import { ArticlePage } from "@/components/seo/ArticlePage";

interface PageProps {
  params: Promise<{ slug: string[] }>;
}

/**
 * Generate Next.js Metadata. Returns an empty object on any failure so a
 * bad slug or a slow content backend never breaks the render.
 */
export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  try {
    const { slug: slugParts } = await params;
    const data = await loadSeoRouteData(slugParts);
    if (!data) return {};

    const base = generatePageMetadata(data.page, data.clusterMeta.basePath);

    // Defence in depth: loadSeoRouteData already refuses noindex pages.
    if (data.page.noindex) {
      return { ...base, robots: { index: false, follow: false } };
    }

    return base;
  } catch (err) {
    console.error("[SEO] generateMetadata error:", err);
    return {};
  }
}

/**
 * Render the SEO content page.
 */
export default async function SEOContentPage({ params }: PageProps) {
  const { slug: slugParts } = await params;
  const data = await loadSeoRouteData(slugParts);

  // Unresolvable slug, missing page, or noindex → 404 from the router so
  // the URL is never crawled.
  if (!data) notFound();

  return (
    <>
      {data.schemas.map((schema, index) => (
        <script
          key={`${data.slug}-schema-${index}`}
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
        />
      ))}
      <ArticlePage
        page={data.page}
        clusterMeta={data.clusterMeta}
        internalLinks={data.internalLinks}
      />
    </>
  );
}
