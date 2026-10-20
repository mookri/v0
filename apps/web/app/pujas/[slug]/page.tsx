import { notFound } from "next/navigation";
import type { Metadata } from "next";
import PujaDetailsClient from "./client";
import { loadPujaRouteData } from "@/dal/seo";
import { generatePageMetadata } from "@/lib/seo/metadata";
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator";
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary";
import { resolveContext } from "@/lib/context-resolver";

// Hourly refresh + on-demand busting live in the "use cache" entry this page
// reads (dal/seo.ts) — this app runs with `cacheComponents: true`, where
// `export const revalidate` on a segment is not allowed. No
// generateStaticParams either: builds never need the content backend.

type PageProps = {
  params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  try {
    const { slug } = await params;
    const data = await loadPujaRouteData(slug);
    if (!data) {
      return {
        title: "Puja Not Found | Navdhya",
        description: "The puja you requested is not available.",
      };
    }

    const base = generatePageMetadata(data.page, data.clusterMeta.basePath);
    if (data.page.noindex) {
      return { ...base, robots: { index: false, follow: false } };
    }
    return base;
  } catch (err) {
    console.error("[pujas] generateMetadata error:", err);
    return {};
  }
}

export default async function PujaDetailsPage({ params }: PageProps) {
  const { slug } = await params;
  const data = await loadPujaRouteData(slug);
  if (!data) notFound();

  const context = await resolveContext(`/pujas/${slug}`);

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        {data.schemas.map((schema, index) => (
          <script
            key={`${data.slug}-schema-${index}`}
            type="application/ld+json"
            dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
          />
        ))}
        <PujaDetailsClient page={data.page} puja={data.puja} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}
