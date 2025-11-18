import { seoManager } from "@/lib/seo-manager"
import DigitalLibraryClient from './client'
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/library",
    title: "Digital Library - Navdhya",
    description: "Downloadable wisdom including spiritual texts, guided meditations, ritual instructions, and educational resources.",
    keywords: ["digital library", "spiritual resources", "vedic texts", "meditation guides", "ritual instructions"]
  })
}

export default async function DigitalLibraryPage() {
  // Resolve context using the new resolver
  const context = await resolveContext('/resources/library');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <DigitalLibraryClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}