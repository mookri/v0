import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"
import PanditsClient from './client'

export const metadata = seoManager.generateMetadata({
  title: "Verified Pandits for Puja, Rituals & Guidance",
  description: "Browse verified Navdhya pandits for home pujas, ceremonies, astrology guidance, and ritual support across Delhi NCR.",
  path: "/pandits",
  keywords: ["verified pandits", "book pandit", "puja priest", "astrology expert", "Navdhya pandits"],
})

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
