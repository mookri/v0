import { notFound } from "next/navigation";
import type { Metadata } from "next";
import PujaDetailsClient from "./client";
import { getPage } from "@/lib/content/loader";
import { getPujaPageViewModel } from "@/lib/content/puja";
import { getSlugsByCluster, getClusterMeta } from "@/lib/content/registry";
import { generatePageMetadata } from "@/lib/seo/metadata";
import { generateSchemas } from "@/lib/schema/generator";
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator";
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary";
import { resolveContext } from "@/lib/context-resolver";


type PageProps = {
  params: Promise<{ slug: string }>;
};

function getPujaPage(slug: string) {
  const page = getPage(slug);
  if (!page || page.cluster !== "pujas") return null;
  return page;
}

export function generateStaticParams() {
  return getSlugsByCluster("pujas").map((slug) => ({ slug }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const page = getPujaPage(slug);
  if (!page) {
    return {
      title: "Puja Not Found | Navdhya",
      description: "The puja you requested is not available.",
    };
  }

  const clusterMeta = getClusterMeta("pujas");
  if (!clusterMeta) return {};

  return generatePageMetadata(page, clusterMeta.basePath);
}

export default async function PujaDetailsPage({ params }: PageProps) {
  const { slug } = await params;
  const page = getPujaPage(slug);
  if (!page) notFound();

  const clusterMeta = getClusterMeta("pujas");
  if (!clusterMeta) notFound();

  const context = await resolveContext(`/pujas/${slug}`);
  const schemas = generateSchemas(page, clusterMeta.basePath, clusterMeta.label);
  const puja = getPujaPageViewModel(page);

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        {schemas.map((schema, index) => (
          <script
            key={`${page.slug}-schema-${index}`}
            type="application/ld+json"
            dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
          />
        ))}
        <PujaDetailsClient page={page} puja={puja} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}
