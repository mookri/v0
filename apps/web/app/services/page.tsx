import dynamic from "next/dynamic"
import { seoManager } from "@/lib/seo-manager"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Dynamically import the client component
const ServicesClient = dynamic(() => import("./client"), {
  loading: () => <div className="min-h-screen flex items-center justify-center">Loading...</div>
})

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Puja Services",
  description: "Authentic Hindu puja services performed by certified Vedic pandits in Delhi",
  path: "/services",
  keywords: ["puja services", "vedic pandits", "hindu rituals", "spiritual services"]
})

export default async function ServicesPage() {
  // Resolve context using the new resolver
  const context = await resolveContext('/services');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ServicesClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}