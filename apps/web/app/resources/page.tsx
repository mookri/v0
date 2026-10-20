import { seoManager } from "@/lib/seo-manager"
import ResourcesClient from './client'
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources",
    title: "Resources - Navdhya",
    description: "Comprehensive spiritual resources including articles, scriptures, tools, news, and a digital library for your spiritual journey.",
    keywords: ["spiritual resources", "hindu scriptures", "vedic texts", "spiritual tools"]
  })
}

export default async function ResourcesPage() {
  // Resolve context using the new resolver
  const context = await resolveContext('/resources');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ResourcesClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}