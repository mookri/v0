import { seoManager } from "@/lib/seo-manager"
import NewsClient from './client'
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/news",
    title: "News & Updates - Navdhya",
    description: "Real-time temple information, spiritual event updates, and latest developments in our community.",
    keywords: ["spiritual news", "temple updates", "vedic events", "community news", "spiritual updates"]
  })
}

export default async function NewsPage() {
  // Resolve context using the new resolver
  const context = await resolveContext('/resources/news');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <NewsClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}