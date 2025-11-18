import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import HelpClient from "./client"
import { LayoutOrchestrator } from '@/components/dlo-x/LayoutOrchestrator'
import { ErrorBoundary } from '@/components/dlo-x/ErrorBoundary'
import { resolveContext } from '@/lib/context-resolver'

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Help & Support",
  description: "Find answers to frequently asked questions about our puja services, pandit consultations, and spiritual guidance.",
  path: "/help",
  keywords: ["help", "support", "faq", "customer service", "spiritual guidance"]
})

// Schema markup for the help page
const HELP_SCHEMA = seoManager.generateSchema({
  type: "FAQPage",
  name: "Help & Support - Navdhya",
  description: "Find answers to frequently asked questions about our puja services, pandit consultations, and spiritual guidance.",
  path: "/help",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Help & Support", url: "https://www.navdhya.com/help" }
  ]
})

export default async function HelpPage() {
  const helpSchema = JSON.stringify(HELP_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/help');

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: helpSchema }}
        />
        <HelpClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}