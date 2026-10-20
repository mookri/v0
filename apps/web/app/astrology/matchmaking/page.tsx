import { seoManager } from "@/lib/seo-manager"
import HoroscopeMatchingClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the Horoscope Matching page
const HOROSCOPE_MATCHING_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Horoscope Matching - Navdhya",
  description: "Expert Vedic astrology horoscope matching for marriage compatibility and relationship harmony.",
  path: "/astrology/matchmaking",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology", url: "https://www.navdhya.com/astrology" },
    { name: "Horoscope Matching", url: "https://www.navdhya.com/astrology/matchmaking" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/astrology/matchmaking",
    title: "Horoscope Matching for Marriage Compatibility",
    description: "Review marriage compatibility with Navdhya horoscope matching, kundli milan guidance, and relationship-focused Vedic insights.",
    keywords: ["horoscope matching", "kundli milan", "marriage compatibility", "vedic astrology"]
  })
}

export default async function HoroscopeMatchingPage() {
  const horoscopeMatchingSchema = JSON.stringify(HOROSCOPE_MATCHING_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/astrology/matchmaking');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: horoscopeMatchingSchema }}
        />
        <HoroscopeMatchingClient horoscopeMatchingSchema={horoscopeMatchingSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
