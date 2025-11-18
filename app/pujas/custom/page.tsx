import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import CustomPujasClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Custom Pujas",
  description: "Personalized puja services tailored to your specific spiritual needs and family traditions.",
  path: "/pujas/custom",
  keywords: [
    "custom puja services",
    "personalized rituals",
    "family traditions",
    "vedic pandits",
    "spiritual ceremonies"
  ]
})

// Schema markup for the Custom Pujas page
const CUSTOM_PUJAS_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Custom Pujas - Navdhya",
  description: "Personalized puja services tailored to your specific spiritual needs and family traditions.",
  path: "/pujas/custom",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
    { name: "Custom Pujas", url: "https://www.navdhya.com/pujas/custom" }
  ]
})

export default async function CustomPujasPage() {
  const customPujasSchema = JSON.stringify(CUSTOM_PUJAS_SCHEMA)
  
  // Resolve context using the new resolver
  const context = await resolveContext('/pujas/custom');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: customPujasSchema }}
        />
        <CustomPujasClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}