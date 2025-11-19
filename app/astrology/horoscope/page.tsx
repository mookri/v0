import { seoManager } from "@/lib/seo-manager"
import HoroscopeClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the Horoscope page
const HOROSCOPE_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Daily Horoscope - Navdhya",
  description: "Accurate daily, weekly, and monthly horoscope predictions based on Vedic astrology for guidance in all aspects of life.",
  path: "/astrology/horoscope",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Astrology", url: "https://www.navdhya.com/astrology" },
    { name: "Horoscope", url: "https://www.navdhya.com/astrology/horoscope" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/astrology/horoscope",
    title: "Daily Horoscope - Navdhya",
    description: "Accurate daily, weekly, and monthly horoscope predictions based on Vedic astrology for guidance in all aspects of life.",
    keywords: ["daily horoscope", "weekly horoscope", "monthly horoscope", "vedic astrology predictions"]
  })
}

export default async function HoroscopePage() {
  const horoscopeSchema = JSON.stringify(HOROSCOPE_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/astrology/horoscope');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: horoscopeSchema }}
        />
        <HoroscopeClient horoscopeSchema={horoscopeSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}