import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import TempleOfferingsClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Temple Offerings",
  description: "Special puja services and offerings for temple ceremonies - Diwali, Navratri, Makar Sankranti, and other temple rituals. Authentic temple worship performed by experienced pandits in Delhi NCR.",
  path: "/pujas/temple",
  keywords: [
    "temple offerings",
    "diwali puja",
    "navratri durga puja",
    "makar sankranti",
    "temple rituals"
  ]
})

// Schema markup for the temple offerings page
const TEMPLE_OFFERINGS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Temple Offerings - Navdhya",
  description: "Special puja services and offerings for temple ceremonies - Diwali, Navratri, Makar Sankranti, and other temple rituals. Authentic temple worship performed by experienced pandits in Delhi NCR.",
  path: "/pujas/temple",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Temple Offerings", url: "https://www.navdhya.com/pujas/temple" }
  ]
})

export default async function TempleOfferingsPage() {
  const pujaSchema = JSON.stringify(TEMPLE_OFFERINGS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/temple');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: pujaSchema }}
        />
        <TempleOfferingsClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}