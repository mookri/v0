import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"
import CertifiedPanditsClient from './client'

// Schema markup for the certified pandits page
const CERTIFIED_PANDITS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Certified Pandits - Navdhya",
  description: "Connect with verified and experienced Vedic pandits in Delhi NCR. Book authentic puja services, astrology consultations, and spiritual guidance from certified experts.",
  path: "/pandits/certified",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Pandits & Experts", url: "https://www.navdhya.com/pandits" },
    { name: "Certified Pandits", url: "https://www.navdhya.com/pandits/certified" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/pandits/certified",
    title: "Certified Pandits - Navdhya",
    description: "Become a verified pandit with Navdhya. Join our network of certified Vedic experts and serve families in Delhi NCR with authentic puja services and spiritual guidance.",
    keywords: ["join as pandit", "certified pandit", "vedic expert", "puja services"]
  })
}
export default async function CertifiedPanditsPage() {
  const certifiedPanditsSchema = JSON.stringify(CERTIFIED_PANDITS_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/pandits/certified');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: certifiedPanditsSchema }}
        />
        <CertifiedPanditsClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}