import { seoManager } from "@/lib/seo-manager"
import InsightsClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the pandit insights page
const PANDIT_INSIGHTS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Pandit Insights & Resources - Navdhya",
  description: "Expert insights, resources, and knowledge from verified Vedic pandits. Learn about puja rituals, astrology, spiritual practices, and ancient wisdom.",
  path: "/pandits/insights",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Pandits & Experts", url: "https://www.navdhya.com/pandits" },
    { name: "Insights", url: "https://www.navdhya.com/pandits/insights" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/pandits/insights",
    title: "Pandit Insights & Resources - Navdhya",
    description: "Expert insights, resources, and knowledge from verified Vedic pandits. Learn about puja rituals, astrology, spiritual practices, and ancient wisdom.",
    keywords: ["pandit insights", "vedic wisdom", "spiritual practices", "ancient knowledge"]
  })
}

export default async function PanditInsightsPage() {
  const insightsSchema = JSON.stringify(PANDIT_INSIGHTS_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/pandits/insights');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: insightsSchema }}
        />
        <InsightsClient insightsSchema={insightsSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}