import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import PujaCatalogClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { getDefaultContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Puja Services Catalog",
  description: "Comprehensive catalog of authentic Hindu puja services and spiritual rituals performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas",
  keywords: [
    "puja services",
    "hindu rituals",
    "vedic pandits",
    "spiritual ceremonies",
    "delhi puja services"
  ]
})

// Schema markup for the puja catalog page
const PUJA_CATALOG_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Puja Services Catalog - Navdhya",
  description: "Comprehensive catalog of authentic Hindu puja services and spiritual rituals performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" }
  ]
})

export default async function PujaCatalogPage() {
  const pujaSchema = JSON.stringify(PUJA_CATALOG_SCHEMA)
  
  // Use default context for statically rendered page to avoid build-time context resolution
  const context = getDefaultContext('/pujas');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: pujaSchema }}
        />
        <PujaCatalogClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}