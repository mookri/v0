import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import PersonalizedRitualsClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Personalized Rituals",
  description: "Tailored rituals for individual or family needs. Custom puja services designed specifically for your unique requirements and spiritual goals, performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas/personalized",
  keywords: [
    "personalized rituals",
    "custom puja",
    "tailored ceremonies",
    "individual needs",
    "family rituals"
  ]
})

// Schema markup for the personalized rituals page
const PERSONALIZED_RITUALS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Personalized Rituals - Navdhya",
  description: "Tailored rituals for individual or family needs. Custom puja services designed specifically for your unique requirements and spiritual goals, performed by certified Vedic pandits in Delhi NCR.",
  path: "/pujas/personalized",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Personalized Rituals", url: "https://www.navdhya.com/pujas/personalized" }
  ]
})

export default async function PersonalizedRitualsPage() {
  const pujaSchema = JSON.stringify(PERSONALIZED_RITUALS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/personalized');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: pujaSchema }}
        />
        <PersonalizedRitualsClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}