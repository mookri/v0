import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import RegionalPujasClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Regional Pujas",
  description: "Authentic regional puja traditions from across India - South Indian, North Indian, Bengali, Tamil, and more. Experience diverse cultural rituals performed by pandits specialized in regional traditions in Delhi NCR.",
  path: "/pujas/regional",
  keywords: [
    "regional pujas",
    "south indian rituals",
    "north indian pujas",
    "bengali durga puja",
    "tamil traditions"
  ]
})

// Schema markup for the regional pujas page
const REGIONAL_PUJAS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Regional Pujas - Navdhya",
  description: "Authentic regional puja traditions from across India - South Indian, North Indian, Bengali, Tamil, and more. Experience diverse cultural rituals performed by pandits specialized in regional traditions in Delhi NCR.",
  path: "/pujas/regional",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Regional Pujas", url: "https://www.navdhya.com/pujas/regional" }
  ]
})

export default async function RegionalPujasPage() {
  const pujaSchema = JSON.stringify(REGIONAL_PUJAS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/regional');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: pujaSchema }}
        />
        <RegionalPujasClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}