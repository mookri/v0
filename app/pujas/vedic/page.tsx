import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import VedicRitualsClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Vedic Rituals",
  description: "Authentic Vedic rituals performed by certified scholars for spiritual growth, purification, and divine blessings.",
  path: "/pujas/vedic",
  keywords: [
    "vedic rituals",
    "samskaras",
    "yajnas",
    "vedic pujas",
    "scholar guidance"
  ]
})

// Schema markup for the Vedic Rituals page
const VEDIC_RITUALS_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Vedic Rituals - Navdhya",
  description: "Authentic Vedic rituals performed by certified scholars for spiritual growth, purification, and divine blessings.",
  path: "/pujas/vedic",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Vedic Rituals", url: "https://www.navdhya.com/pujas/vedic" }
  ]
})

export default async function VedicRitualsPage() {
  const vedicRitualsSchema = JSON.stringify(VEDIC_RITUALS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/vedic');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: vedicRitualsSchema }}
        />
        <VedicRitualsClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}