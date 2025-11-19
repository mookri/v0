import { seoManager } from "@/lib/seo-manager"
import FindPanditClient from "./client"
import Script from "next/script"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Schema markup for the find pandit page
const FIND_PANDIT_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Find Qualified Pandits - Navdhya",
  description: "Browse and book qualified Vedic pandits for your spiritual rituals and pujas. Connect with verified experts in Delhi NCR for authentic Hindu ceremonies and consultations.",
  path: "/find-pandit",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Find Pandit", url: "https://www.navdhya.com/find-pandit" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/find-pandit",
    title: "Find Qualified Pandits - Navdhya",
    description: "Browse and book qualified Vedic pandits for your spiritual rituals and pujas. Connect with verified experts in Delhi NCR for authentic Hindu ceremonies and consultations.",
    keywords: ["find pandit", "vedic pandits", "puja pandit", "spiritual guide", "hindu rituals"]
  })
}

export default async function FindPanditPage() {
  const findPanditSchema = JSON.stringify(FIND_PANDIT_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/find-pandit');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: findPanditSchema }}
        />
        <FindPanditClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}