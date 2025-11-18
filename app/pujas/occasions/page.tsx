import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import OccasionPujasClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Occasion Pujas",
  description: "Special puja services for life's important moments - weddings, baby naming, annaprashan, and other significant occasions. Performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas/occasions",
  keywords: [
    "occasion pujas",
    "life events",
    "wedding ceremony",
    "baby naming",
    "annaprashan"
  ]
})

// Schema markup for the occasion pujas page
const OCCASION_PUJAS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Occasion Pujas - Navdhya",
  description: "Special puja services for life's important moments - weddings, baby naming, annaprashan, and other significant occasions. Performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas/occasions",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Occasion Pujas", url: "https://www.navdhya.com/pujas/occasions" }
  ]
})

export default async function OccasionPujasPage() {
  const pujaSchema = JSON.stringify(OCCASION_PUJAS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/occasions');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: pujaSchema }}
        />
        <OccasionPujasClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}