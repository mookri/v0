import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import PujaHomeClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Puja at Home",
  description: "Authentic Hindu puja services performed by certified Vedic pandits in the comfort of your home.",
  path: "/pujas/home",
  keywords: ["home puja", "vedic rituals", "pandit at home", "spiritual ceremonies"]
})

// Schema markup for the Puja at Home page
const PUJA_HOME_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Puja at Home - Navdhya",
  description: "Authentic Hindu puja services performed by certified Vedic pandits in the comfort of your home.",
  path: "/pujas/home",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Puja at Home", url: "https://www.navdhya.com/pujas/home" }
  ]
})

export default async function PujaAtHomePage() {
  const pujaHomeSchema = JSON.stringify(PUJA_HOME_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/home');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: pujaHomeSchema }}
        />
        <PujaHomeClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}