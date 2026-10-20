import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import HavanYagyaClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Havan & Yagya",
  description: "Sacred fire rituals performed by certified Vedic pandits for purification, prosperity, and spiritual upliftment.",
  path: "/pujas/havan",
  keywords: [
    "havan",
    "yagya",
    "fire ritual",
    "vedic pandits",
    "spiritual ceremonies"
  ]
})

// Schema markup for the Havan & Yagya page
const HAVAN_YAGYA_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Havan & Yagya - Navdhya",
  description: "Sacred fire rituals performed by certified Vedic pandits for purification, prosperity, and spiritual upliftment.",
  path: "/pujas/havan",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Havan & Yagya", url: "https://www.navdhya.com/pujas/havan" }
  ]
})

export default async function HavanYagyaPage() {
  const havanYagyaSchema = JSON.stringify(HAVAN_YAGYA_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/havan');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: havanYagyaSchema }}
        />
        <HavanYagyaClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}