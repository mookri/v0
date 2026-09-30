import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import CertificationClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

// Generate page metadata using SEO manager
export const metadata = seoManager.generateMetadata({
  title: "Certification Programs - Navdhya",
  description: "Earn authorized cultural training certifications in Vedic rituals, astrology, and spiritual practices. Advance your knowledge and credentials with our professional certification programs.",
  path: "/learn/certified",
  keywords: ["certification programs", "vedic rituals", "astrology certification", "spiritual practices"]
})

// Schema markup for the certification page
const CERTIFICATION_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Certification Programs - Navdhya",
  description: "Earn authorized cultural training certifications in Vedic rituals, astrology, and spiritual practices. Advance your knowledge and credentials with our professional certification programs.",
  path: "/learn/certified",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Learning", url: "https://www.navdhya.com/learn" },
    { name: "Certification Programs", url: "https://www.navdhya.com/learn/certified" }
  ]
})

export default async function CertificationPage() {
  const certificationSchema = JSON.stringify(CERTIFICATION_SCHEMA)
  
  // Resolve context using the server function
  const context = await resolveContext('/learn/certified');
  
  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <Script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: certificationSchema }}
        />
        <CertificationClient certificationSchema={certificationSchema} />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}