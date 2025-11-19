import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"
import LearnClient from './client'

// Schema markup for the main learn page
const LEARN_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Learning & Spiritual Growth - Navdhya",
  description: "Expand your spiritual knowledge with our comprehensive learning resources. From beginner courses to advanced certification programs, discover pathways to deepen your understanding of Vedic wisdom.",
  path: "/learn",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" }
  ]
})

export default async function LearnPage() {
  const learnSchema = JSON.stringify(LEARN_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/learn');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: learnSchema }}
        />
        <LearnClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}