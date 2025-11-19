import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"
import PanditsClient from './client'

// Schema markup for the main pandits page
const PANDITS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Pandits & Experts - Navdhya",
  description: "Connect with verified and experienced Vedic pandits in Delhi NCR. Book authentic puja services, astrology consultations, and spiritual guidance from certified experts.",
  path: "/pandits",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Pandits & Experts", url: "https://www.navdhya.com/pandits" }
  ]
})

export default async function PanditsPage() {
  const panditsSchema = JSON.stringify(PANDITS_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/pandits');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: panditsSchema }}
        />
        <PanditsClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}