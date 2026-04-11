import { seoManager } from "@/lib/seo-manager"
import RemediesClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the Remedies page
const REMEDIES_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Astrological Remedies - Navdhya",
  description: "Personalized Vedic astrological remedies to overcome challenges and enhance positive planetary influences in your life.",
  path: "/astrology/remedies",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology", url: "https://www.navdhya.com/astrology" },
    { name: "Astrological Remedies", url: "https://www.navdhya.com/astrology/remedies" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/astrology/remedies",
    title: "Astrological Remedies for Planets, Doshas & Life Goals",
    description: "Explore personalized Vedic remedies from Navdhya for doshas, difficult planetary periods, and practical life challenges.",
    keywords: ["astrological remedies", "vedic remedies", "planetary remedies", "mantra remedies"]
  })
}

export default async function RemediesPage() {
  const remediesSchema = JSON.stringify(REMEDIES_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/astrology/remedies');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: remediesSchema }}
        />
        <RemediesClient remediesSchema={remediesSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
