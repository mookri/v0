import { seoManager } from "@/lib/seo-manager"
import ToolsClient from './client'
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/resources/tools",
    title: "Tools & Calculators - Navdhya",
    description: "Horoscope, Muhurat, and Panchang calculators for determining auspicious timings and astrological insights.",
    keywords: ["astrology tools", "muhurat calculator", "panchang calculator", "horoscope calculator", "spiritual tools"]
  })
}

export default async function ToolsPage() {
  // Resolve context using the new resolver
  const context = await resolveContext('/resources/tools');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ToolsClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  );
}